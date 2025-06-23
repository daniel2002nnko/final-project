<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a warden
if (!is_logged_in() || !check_role('warden')) {
    redirect("/JESUS/auth/login.php");
}

$success = '';
$error = '';

// Handle block manager creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create') {
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
                try {
                    $log_sql = "INSERT INTO block_manager_logs (student_id, assigned_by, action) 
                                VALUES (?, ?, 'assigned')";
                    $log_stmt = $conn->prepare($log_sql);
                    $warden_id = $_SESSION['user_id'];
                    $log_stmt->bind_param("ii", $student_id, $warden_id);
                    $log_stmt->execute();
                } catch (Exception $e) {
                    // Silently handle the error
                }
            } else {
                $error = "Error assigning block manager role: " . $conn->error;
            }
        }
    }
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
            $warden_id = $_SESSION['user_id'];
            $log_stmt->bind_param("ii", $student_id, $warden_id);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Silently handle the error
        }
    } else {
        $error = "Error removing block manager role or user is not a block manager.";
    }
}

// Get all students who are not block managers
$sql = "SELECT s.id, u.name, u.email, s.admission_number, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN rooms r ON s.room_id = r.id 
        LEFT JOIN blocks b ON r.block_id = b.id 
        WHERE u.role = 'student' AND s.room_id IS NOT NULL
        ORDER BY b.name, r.room_number";
$result = $conn->query($sql);
$students = $result->fetch_all(MYSQLI_ASSOC);

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
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/warden/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Block Managers</li>
                </ol>
            </nav>
        </div>
    </div>
    
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
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Assign New Block Manager</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Select Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">-- Select a student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo $student['name']; ?> (<?php echo $student['admission_number']; ?>) - 
                                        Block: <?php echo $student['block_name'] ?? 'N/A'; ?>, 
                                        Room: <?php echo $student['room_number'] ?? 'N/A'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only students with assigned rooms can be block managers.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Assign as Block Manager
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>