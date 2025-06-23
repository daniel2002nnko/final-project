<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin or warden
if (!is_logged_in() || (!check_role('admin') && !check_role('warden'))) {
    redirect("/JESUS/auth/login.php");
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/admin/students.php");
}

$student_id = (int)$_GET['id'];

// Get student information
$sql = "SELECT s.*, u.email, u.name, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN blocks b ON r.block_id = b.id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect("/JESUS/pages/admin/students.php");
}

$student = $result->fetch_assoc();

// Get equipment status
$sql = "SELECT * FROM equipment WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$equipment = $result->fetch_assoc();

// If equipment record doesn't exist, create one
if (!$equipment) {
    $sql = "INSERT INTO equipment (student_id) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    
    // Get the newly created equipment record
    $sql = "SELECT * FROM equipment WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();
}

$success = '';
$error = '';

// Handle equipment verification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get equipment items from form
    $net = isset($_POST['net']) ? 1 : 0;
    $bucket = isset($_POST['bucket']) ? 1 : 0;
    $broom = isset($_POST['broom']) ? 1 : 0;
    $toilet_bowl = isset($_POST['toilet_bowl']) ? 1 : 0;
    
    // Update equipment record
    $sql = "UPDATE equipment SET 
            net = ?, 
            bucket = ?, 
            broom = ?, 
            toilet_bowl = ?, 
            verified_by = ?, 
            verified_at = NOW() 
            WHERE student_id = ?";
    
    $stmt = $conn->prepare($sql);
    $admin_id = $_SESSION['user_id'];
    $stmt->bind_param("iiiiii", $net, $bucket, $broom, $toilet_bowl, $admin_id, $student_id);
    
    if ($stmt->execute()) {
        $success = "Equipment verification has been updated successfully.";
        
        // Check if all equipment is verified and student has paid fees
        if ($net && $bucket && $broom && $toilet_bowl && 
            $student['college_fee_paid'] && $student['hostel_fee_paid'] && 
            !$student['room_id']) {
            
            // Determine appropriate block based on student's class
            $block_id = 1; // Default to Block A
            
            // Map student class to appropriate block
            if (stripos($student['class'], 'first') !== false || stripos($student['class'], '1') !== false) {
                $block_id = 1; // Block A for first year
            } elseif (stripos($student['class'], 'second') !== false || stripos($student['class'], '2') !== false) {
                $block_id = 2; // Block B for second year
            } elseif (stripos($student['class'], 'third') !== false || stripos($student['class'], '3') !== false) {
                $block_id = 3; // Block C for third year
            } elseif (stripos($student['class'], 'fourth') !== false || stripos($student['class'], 'final') !== false || 
                      stripos($student['class'], '4') !== false) {
                $block_id = 4; // Block D for fourth/final year
            }
            
            // Find an available room in the appropriate block
            $sql = "SELECT r.id, r.room_number, b.name as block_name 
                    FROM rooms r 
                    JOIN blocks b ON r.block_id = b.id
                    WHERE r.status = 'available' AND b.id = ?
                    ORDER BY r.room_number 
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $block_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // If no room available in preferred block, try any available room
            if ($result->num_rows == 0) {
                $sql = "SELECT r.id, r.room_number, b.name as block_name 
                        FROM rooms r 
                        JOIN blocks b ON r.block_id = b.id
                        WHERE r.status = 'available' 
                        ORDER BY b.id, r.room_number 
                        LIMIT 1";
                $result = $conn->query($sql);
            }
            
            if ($result && $result->num_rows > 0) {
                $room = $result->fetch_assoc();
                $room_id = $room['id'];
                
                // Assign room to student
                $sql = "UPDATE students SET room_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $room_id, $student_id);
                
                if ($stmt->execute()) {
                    // Update room status
                    $sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $room_id);
                    $stmt->execute();
                    
                    $success .= " Room {$room['room_number']} in {$room['block_name']} has been automatically assigned to the student.";
                    
                    // Update student info with room details
                    $student['room_id'] = $room_id;
                    $student['room_number'] = $room['room_number'];
                    $student['block_name'] = $room['block_name'];
                    
                    // Log room assignment
                    try {
                        $log_sql = "INSERT INTO room_assignment_logs (student_id, room_id, assigned_by, assignment_type) 
                                    VALUES (?, ?, ?, 'automatic')";
                        $log_stmt = $conn->prepare($log_sql);
                        $log_stmt->bind_param("iii", $student_id, $room_id, $admin_id);
                        $log_stmt->execute();
                    } catch (Exception $e) {
                        // Silently handle the error - the room is still assigned
                    }
                } else {
                    $error = "Error assigning room: " . $conn->error;
                }
            } else {
                $error = "No available rooms found for automatic assignment.";
            }
        }
        
        // Refresh equipment data
        $sql = "SELECT * FROM equipment WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipment = $result->fetch_assoc();
    } else {
        $error = "Error updating equipment verification: " . $conn->error;
    }
}

// Check for students with verified fees and equipment but no room
$sql = "SELECT s.id, s.class FROM students s 
        JOIN equipment e ON s.id = e.student_id 
        WHERE s.college_fee_paid = 1 
        AND s.hostel_fee_paid = 1 
        AND e.net = 1 
        AND e.bucket = 1 
        AND e.broom = 1 
        AND e.toilet_bowl = 1 
        AND s.room_id IS NULL";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Process each student who needs a room
    while ($row = $result->fetch_assoc()) {
        $student_to_assign = $row['id'];
        $student_class = $row['class'];
        
        // Determine appropriate block based on student's class
        $block_id = 1; // Default to Block A
        
        // Map student class to appropriate block
        if (stripos($student_class, 'first') !== false || stripos($student_class, '1') !== false) {
            $block_id = 1; // Block A for first year
        } elseif (stripos($student_class, 'second') !== false || stripos($student_class, '2') !== false) {
            $block_id = 2; // Block B for second year
        } elseif (stripos($student_class, 'third') !== false || stripos($student_class, '3') !== false) {
            $block_id = 3; // Block C for third year
        } elseif (stripos($student_class, 'fourth') !== false || stripos($student_class, 'final') !== false || 
                  stripos($student_class, '4') !== false) {
            $block_id = 4; // Block D for fourth/final year
        }
        
        // Find an available room in the appropriate block
        $sql = "SELECT r.id, r.room_number, b.name as block_name 
                FROM rooms r 
                JOIN blocks b ON r.block_id = b.id
                WHERE r.status = 'available' AND b.id = ?
                ORDER BY r.room_number 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $block_id);
        $stmt->execute();
        $room_result = $stmt->get_result();
        
        // If no room available in preferred block, try any available room
        if ($room_result->num_rows == 0) {
            $sql = "SELECT r.id, r.room_number, b.name as block_name 
                    FROM rooms r 
                    JOIN blocks b ON r.block_id = b.id
                    WHERE r.status = 'available' 
                    ORDER BY b.id, r.room_number 
                    LIMIT 1";
            $room_result = $conn->query($sql);
        }
        
        if ($room_result && $room_result->num_rows > 0) {
            $room = $room_result->fetch_assoc();
            $room_id = $room['id'];
            
            // Assign room to student
            $sql = "UPDATE students SET room_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $room_id, $student_to_assign);
            
            if ($stmt->execute()) {
                // Update room status
                $sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                
                // Log room assignment
                $admin_id = $_SESSION['user_id'];
                try {
                    $log_sql = "INSERT INTO room_assignment_logs (student_id, room_id, assigned_by, assignment_type) 
                                VALUES (?, ?, ?, 'automatic')";
                    $log_stmt = $conn->prepare($log_sql);
                    $log_stmt->bind_param("iii", $student_to_assign, $room_id, $admin_id);
                    $log_stmt->execute();
                } catch (Exception $e) {
                    // Silently handle the error - the room is still assigned
                }
                
                // If this is the current student being viewed, update their info
                if ($student_to_assign == $student_id) {
                    $success .= " Room {$room['room_number']} in {$room['block_name']} has been automatically assigned to the student.";
                    
                    // Update student info with room details
                    $student['room_id'] = $room_id;
                    $student['room_number'] = $room['room_number'];
                    $student['block_name'] = $room['block_name'];
                }
            }
        }
    }
}

// Get block occupancy statistics
$sql = "SELECT b.id, b.name, 
        COUNT(r.id) as total_rooms, 
        SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
        SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms
        FROM blocks b
        LEFT JOIN rooms r ON b.id = r.block_id
        GROUP BY b.id
        ORDER BY b.id";
$result = $conn->query($sql);
$blocks = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Verify Student Equipment</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <p><strong>Name:</strong> <?php echo $student['name']; ?></p>
                            <p><strong>Admission Number:</strong> <?php echo $student['admission_number']; ?></p>
                            <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                            <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
                            <p><strong>Payment Status:</strong> 
                                <?php if ($student['college_fee_paid'] && $student['hostel_fee_paid']): ?>
                                    <span class="badge bg-success">All Fees Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Fees Pending</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Room Information</h6>
                            <?php if ($student['room_id']): ?>
                                <p><strong>Block:</strong> <?php echo $student['block_name']; ?></p>
                                <p><strong>Room Number:</strong> <?php echo $student['room_number']; ?></p>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Room already assigned
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Room will be automatically assigned when all equipment is verified and fees are paid.
                                </div>
                                <p><small>Note: Rooms are assigned based on student's class year when possible.</small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $student_id); ?>">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Equipment Verification</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="net" id="net" <?php echo ($equipment['net']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="net">
                                                Mosquito Net
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="bucket" id="bucket" <?php echo ($equipment['bucket']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="bucket">
                                                Bucket
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="broom" id="broom" <?php echo ($equipment['broom']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="broom">
                                                Broom
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="toilet_bowl" id="toilet_bowl" <?php echo ($equipment['toilet_bowl']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="toilet_bowl">
                                                Toilet Bowl
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($equipment['verified_by']): ?>
                                <div class="alert alert-info mt-3">
                                    <small>Last verified on: <?php echo date('F j, Y, g:i a', strtotime($equipment['verified_at'])); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="student-details.php?id=<?php echo $student_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Student Details
                            </a>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Save Verification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Block Occupancy</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($blocks as $block): ?>
                        <div class="mb-3">
                            <h6><?php echo $block['name']; ?></h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Occupied: <?php echo $block['occupied_rooms']; ?>/<?php echo $block['total_rooms']; ?></span>
                                <span>Available: <?php echo $block['available_rooms']; ?></span>
                            </div>
                            <div class="progress">
                                <?php 
                                    $percentage = ($block['total_rooms'] > 0) ? 
                                        ($block['occupied_rooms'] / $block['total_rooms']) * 100 : 0;
                                ?>
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                     aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <ul>
                                <li>Block A: First year students</li>
                                <li>Block B: Second year students</li>
                                <li>Block C: Third year students</li>
                                <li>Block D: Final year students</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>



















