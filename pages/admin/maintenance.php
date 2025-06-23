<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin or warden
if (!is_logged_in() || (!check_role('admin') && !check_role('warden'))) {
    redirect("/JESUS/auth/login.php");
}

$success = '';
$error = '';

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $request_id = (int)$_POST['request_id'];
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
            $log_sql = "INSERT INTO maintenance_logs (request_id, updated_by, old_status, new_status, notes) 
                        VALUES (?, ?, (SELECT status FROM maintenance_requests WHERE id = ?), ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("iisss", $request_id, $admin_id, $request_id, $new_status, $admin_notes);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Check if table exists, create if not
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
                    // Try logging again
                    $log_sql = "INSERT INTO maintenance_logs (request_id, updated_by, old_status, new_status, notes) 
                                VALUES (?, ?, (SELECT status FROM maintenance_requests WHERE id = ?), ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $log_stmt->bind_param("iisss", $request_id, $admin_id, $request_id, $new_status, $admin_notes);
                    $log_stmt->execute();
                }
            }
        }
    } else {
        $error = "Error updating maintenance request: " . $conn->error;
    }
}

// Handle assignment to maintenance staff
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_staff') {
    $request_id = (int)$_POST['request_id'];
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$block_filter = isset($_GET['block']) ? (int)$_GET['block'] : 0;
$priority_filter = isset($_GET['priority']) ? sanitize_input($_GET['priority']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build the query with filters
$sql = "SELECT mr.*, 
        s.first_name, s.last_name, s.admission_number,
        r.room_number, b.name as block_name,
        u.name as reported_by_name
        FROM maintenance_requests mr
        JOIN students s ON mr.reported_by = s.user_id
        JOIN rooms r ON mr.room_id = r.id
        JOIN blocks b ON mr.block_id = b.id
        JOIN users u ON mr.reported_by = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND mr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($block_filter)) {
    $sql .= " AND mr.block_id = ?";
    $params[] = $block_filter;
    $types .= "i";
}

if (!empty($priority_filter)) {
    $sql .= " AND mr.priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (mr.issue_type LIKE ? OR mr.description LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR r.room_number LIKE ? OR b.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssssss";
}

$sql .= " ORDER BY 
         CASE 
            WHEN mr.priority = 'high' THEN 1
            WHEN mr.priority = 'medium' THEN 2
            WHEN mr.priority = 'low' THEN 3
            ELSE 4
         END,
         CASE 
            WHEN mr.status = 'pending' THEN 1
            WHEN mr.status = 'in_progress' THEN 2
            WHEN mr.status = 'completed' THEN 3
            WHEN mr.status = 'rejected' THEN 4
            ELSE 5
         END,
         mr.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get all blocks for filter
$blocks_sql = "SELECT id, name FROM blocks ORDER BY name";
$blocks_result = $conn->query($blocks_sql);
$blocks = $blocks_result->fetch_all(MYSQLI_ASSOC);

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

<div class="container-fluid mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fas fa-tools"></i> Maintenance Management</h2>
            <p>Review and manage maintenance requests from students and block managers.</p>
            
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
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filter Requests</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo ($status_filter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="block" class="form-label">Block</label>
                            <select class="form-select" id="block" name="block">
                                <option value="">All Blocks</option>
                                <?php foreach ($blocks as $block): ?>
                                    <option value="<?php echo $block['id']; ?>" <?php echo ($block_filter == $block['id']) ? 'selected' : ''; ?>>
                                        <?php echo $block['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="">All Priorities</option>
                                <option value="high" <?php echo ($priority_filter == 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo ($priority_filter == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo ($priority_filter == 'low') ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Requests Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (count($maintenance_requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Issue Type</th>
                                        <th>Room</th>
                                        <th>Block</th>
                                        <th>Reported By</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenance_requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo $request['issue_type']; ?></td>
                                            <td><?php echo $request['room_number']; ?></td>
                                            <td><?php echo $request['block_name']; ?></td>
                                            <td>
                                                <?php echo $request['first_name'] . ' ' . $request['last_name']; ?>
                                                <small class="d-block text-muted"><?php echo $request['admission_number']; ?></small>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <?php echo $request['assigned_to_name'] ?? 'Not Assigned'; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewRequestModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-eye"></i> View Details
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-edit"></i> Update Status
                                                            </a>
                                                        </li>
                                                        <?php if (empty($request['assigned_to']) && $request['status'] != 'completed' && $request['status'] != 'rejected'): ?>
                                                        <li>
                                                            <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignStaffModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-user-plus"></i> Assign Staff
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- View Request Modal -->
                                        <div class="modal fade" id="viewRequestModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="viewRequestModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="viewRequestModalLabel<?php echo $request['id']; ?>">
                                                            Maintenance Request #<?php echo $request['id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Issue Details</h6>
                                                                <p><strong>Type:</strong> <?php echo $request['issue_type']; ?></p>
                                                                <p><strong>Description:</strong> <?php echo $request['description']; ?></p>
                                                                <p><strong>Priority:</strong> 
                                                                    <span class="badge <?php echo $priority_class; ?>">
                                                                        <?php echo ucfirst($request['priority']); ?>
                                                                    </span>
                                                                </p>
                                                                <p><strong>Status:</strong> 
                                                                    <span class="badge <?php echo $status_class; ?>">
                                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                                    </span>
                                                                </p>
                                                                <p><strong>Reported On:</strong> <?php echo date('F j, Y, g:i a', strtotime($request['created_at'])); ?></p>
                                                                <?php if (!empty($request['admin_notes'])): ?>
                                                                <p><strong>Admin Notes:</strong> <?php echo $request['admin_notes']; ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Location & Reporter</h6>
                                                                <p><strong>Block:</strong> <?php echo $request['block_name']; ?></p>
                                                                <p><strong>Room:</strong> <?php echo $request['room_number']; ?></p>
                                                                <p><strong>Reported By:</strong> <?php echo $request['first_name'] . ' ' . $request['last_name']; ?></p>
                                                                <p><strong>Admission Number:</strong> <?php echo $request['admission_number']; ?></p>
                                                                <p><strong>Assigned To:</strong> <?php echo $request['assigned_to_name'] ?? 'Not Assigned'; ?></p>
                                                                <?php if (!empty($request['image'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Attached Image</h6>
                                                                    <img src="/JESUS/uploads/maintenance/<?php echo $request['image']; ?>" class="img-fluid img-thumbnail" alt="Maintenance Issue">
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $request['id']; ?>" data-bs-dismiss="modal">
                                                            Update Status
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Update Status Modal -->
                                        <div class="modal fade" id="updateStatusModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="updateStatusModalLabel<?php echo $request['id']; ?>">
                                                            Update Request Status
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="status<?php echo $request['id']; ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?php echo $request['id']; ?>" name="status" required>
                                                                    <option value="pending" <?php echo ($request['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="in_progress" <?php echo ($request['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                                                    <option value="completed" <?php echo ($request['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                    <option value="rejected" <?php echo ($request['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_notes<?php echo $request['id']; ?>" class="form-label">Admin Notes</label>
                                                                <textarea class="form-control" id="admin_notes<?php echo $request['id']; ?>" name="admin_notes" rows="3"><?php echo $request['admin_notes']; ?></textarea>
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
                                        <div class="modal fade" id="assignStaffModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="assignStaffModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="assignStaffModalLabel<?php echo $request['id']; ?>">
                                                            Assign Maintenance Staff
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="assign_staff">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="staff_id<?php echo $request['id']; ?>" class="form-label">Select Maintenance Staff</label>
                                                                <select class="form-select" id="staff_id<?php echo $request['id']; ?>" name="staff_id" required>
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
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No maintenance requests found matching your criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="/JESUS/pages/admin/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
