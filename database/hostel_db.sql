-- Create database
CREATE DATABASE IF NOT EXISTS hostel_db;
USE hostel_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'block_manager') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blocks table
CREATE TABLE IF NOT EXISTS blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    block_id INT NOT NULL,
    capacity INT DEFAULT 1,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    class VARCHAR(50) NOT NULL,
    admission_number VARCHAR(50) NOT NULL UNIQUE,
    room_id INT,
    mattress_number VARCHAR(20),
    college_fee_paid BOOLEAN DEFAULT FALSE,
    hostel_fee_paid BOOLEAN DEFAULT FALSE,
    college_fee_document VARCHAR(255),
    hostel_fee_document VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    net BOOLEAN DEFAULT FALSE,
    bucket BOOLEAN DEFAULT FALSE,
    broom BOOLEAN DEFAULT FALSE,
    toilet_bowl BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Maintenance requests table
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_id INT NOT NULL,
    room_id INT,
    reported_by INT NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payment logs table
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    payment_type ENUM('college', 'hostel') NOT NULL,
    verified_by INT NOT NULL,
    action ENUM('verified', 'rejected') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default blocks
INSERT INTO blocks (name, description) VALUES 
('Block A', 'First year students block'),
('Block B', 'Second year students block'),
('Block C', 'Third year students block'),
('Block D', 'Final year students block');

-- Generate 50 rooms for each block
DELIMITER //
CREATE PROCEDURE generate_rooms()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE j INT DEFAULT 1;
    DECLARE block_id INT;
    DECLARE room_num VARCHAR(10);
    
    WHILE j <= 4 DO
        SET block_id = j;
        SET i = 1;
        
        WHILE i <= 50 DO
            SET room_num = CONCAT(CHAR(64 + j), '-', LPAD(i, 3, '0'));
            INSERT INTO rooms (room_number, block_id, status) VALUES (room_num, block_id, 'available');
            SET i = i + 1;
        END WHILE;
        
        SET j = j + 1;
    END WHILE;
END //
DELIMITER ;

CALL generate_rooms();
DROP PROCEDURE generate_rooms;

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@hostel.com', '$2y$10$8MNXAOYLg8uFV1wVAgXG5OQl9KpNJzHRzpMq1aRHs7Kcr5.OTgUOe', 'admin');

