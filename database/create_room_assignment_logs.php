<?php
require_once '../config/database.php';

// Create room_assignment_logs table
$sql = "CREATE TABLE IF NOT EXISTS room_assignment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assignment_type ENUM('manual', 'automatic') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table room_assignment_logs created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>