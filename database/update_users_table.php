<?php
require_once '../config/database.php';

// Update users table to include warden role
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'student', 'warden', 'block_manager') NOT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Users table updated successfully to include warden role";
} else {
    echo "Error updating users table: " . $conn->error;
}

$conn->close();
?>