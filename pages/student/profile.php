<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !check_role('student')) {
    redirect("/JESUS/auth/login.php");
}

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.*, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $middle_name = sanitize_input($_POST['middle_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $class = sanitize_input($_POST['class']);
    $admission_number = sanitize_input($_POST['admission_number']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($class) || empty($admission_number) || empty($email)) {
        $error = "Please fill all required fields";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update student information
            $sql = "UPDATE students SET 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    class = ?, 
                    admission_number = ? 
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $first_name, $middle_name, $last_name, $class, $admission_number, $user_id);
            $stmt->execute();
            
            // Update user email and name
            $name = $first_name . ' ' . $middle_name . ' ' . $last_name;
            $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $name, $email, $user_id);
            $stmt->execute();
            
            // Update password if requested
            if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
                // Verify current password
                $sql = "SELECT password FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception("Current password is incorrect");
                }
                
                if ($new_password != $confirm_password) {
                    throw new Exception("New passwords do not match");
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception("Password must be at least 6 characters long");
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Update session variables
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            
            $success = "Profile updated successfully";
            
            // Refresh student data
            $sql = "SELECT s.*, u.email 
                    FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE s.user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Student Profile</h2>
            <p>Update your personal information below.</p>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $student['first_name']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo $student['middle_name']; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $student['last_name']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $student['email']; ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="class" class="form-label">Class</label>
                                <input type="text" class="form-control" id="class" name="class" value="<?php echo $student['class']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admission_number" class="form-label">Admission Number</label>
                                <input type="text" class="form-control" id="admission_number" name="admission_number" value="<?php echo $student['admission_number']; ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Password must be at least 6 characters long</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <!-- Hidden fields to maintain other form values -->
                        <input type="hidden" name="first_name" value="<?php echo $student['first_name']; ?>">
                        <input type="hidden" name="middle_name" value="<?php echo $student['middle_name']; ?>">
                        <input type="hidden" name="last_name" value="<?php echo $student['last_name']; ?>">
                        <input type="hidden" name="email" value="<?php echo $student['email']; ?>">
                        <input type="hidden" name="class" value="<?php echo $student['class']; ?>">
                        <input type="hidden" name="admission_number" value="<?php echo $student['admission_number']; ?>">
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Account Type:</strong> Student</p>
                    <p><strong>Account Created:</strong> <?php echo date('F j, Y', strtotime($student['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($student['updated_at'])); ?></p>
                    
                    <?php if ($student['room_id']): ?>
                        <div class="alert alert-info mt-3">
                            <p><i class="fas fa-info-circle"></i> You have been assigned to a room. Some information cannot be changed after room assignment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Need Help?</h5>
                </div>
                <div class="card-body">
                    <p>If you need assistance with your account, please contact the hostel administration:</p>
                    <p><i class="fas fa-phone"></i> +123-456-7890</p>
                    <p><i class="fas fa-envelope"></i> hostel@example.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>