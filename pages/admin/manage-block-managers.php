<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

$success = '';
$error = '';

// Check if block_manager_logs table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'block_manager_logs'");
if ($table_check->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE IF NOT EXISTS block_manager_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        assigned_by INT NOT NULL,
        action ENUM('assigned', 'removed') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($create_table_sql);
}

// Handle block manager removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove') {
    $user_id = (int)$_POST['user_id'];
    $student_id = (int)$_POST['student_id'];
    
    // Update user role back to student
    $update_sql = "UPDATE users SET role = 'student' WHERE id = ? AND role = 'block_manager'";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    
    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        $success = "Block manager role has been removed successfully.";
        
        // Log the block manager removal
        try {
            $log_sql = "INSERT INTO block_manager_logs (student_id, assigned_by, action) 
                        VALUES (?, ?, 'removed')";
            $log_stmt = $conn->prepare($log_sql);
            $admin_id = $_SESSION['user_id'];
            $log_stmt->bind_param("ii", $student_id, $admin_id);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Silently handle the error
        }
    } else {
        $error = "Error removing block manager role or user is not a block manager.";
    }
}

// Get all current block managers
$sql = "SELECT s.id as student_id, u.id as user_id, u.name, u.email, s.admission_number, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN rooms r ON s.room_id = r.id 
        LEFT JOIN blocks b ON r.block_id = b.id 
        WHERE u.role = 'block_manager'
        ORDER BY b.name, r.room_number";
$result = $conn->query($sql);
$block_managers = $result->fetch_all(MYSQLI_ASSOC);

// Get block manager assignment history
$history = [];
try {
    // Check if the table exists before querying
    $table_check = $conn->query("SHOW TABLES LIKE 'block_manager_logs'");
    if ($table_check->num_rows > 0) {
        $history_sql = "SELECT bml.id, bml.student_id, bml.assigned_by, bml.action, bml.created_at,
                        s.admission_number, u1.name as student_name, u2.name as admin_name
                        FROM block_manager_logs bml
                        JOIN students s ON bml.student_id = s.id
                        JOIN users u1 ON s.user_id = u1.id
                        JOIN users u2 ON bml.assigned_by = u2.id
                        ORDER BY bml.created_at DESC
                        LIMIT 20";
        $history_result = $conn->query($history_sql);
        if ($history_result) {
            $history = $history_result->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    // Silently handle the error
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fas fa-user-shield"></i> Manage Block Managers</h2>
            <p>View and manage students with block manager privileges.</p>
            
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
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Block Managers</h5>
                </div>
                <div class="card-body">
                    <?php if (count($block_managers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Admission Number</th>
                                        <th>Room</th>
                                        <th>Block</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($block_managers as $manager): ?>
                                        <tr>
                                            <td><?php echo $manager['name']; ?></td>
                                            <td><?php echo $manager['email']; ?></td>
                                            <td><?php echo $manager['admission_number']; ?></td>
                                            <td><?php echo $manager['room_number'] ?? 'Not Assigned'; ?></td>
                                            <td><?php echo $manager['block_name'] ?? 'Not Assigned'; ?></td>
                                            <td>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="user_id" value="<?php echo $manager['user_id']; ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo $manager['student_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this block manager?')">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No block managers have been assigned yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($history)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Block Manager Assignment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Admission Number</th>
                                    <th>Action</th>
                                    <th>By Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo $log['student_name']; ?></td>
                                        <td><?php echo $log['admission_number']; ?></td>
                                        <td>
                                            <?php if ($log['action'] == 'assigned'): ?>
                                                <span class="badge bg-success">Assigned</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Removed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $log['admin_name']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="/JESUS/pages/admin/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="#" data-bs-toggle="modal" data-bs-target="#createBlockManagerModal" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Create New Block Manager
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Create Block Manager Modal -->
<div class="modal fade" id="createBlockManagerModal" tabindex="-1" aria-labelledby="createBlockManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createBlockManagerModalLabel"><i class="fas fa-user-plus"></i> Create Block Manager</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/JESUS/pages/admin/create-block-manager.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student to Assign as Block Manager</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Select a student --</option>
                            <?php
                            // Get eligible students for block managers
                            $bm_sql = "SELECT s.id, u.name, s.admission_number, r.room_number, b.name as block_name 
                                    FROM students s 
                                    JOIN users u ON s.user_id = u.id 
                                    LEFT JOIN rooms r ON s.room_id = r.id 
                                    LEFT JOIN blocks b ON r.block_id = b.id 
                                    WHERE u.role = 'student' AND s.room_id IS NOT NULL
                                    ORDER BY b.name, r.room_number
                                    LIMIT 20";
                            $bm_result = $conn->query($bm_sql);
                            $eligible_students = $bm_result->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($eligible_students as $student): 
                            ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['name']; ?> (<?php echo $student['admission_number']; ?>) - 
                                    Block: <?php echo $student['block_name'] ?? 'N/A'; ?>, Room: <?php echo $student['room_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only students with assigned rooms can be block managers.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Block Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>


