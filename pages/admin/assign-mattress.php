<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin or warden
if (!is_logged_in() || (!check_role('admin') && !check_role('warden'))) {
    redirect("/JESUS/auth/login.php");
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/admin/students.php");
}

$student_id = (int)$_GET['id'];

// Get student information
$sql = "SELECT s.*, u.email, u.name, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN blocks b ON r.block_id = b.id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect("/JESUS/pages/admin/students.php");
}

$student = $result->fetch_assoc();

$success = '';
$error = '';

// Handle mattress assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mattress_number = trim($_POST['mattress_number']);
    
    // Validate mattress number
    if (empty($mattress_number)) {
        $error = "Mattress number cannot be empty.";
    } else {
        // Check if mattress number is already assigned to another student
        $check_sql = "SELECT id FROM students WHERE mattress_number = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $mattress_number, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "This mattress number is already assigned to another student.";
        } else {
            // Update mattress number
            $sql = "UPDATE students SET mattress_number = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $mattress_number, $student_id);
            
            if ($stmt->execute()) {
                $success = "Mattress number has been assigned successfully.";
                
                // Update student info with new mattress number
                $student['mattress_number'] = $mattress_number;
                
                // Log mattress assignment
                try {
                    $log_sql = "INSERT INTO mattress_assignment_logs (student_id, mattress_number, assigned_by) 
                                VALUES (?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $admin_id = $_SESSION['user_id'];
                    $log_stmt->bind_param("isi", $student_id, $mattress_number, $admin_id);
                    $log_stmt->execute();
                } catch (Exception $e) {
                    // Silently handle the error - the mattress is still assigned
                }
            } else {
                $error = "Error assigning mattress number: " . $conn->error;
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/students.php">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Assign Mattress</li>
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
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Assign Mattress Number</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <p><strong>Name:</strong> <?php echo $student['name']; ?></p>
                            <p><strong>Admission Number:</strong> <?php echo $student['admission_number']; ?></p>
                            <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                            <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Room Information</h6>
                            <?php if ($student['room_id']): ?>
                                <p><strong>Block:</strong> <?php echo $student['block_name']; ?></p>
                                <p><strong>Room Number:</strong> <?php echo $student['room_number']; ?></p>
                                <p><strong>Current Mattress Number:</strong> 
                                    <?php echo $student['mattress_number'] ? $student['mattress_number'] : 'Not assigned'; ?>
                                </p>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Student does not have a room assigned yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $student_id); ?>">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Mattress Assignment</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="mattress_number" class="form-label">Mattress Number</label>
                                    <input type="text" class="form-control" id="mattress_number" name="mattress_number" 
                                           value="<?php echo $student['mattress_number'] ?? ''; ?>" required>
                                    <div class="form-text">Enter a unique identifier for the mattress.</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Assign Mattress Number
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="/JESUS/pages/admin/student-details.php?id=<?php echo $student_id; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-user"></i> View Student Details
                        </a>
                        <a href="/JESUS/pages/admin/verify-payment.php?id=<?php echo $student_id; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-money-bill"></i> Verify Payments
                        </a>
                        <a href="/JESUS/pages/admin/verify-equipment.php?id=<?php echo $student_id; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-tools"></i> Verify Equipment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>