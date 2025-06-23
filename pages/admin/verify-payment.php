<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/admin/verify-payments.php");
}

$student_id = (int)$_GET['id'];

// Get student information
$sql = "SELECT s.*, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect("/JESUS/pages/admin/verify-payments.php");
}

$student = $result->fetch_assoc();

$success = '';
$error = '';

// Handle payment verification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_type = sanitize_input($_POST['payment_type']);
    $action = sanitize_input($_POST['action']);
    
    if ($payment_type == 'college' || $payment_type == 'hostel') {
        $column = $payment_type . '_fee_paid';
        $document_column = $payment_type . '_fee_document';
        
        if ($action == 'verify') {
            // Verify the payment
            $sql = "UPDATE students SET $column = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                $success = ucfirst($payment_type) . " fee payment has been verified successfully.";
                
                // Log the verification
                $admin_id = $_SESSION['user_id'];
                $log_sql = "INSERT INTO payment_logs (student_id, payment_type, verified_by, action) VALUES (?, ?, ?, 'verified')";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("isi", $student_id, $payment_type, $admin_id);
                $log_stmt->execute();
                
                // Check if all fees are paid and equipment is verified, then assign room if needed
                $check_sql = "SELECT s.*, e.net, e.bucket, e.broom, e.toilet_bowl 
                              FROM students s 
                              JOIN equipment e ON s.id = e.student_id 
                              WHERE s.id = ? AND s.college_fee_paid = 1 AND s.hostel_fee_paid = 1 
                              AND s.room_id IS NULL";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $student_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $student_data = $check_result->fetch_assoc();
                    
                    // Check if all equipment is verified
                    if ($student_data['net'] && $student_data['bucket'] && $student_data['broom'] && $student_data['toilet_bowl']) {
                        // Determine appropriate block based on student's class
                        $block_id = 1; // Default to Block A
                        
                        // Map student class to appropriate block
                        if (stripos($student_data['class'], 'first') !== false || stripos($student_data['class'], '1') !== false) {
                            $block_id = 1; // Block A for first year
                        } elseif (stripos($student_data['class'], 'second') !== false || stripos($student_data['class'], '2') !== false) {
                            $block_id = 2; // Block B for second year
                        } elseif (stripos($student_data['class'], 'third') !== false || stripos($student_data['class'], '3') !== false) {
                            $block_id = 3; // Block C for third year
                        } elseif (stripos($student_data['class'], 'fourth') !== false || stripos($student_data['class'], 'final') !== false || 
                                  stripos($student_data['class'], '4') !== false) {
                            $block_id = 4; // Block D for fourth/final year
                        }
                        
                        // Find an available room in the appropriate block
                        $room_sql = "SELECT r.id, r.room_number, b.name as block_name 
                                    FROM rooms r 
                                    JOIN blocks b ON r.block_id = b.id
                                    WHERE r.status = 'available' AND b.id = ?
                                    ORDER BY r.room_number 
                                    LIMIT 1";
                        $room_stmt = $conn->prepare($room_sql);
                        $room_stmt->bind_param("i", $block_id);
                        $room_stmt->execute();
                        $room_result = $room_stmt->get_result();
                        
                        // If no room available in preferred block, try any available room
                        if ($room_result->num_rows == 0) {
                            $room_sql = "SELECT r.id, r.room_number, b.name as block_name 
                                        FROM rooms r 
                                        JOIN blocks b ON r.block_id = b.id
                                        WHERE r.status = 'available' 
                                        ORDER BY b.id, r.room_number 
                                        LIMIT 1";
                            $room_result = $conn->query($room_sql);
                        }
                        
                        if ($room_result && $room_result->num_rows > 0) {
                            $room = $room_result->fetch_assoc();
                            $room_id = $room['id'];
                            
                            // Assign room to student
                            $assign_sql = "UPDATE students SET room_id = ? WHERE id = ?";
                            $assign_stmt = $conn->prepare($assign_sql);
                            $assign_stmt->bind_param("ii", $room_id, $student_id);
                            
                            if ($assign_stmt->execute()) {
                                // Update room status
                                $update_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                                $update_room_stmt = $conn->prepare($update_room_sql);
                                $update_room_stmt->bind_param("i", $room_id);
                                $update_room_stmt->execute();
                                
                                $success .= " Room {$room['room_number']} in {$room['block_name']} has been automatically assigned to the student.";
                                
                                // Log room assignment
                                $log_sql = "INSERT INTO room_assignment_logs (student_id, room_id, assigned_by, assignment_type) 
                                            VALUES (?, ?, ?, 'automatic')";
                                $log_stmt = $conn->prepare($log_sql);
                                $log_stmt->bind_param("iii", $student_id, $room_id, $admin_id);
                                $log_stmt->execute();
                            }
                        }
                    }
                }
                
                // No need to execute the update query again, just refresh student data
            } else {
                $error = "Error verifying payment: " . $conn->error;
            }
        } elseif ($action == 'reject') {
            // Get the document filename
            $document = $student[$document_column];
            
            // Delete the document file if it exists
            if ($document && file_exists("../../assets/uploads/payments/" . $document)) {
                unlink("../../assets/uploads/payments/" . $document);
            }
            
            // Update the database
            $sql = "UPDATE students SET $document_column = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                $success = ucfirst($payment_type) . " fee document has been rejected. The student will need to upload a new document.";
                
                // Log the rejection
                $admin_id = $_SESSION['user_id'];
                $log_sql = "INSERT INTO payment_logs (student_id, payment_type, verified_by, action) VALUES (?, ?, ?, 'rejected')";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("isi", $student_id, $payment_type, $admin_id);
                $log_stmt->execute();
                
                // No need to execute the update query again, just refresh student data
            } else {
                $error = "Error rejecting document: " . $conn->error;
            }
        }
        
        // Refresh student data
        $sql = "SELECT s.*, u.email 
                FROM students s 
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
    } else {
        $error = "Invalid payment type.";
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/verify-payments.php">Verify Payments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Verify Student Payment</li>
                </ol>
            </nav>
            
            <h2>Verify Student Payment</h2>
            <p>Review and verify payment documents for <?php echo $student['first_name'] . ' ' . $student['last_name']; ?></p>
            
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
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Full Name:</strong> <?php echo $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                    <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
                    <p><strong>Admission Number:</strong> <?php echo $student['admission_number']; ?></p>
                    <p><strong>Registered On:</strong> <?php echo date('F j, Y', strtotime($student['created_at'])); ?></p>
                    
                    <a href="student-details.php?id=<?php echo $student['id']; ?>" class="btn btn-primary mt-2">
                        <i class="fas fa-user"></i> View Full Profile
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="row">
                <!-- College Fee Section -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">College Fee</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($student['college_fee_paid']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> College fee payment has been verified.
                                </div>
                            <?php elseif ($student['college_fee_document']): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock"></i> College fee document is pending verification.
                                </div>
                                
                                <div class="mb-3">
                                    <h6>Payment Document</h6>
                                    <div class="document-preview mb-3">
                                        <?php 
                                            $file_ext = pathinfo($student['college_fee_document'], PATHINFO_EXTENSION);
                                            if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png'])):
                                        ?>
                                            <img src="/JESUS/assets/uploads/payments/<?php echo $student['college_fee_document']; ?>" class="img-fluid border" alt="College Fee Document">
                                        <?php else: ?>
                                            <div class="text-center p-4 border">
                                                <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                                <p class="mt-2">PDF Document</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="/JESUS/assets/uploads/payments/<?php echo $student['college_fee_document']; ?>" target="_blank" class="btn btn-info mb-3">
                                        <i class="fas fa-external-link-alt"></i> Open Document in New Tab
                                    </a>
                                    
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $student_id); ?>" method="post">
                                        <input type="hidden" name="payment_type" value="college">
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action" value="verify" class="btn btn-success">
                                                <i class="fas fa-check-circle"></i> Verify Payment
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this document? This will delete the uploaded file and the student will need to upload a new one.')">
                                                <i class="fas fa-times-circle"></i> Reject Document
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> No college fee document has been uploaded yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Hostel Fee Section -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Hostel Fee</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($student['hostel_fee_paid']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Hostel fee payment has been verified.
                                </div>
                            <?php elseif ($student['hostel_fee_document']): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock"></i> Hostel fee document is pending verification.
                                </div>
                                
                                <div class="mb-3">
                                    <h6>Payment Document</h6>
                                    <div class="document-preview mb-3">
                                        <?php 
                                            $file_ext = pathinfo($student['hostel_fee_document'], PATHINFO_EXTENSION);
                                            if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png'])):
                                        ?>
                                            <img src="/JESUS/assets/uploads/payments/<?php echo $student['hostel_fee_document']; ?>" class="img-fluid border" alt="Hostel Fee Document">
                                        <?php else: ?>
                                            <div class="text-center p-4 border">
                                                <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                                <p class="mt-2">PDF Document</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="/JESUS/assets/uploads/payments/<?php echo $student['hostel_fee_document']; ?>" target="_blank" class="btn btn-info mb-3">
                                        <i class="fas fa-external-link-alt"></i> Open Document in New Tab
                                    </a>
                                    
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $student_id); ?>" method="post">
                                        <input type="hidden" name="payment_type" value="hostel">
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action" value="verify" class="btn btn-success">
                                                <i class="fas fa-check-circle"></i> Verify Payment
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this document? This will delete the uploaded file and the student will need to upload a new one.')">
                                                <i class="fas fa-times-circle"></i> Reject Document
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> No hostel fee document has been uploaded yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Payment Instructions</h5>
                        <p>Please verify that the payment documents match the following account details:</p>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>College Fee</h6>
                                <p>Bank: National Bank<br>
                                Account Name: College Account<br>
                                Account Number: 1234567890<br>
                                Branch: Main Campus</p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Hostel Fee</h6>
                                <p>Bank: National Bank<br>
                                Account Name: Hostel Account<br>
                                Account Number: 0987654321<br>
                                Branch: Main Campus</p>
                            </div>
                        </div>
                        
                        <p class="mt-3">Ensure that the payment receipt shows the correct amount and account details before verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

