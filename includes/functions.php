<?php
// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

// Function to check if user has a specific role
function check_role($role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if ($role == 'admin' && $_SESSION['role'] == 'admin') {
        return true;
    }
    
    if ($role == 'student' && $_SESSION['role'] == 'student') {
        return true;
    }
    
    if ($role == 'warden' && $_SESSION['role'] == 'warden') {
        return true;
    }
    
    if ($role == 'block_manager' && $_SESSION['role'] == 'block_manager') {
        return true;
    }
    
    return false;
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Function to upload file
function upload_file($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_type;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is an actual image or PDF
    if ($file_type == "pdf") {
        // For PDF files, we can't use getimagesize
        if ($file["type"] != "application/pdf") {
            error_log("File is not a valid PDF");
            return false;
        }
    } else {
        // For images, check with getimagesize
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            error_log("File is not a valid image");
            return false;
        }
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        error_log("File is too large: " . $file["size"] . " bytes");
        return false;
    }
    
    // Allow certain file formats
    if ($file_type != "jpg" && $file_type != "png" && $file_type != "jpeg" && $file_type != "pdf") {
        error_log("Invalid file type: " . $file_type);
        return false;
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            error_log("Failed to create directory: " . $target_dir);
            return false;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($target_dir)) {
        error_log("Directory is not writable: " . $target_dir);
        chmod($target_dir, 0777);
    }
    
    // Try to upload the file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        error_log("File uploaded successfully to: " . $target_file);
        return $new_filename;
    } else {
        error_log("Failed to move uploaded file. PHP error: " . error_get_last()['message']);
        return false;
    }
}
?>




