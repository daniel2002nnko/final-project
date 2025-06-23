<?php
require_once '../config/database.php';

// Add room_assigned_date column to students table
$sql = "ALTER TABLE students ADD COLUMN room_assigned_date TIMESTAMP NULL DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column room_assigned_date added to students table successfully";
    
    // Update existing records to set room_assigned_date to created_at for students with rooms
    $update_sql = "UPDATE students SET room_assigned_date = created_at WHERE room_id IS NOT NULL";
    if ($conn->query($update_sql) === TRUE) {
        echo "<br>Existing room assignments updated with dates";
    } else {
        echo "<br>Error updating existing records: " . $conn->error;
    }
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>