<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !check_role('student')) {
    redirect("/JESUS/auth/login.php");
}

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

$success = '';
$error = '';

// Handle file uploads
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_type = sanitize_input($_POST['payment_type']);
    
    // Check if file was uploaded without errors
    if (isset($_FILES["payment_document"]) && $_FILES["payment_document"]["error"] == 0) {
        // Create uploads directory if it doesn't exist
        $target_dir = "../../assets/uploads/payments/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Debug information
        error_log("File upload started: " . $_FILES["payment_document"]["name"]);
        error_log("Upload directory: " . $target_dir);
        
        $file_name = upload_file($_FILES["payment_document"], $target_dir);
        
        if ($file_name) {
            error_log("File uploaded successfully: " . $file_name);
            
            // Update student record with document info
            if ($payment_type == 'college') {
                $sql = "UPDATE students SET college_fee_document = ? WHERE id = ?";
            } else {
                $sql = "UPDATE students SET hostel_fee_document = ? WHERE id = ?";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $file_name, $student['id']);
            
            if ($stmt->execute()) {
                $success = ucfirst($payment_type) . " fee document uploaded successfully. It will be verified by the warden.";
                error_log("Database updated successfully for " . $payment_type . " fee");
                
                // Refresh student data
                $sql = "SELECT * FROM students WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $student = $result->fetch_assoc();
            } else {
                $error = "Error updating record: " . $conn->error;
                error_log("Database update error: " . $conn->error);
            }
        } else {
            $error = "Error uploading file. Please ensure it's a JPG, PNG, or PDF file under 5MB.";
            error_log("File upload failed");
        }
    } else {
        $error = "Please select a file to upload. Error code: " . $_FILES["payment_document"]["error"];
        error_log("File upload error: " . $_FILES["payment_document"]["error"]);
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Payment Management</h2>
            <p>Upload your payment documents for verification.</p>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">College Fee</h5>
                </div>
                <div class="card-body">
                    <?php if ($student['college_fee_paid']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Your college fee payment has been verified.
                        </div>
                    <?php elseif ($student['college_fee_document']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> Your college fee document has been uploaded and is pending verification.
                        </div>
                        <p>Document: <a href="/JESUS/assets/uploads/payments/<?php echo $student['college_fee_document']; ?>" target="_blank">View Document</a></p>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="college_fee_document" class="form-label">Upload College Fee Receipt</label>
                                <input class="form-control" type="file" id="college_fee_document" name="payment_document" required>
                                <div class="form-text">Upload JPG, PNG or PDF file (max 5MB)</div>
                            </div>
                            <input type="hidden" name="payment_type" value="college">
                            <button type="submit" class="btn btn-primary">Upload Document</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Hostel Fee</h5>
                </div>
                <div class="card-body">
                    <?php if ($student['hostel_fee_paid']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Your hostel fee payment has been verified.
                        </div>
                    <?php elseif ($student['hostel_fee_document']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> Your hostel fee document has been uploaded and is pending verification.
                        </div>
                        <p>Document: <a href="/JESUS/assets/uploads/payments/<?php echo $student['hostel_fee_document']; ?>" target="_blank">View Document</a></p>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="hostel_fee_document" class="form-label">Upload Hostel Fee Receipt</label>
                                <input class="form-control" type="file" id="hostel_fee_document" name="payment_document" required>
                                <div class="form-text">Upload JPG, PNG or PDF file (max 5MB)</div>
                            </div>
                            <input type="hidden" name="payment_type" value="hostel">
                            <button type="submit" class="btn btn-primary">Upload Document</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Payment Instructions</h5>
                        <p>Please make payments to the following accounts:</p>
                        
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
                        
                        <p class="mt-3">After making the payment, please upload the payment receipt for verification. Room allocation will be done after both payments are verified.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>




