<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a warden
if (!is_logged_in() || !check_role('warden')) {
    redirect("/JESUS/auth/login.php");
}

// Get counts for dashboard stats
$counts = array();

// Total students
$sql = "SELECT COUNT(*) as count FROM students";
$result = $conn->query($sql);
$counts['total_students'] = $result->fetch_assoc()['count'];

// Total block managers
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'block_manager'";
$result = $conn->query($sql);
$counts['block_managers'] = $result->fetch_assoc()['count'];

// Total maintenance requests
$sql = "SELECT COUNT(*) as count FROM maintenance_requests";
$result = $conn->query($sql);
$counts['maintenance_requests'] = $result->fetch_assoc()['count'];

// Get eligible students for block managers
$sql = "SELECT s.id, u.name, s.admission_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN rooms r ON s.room_id = r.id 
        LEFT JOIN blocks b ON r.block_id = b.id 
        WHERE u.role = 'student' AND s.room_id IS NOT NULL
        ORDER BY b.name
        LIMIT 10";
$result = $conn->query($sql);
$eligible_students = $result->fetch_all(MYSQLI_ASSOC);

// Handle block manager creation
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_block_manager') {
    $student_id = (int)$_POST['student_id'];
    
    // Check if student exists and is not already a block manager
    $check_sql = "SELECT s.id, s.user_id, u.name, u.email, u.role 
                  FROM students s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE s.id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        $error = "Student not found.";
    } else {
        $student = $check_result->fetch_assoc();
        
        if ($student['role'] == 'block_manager') {
            $error = "This student is already a block manager.";
        } else {
            // Update user role to block_manager
            $update_sql = "UPDATE users SET role = 'block_manager' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $student['user_id']);
            
            if ($update_stmt->execute()) {
                $success = "Block manager role has been assigned to " . $student['name'] . " successfully.";
                
                // Log the block manager assignment
                $log_sql = "INSERT INTO block_manager_logs (student_id, assigned_by, action) 
                            VALUES (?, ?, 'assigned')";
                $log_stmt = $conn->prepare($log_sql);
                $warden_id = $_SESSION['user_id'];
                $log_stmt->bind_param("ii", $student_id, $warden_id);
                $log_stmt->execute();
            } else {
                $error = "Error assigning block manager role: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Warden Dashboard</h2>
            <p class="lead">Welcome back, <?php echo $_SESSION['name']; ?>!</p>
            
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
    
    <!-- Stats Cards -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Students</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['total_students']; ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="/JESUS/pages/admin/students.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Create Block Manager</h5>
                            <p class="mt-2 mb-0">Assign students as block managers</p>
                        </div>
                        <i class="fas fa-user-plus fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#createBlockManagerModal" class="text-white stretched-link">Create Now</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Maintenance Requests</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['maintenance_requests']; ?></h2>
                        </div>
                        <i class="fas fa-tools fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="/JESUS/pages/admin/maintenance.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Block Manager Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Create Block Manager</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="create_block_manager">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Select Student to Assign as Block Manager</label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">-- Select a student --</option>
                                        <?php foreach ($eligible_students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>">
                                                <?php echo $student['name']; ?> (<?php echo $student['admission_number']; ?>) - 
                                                Block: <?php echo $student['block_name'] ?? 'N/A'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Only students with assigned rooms can be block managers.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid gap-2 h-100">
                                    <button type="submit" class="btn btn-success btn-lg mt-4">
                                        <i class="fas fa-user-plus"></i> Create Block Manager
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="/JESUS/pages/admin/students.php" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <span>Manage Students</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/JESUS/pages/warden/manage-block-managers.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-user-shield fa-3x mb-3"></i>
                                <span>Manage Block Managers</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/JESUS/pages/admin/maintenance.php" class="btn btn-outline-warning btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-tools fa-3x mb-3"></i>
                                <span>Maintenance Requests</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/JESUS/pages/admin/assign-mattress.php" class="btn btn-outline-info btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-bed fa-3x mb-3"></i>
                                <span>Assign Mattresses</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Create Block Manager Modal -->
<div class="modal fade" id="createBlockManagerModal" tabindex="-1" aria-labelledby="createBlockManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createBlockManagerModalLabel"><i class="fas fa-user-plus"></i> Create Block Manager</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="action" value="create_block_manager">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student to Assign as Block Manager</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Select a student --</option>
                            <?php foreach ($eligible_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['name']; ?> (<?php echo $student['admission_number']; ?>) - 
                                    Block: <?php echo $student['block_name'] ?? 'N/A'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only students with assigned rooms can be block managers.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Create Block Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>


