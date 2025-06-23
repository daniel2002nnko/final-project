<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin or warden
if (!is_logged_in() || (!check_role('admin') && !check_role('warden'))) {
    redirect("/JESUS/auth/login.php");
}

// Check if request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/admin/maintenance.php");
}

$request_id = (int)$_GET['id'];
$success = '';
$error = '';

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $new_status = sanitize_input($_POST['status']);
    $admin_notes = sanitize_input($_POST['admin_notes']);
    
    // Update the maintenance request status
    $update_sql = "UPDATE maintenance_requests SET 
                   status = ?, 
                   admin_notes = ?,
                   updated_by = ?,
                   updated_at = NOW() 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $admin_id = $_SESSION['user_id'];
    $update_stmt->bind_param("ssii", $new_status, $admin_notes, $admin_id, $request_id);
    
    if ($update_stmt->execute()) {
        $success = "Maintenance request status has been updated successfully.";
        
        // Log the status update
        try {
            // Check if table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'maintenance_logs'");
            if ($table_check->num_rows == 0) {
                // Create maintenance_logs table
                $create_table_sql = "CREATE TABLE IF NOT EXISTS maintenance_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    updated_by INT NOT NULL,
                    old_status VARCHAR(20) NOT NULL,
                    new_status VARCHAR(20) NOT NULL,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
                    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if ($conn->query($create_table_sql) === TRUE) {
                    // Try logging again after creating the table
                    $log_sql = "INSERT INTO maintenance_logs (request_id, updated_by, old_status, new_status, notes) 
                                VALUES (?, ?, (SELECT status FROM maintenance_requests WHERE id = ?), ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $log_stmt->bind_param("iisss", $request_id, $admin_id, $request_id, $new_status, $admin_notes);
                    $log_stmt->execute();
                }
            } else {
                // Table exists, insert log
                $log_sql = "INSERT INTO maintenance_logs (request_id, updated_by, old_status, new_status, notes) 
                            VALUES (?, ?, (SELECT status FROM maintenance_requests WHERE id = ?), ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("iisss", $request_id, $admin_id, $request_id, $new_status, $admin_notes);
                $log_stmt->execute();
            }
        } catch (Exception $e) {
            // Silently handle the error
        }
    } else {
        $error = "Error updating maintenance request: " . $conn->error;
    }
}

// Handle assignment to maintenance staff
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_staff') {
    $staff_id = (int)$_POST['staff_id'];
    
    // Update the maintenance request with assigned staff
    $update_sql = "UPDATE maintenance_requests SET 
                   assigned_to = ?,
                   status = 'in_progress',
                   updated_by = ?,
                   updated_at = NOW() 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $admin_id = $_SESSION['user_id'];
    $update_stmt->bind_param("iii", $staff_id, $admin_id, $request_id);
    
    if ($update_stmt->execute()) {
        $success = "Maintenance request has been assigned to staff successfully.";
    } else {
        $error = "Error assigning maintenance request: " . $conn->error;
    }
}

// Get maintenance request details
$sql = "SELECT mr.*, 
        s.first_name, s.last_name, s.admission_number, s.id as student_id,
        r.room_number, b.name as block_name,
        u.name as reported_by_name, u.email as reporter_email, u.phone as reporter_phone,
        staff.name as assigned_to_name, staff.email as staff_email, staff.phone as staff_phone
        FROM maintenance_requests mr
        JOIN students s ON mr.reported_by = s.user_id
        JOIN rooms r ON mr.room_id = r.id
        JOIN blocks b ON mr.block_id = b.id
        JOIN users u ON mr.reported_by = u.id
        LEFT JOIN users staff ON mr.assigned_to = staff.id
        WHERE mr.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect("/JESUS/pages/admin/maintenance.php");
}

$request = $result->fetch_assoc();

// Get maintenance logs for this request
$logs = [];
try {
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'maintenance_logs'");
    if ($table_check->num_rows > 0) {
        $logs_sql = "SELECT ml.*, u.name as updated_by_name
                    FROM maintenance_logs ml
                    JOIN users u ON ml.updated_by = u.id
                    WHERE ml.request_id = ?
                    ORDER BY ml.created_at DESC";
        $logs_stmt = $conn->prepare($logs_sql);
        $logs_stmt->bind_param("i", $request_id);
        $logs_stmt->execute();
        $logs_result = $logs_stmt->get_result();
        $logs = $logs_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Silently handle the error
}

// Get maintenance staff (users with role 'maintenance')
$staff_sql = "SELECT id, name FROM users WHERE role = 'maintenance' ORDER BY name";
$staff_result = $conn->query($staff_sql);
$maintenance_staff = [];
if ($staff_result) {
    $maintenance_staff = $staff_result->fetch_all(MYSQLI_ASSOC);
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/maintenance.php">Maintenance</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Request #<?php echo $request_id; ?></li>
                </ol>
            </nav>
            
            <h2><i class="fas fa-tools"></i> Maintenance Request Details</h2>
            <p>Viewing detailed information for maintenance request #<?php echo $request_id; ?></p>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Request Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Issue Details</h6>
                            <p><strong>Type:</strong> <?php echo $request['issue_type']; ?></p>
                            <p><strong>Description:</strong> <?php echo $request['description']; ?></p>
                            <p><strong>Priority:</strong> 
                                <?php 
                                    $priority_class = '';
                                    switch ($request['priority']) {
                                        case 'high':
                                            $priority_class = 'bg-danger';
                                            break;
                                        case 'medium':
                                            $priority_class = 'bg-warning';
                                            break;
                                        case 'low':
                                            $priority_class = 'bg-info';
                                            break;
                                    }
                                ?>
                                <span class="badge <?php echo $priority_class; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </p>
                            <p><strong>Status:</strong> 
                                <?php 
                                    $status_class = '';
                                    switch ($request['status']) {
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            break;
                                        case 'in_progress':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'completed':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'rejected':
                                            $status_class = 'bg-danger';
                                            break;
                                    }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </p>
                            <p><strong>Reported On:</strong> <?php echo date('F j, Y, g:i a', strtotime($request['created_at'])); ?></p>
                            <?php if (!empty($request['updated_at']) && $request['updated_at'] != $request['created_at']): ?>
                                <p><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($request['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Location Information</h6>
                            <p><strong>Block:</strong> <?php echo $request['block_name']; ?></p>
                            <p><strong>Room:</strong> <?php echo $request['room_number']; ?></p>
                            
                            <h6 class="mt-4">Reporter Information</h6>
                            <p><strong>Name:</strong> <?php echo $request['first_name'] . ' ' . $request['last_name']; ?></p>
                            <p><strong>Admission Number:</strong> <?php echo $request['admission_number']; ?></p>
                            <p><strong>Email:</strong> <?php echo $request['reporter_email']; ?></p>
                            <?php if (!empty($request['reporter_phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo $request['reporter_phone']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($request['admin_notes'])): ?>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6>Admin Notes</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br($request['admin_notes']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['image'])): ?>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h6>Attached Image</h6>
                                <div class="text-center">
                                    <img src="/JESUS/uploads/maintenance/<?php echo $request['image']; ?>" class="img-fluid img-thumbnail" style="max-height: 300px;" alt="Maintenance Issue">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Maintenance Logs Card -->
            <?php if (count($logs) > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Maintenance History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Updated By</th>
                                    <th>Status Change</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo $log['updated_by_name']; ?></td>
                                        <td>
                                            <?php 
                                                $old_status_class = '';
                                                switch ($log['old_status']) {
                                                    case 'pending':
                                                        $old_status_class = 'bg-warning';
                                                        break;
                                                    case 'in_progress':
                                                        $old_status_class = 'bg-info';
                                                        break;
                                                    case 'completed':
                                                        $old_status_class = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $old_status_class = 'bg-danger';
                                                        break;
                                                }
                                                
                                                $new_status_class = '';
                                                switch ($log['new_status']) {
                                                    case 'pending':
                                                        $new_status_class = 'bg-warning';
                                                        break;
                                                    case 'in_progress':
                                                        $new_status_class = 'bg-info';
                                                        break;
                                                    case 'completed':
                                                        $new_status_class = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $new_status_class = 'bg-danger';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $old_status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $log['old_status'])); ?>
                                            </span>
                                            <i class="fas fa-arrow-right mx-2"></i>
                                            <span class="badge <?php echo $new_status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $log['new_status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo !empty($log['notes']) ? nl2br($log['notes']) : '<em>No notes</em>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="fas fa-edit"></i> Update Status
                        </button>
                        
                        <?php if (empty($request['assigned_to']) && $request['status'] != 'completed' && $request['status'] != 'rejected' && count($maintenance_staff) > 0): ?>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignStaffModal">
                                <i class="fas fa-user-plus"></i> Assign Maintenance Staff
                            </button>
                        <?php endif; ?>
                        
                        <a href="/JESUS/pages/admin/student-details.php?id=<?php echo $request['student_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-user"></i> View Student Details
                        </a>
                        
                        <a href="/JESUS/pages/admin/maintenance.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Maintenance List
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Assigned Staff Card -->
            <?php if (!empty($request['assigned_to'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Assigned Maintenance Staff</h5>
                </div>
                <div class="card-body">
                    <h6><?php echo $request['assigned_to_name']; ?></h6>
                    <p><strong>Email:</strong> <?php echo $request['staff_email']; ?></p>
                    <?php if (!empty($request['staff_phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo $request['staff_phone']; ?></p>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] != 'completed' && $request['status'] != 'rejected'): ?>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" data-bs-toggle="modal" data-bs-target="#assignStaffModal">
                            <i class="fas fa-exchange-alt"></i> Reassign Staff
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Status Card -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Quick Status Update</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $request_id); ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="admin_notes" value="<?php echo $request['admin_notes']; ?>">
                        
                        <div class="d-grid gap-2">
                            <?php if ($request['status'] != 'in_progress'): ?>
                                <button type="submit" name="status" value="in_progress" class="btn btn-info">
                                    <i class="fas fa-hourglass-half"></i> Mark as In Progress
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] != 'completed'): ?>
                                <button type="submit" name="status" value="completed" class="btn btn-success">
                                    <i class="fas fa-check-circle"></i> Mark as Completed
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] != 'rejected'): ?>
                                <button type="submit" name="status" value="rejected" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Mark as Rejected
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] != 'pending'): ?>
                                <button type="submit" name="status" value="pending" class="btn btn-warning">
                                    <i class="fas fa-undo"></i> Reset to Pending
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    Update Request Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $request_id); ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo ($request['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo ($request['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo ($request['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="rejected" <?php echo ($request['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="5"><?php echo $request['admin_notes']; ?></textarea>
                        <div class="form-text">Add any notes about this maintenance request.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Staff Modal -->
<?php if (count($maintenance_staff) > 0): ?>
<div class="modal fade" id="assignStaffModal" tabindex="-1" aria-labelledby="assignStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assignStaffModalLabel">
                    Assign Maintenance Staff
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $request_id); ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_staff">
                    
                    <div class="mb-3">
                        <label for="staff_id" class="form-label">Select Maintenance Staff</label>
                        <select class="form-select" id="staff_id" name="staff_id" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($maintenance_staff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo ($request['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo $staff['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Assigning staff will automatically set the status to "In Progress".</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>



