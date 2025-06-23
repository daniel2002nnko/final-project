<?php
require_once '../config/database.php';

// Create mattress_assignment_logs table
$sql = "CREATE TABLE IF NOT EXISTS mattress_assignment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    mattress_number VARCHAR(20) NOT NULL,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table mattress_assignment_logs created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>