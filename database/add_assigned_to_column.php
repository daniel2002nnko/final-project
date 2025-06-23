<?php
require_once '../config/database.php';

// Add assigned_to, updated_by, and priority columns to maintenance_requests table
$sql = "ALTER TABLE maintenance_requests 
        ADD COLUMN assigned_to INT NULL DEFAULT NULL,
        ADD COLUMN updated_by INT NULL DEFAULT NULL,
        ADD COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        ADD COLUMN admin_notes TEXT NULL DEFAULT NULL,
        ADD FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        ADD FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL";

if ($conn->query($sql) === TRUE) {
    echo "Columns added to maintenance_requests table successfully";
} else {
    echo "Error adding columns: " . $conn->error;
}

$conn->close();
?>