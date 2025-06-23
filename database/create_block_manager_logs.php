<?php
require_once '../config/database.php';

// Create block_manager_logs table
$sql = "CREATE TABLE IF NOT EXISTS block_manager_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    assigned_by INT NOT NULL,
    action ENUM('assigned', 'removed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table block_manager_logs created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>

