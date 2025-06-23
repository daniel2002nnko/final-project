<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Initialize variables
$success = '';
$error = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_id'])) {
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
                
                // Try to log the block manager assignment
                try {
                    // First check if the table exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'block_manager_logs'");
                    
                    if ($table_check->num_rows > 0) {
                        // Table exists, proceed with logging
                        $log_sql = "INSERT INTO block_manager_logs (student_id, assigned_by, action) 
                                    VALUES (?, ?, 'assigned')";
                        $log_stmt = $conn->prepare($log_sql);
                        $admin_id = $_SESSION['user_id'];
                        $log_stmt->bind_param("ii", $student_id, $admin_id);
                        $log_stmt->execute();
                    } else {
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
                        
                        if ($conn->query($create_table_sql) === TRUE) {
                            // Now insert the log
                            $log_sql = "INSERT INTO block_manager_logs (student_id, assigned_by, action) 
                                        VALUES (?, ?, 'assigned')";
                            $log_stmt = $conn->prepare($log_sql);
                            $admin_id = $_SESSION['user_id'];
                            $log_stmt->bind_param("ii", $student_id, $admin_id);
                            $log_stmt->execute();
                        }
                    }
                } catch (Exception $e) {
                    // Silently handle the error - the role is still assigned
                    // We could log this error to a file if needed
                }
                
                // Set session message for redirect
                $_SESSION['success_message'] = $success;
                redirect("/JESUS/pages/admin/dashboard.php");
            } else {
                $error = "Error assigning block manager role: " . $conn->error;
            }
        }
    }
    
    // If there was an error, set session message and redirect
    if (!empty($error)) {
        $_SESSION['error_message'] = $error;
        redirect("/JESUS/pages/admin/dashboard.php");
    }
} else {
    // If accessed directly without POST data, redirect to dashboard
    redirect("/JESUS/pages/admin/dashboard.php");
}
?>

