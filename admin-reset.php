<?php
// This is a temporary script to reset the admin password
// Delete this file after use for security reasons

require_once 'config/database.php';

// Create a new admin user or update existing one
$name = "Admin User";
$email = "admin@hostel.com";
$password = "admin123";
$role = "admin";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $user = $result->fetch_assoc();
    $sql = "UPDATE users SET name = ?, password = ?, role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $hashed_password, $role, $user['id']);
    
    if ($stmt->execute()) {
        echo "Admin user updated successfully.<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
        echo "Please delete this file after use.";
    } else {
        echo "Error updating admin user: " . $conn->error;
    }
} else {
    // Create new admin
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully.<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
        echo "Please delete this file after use.";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
}
?>