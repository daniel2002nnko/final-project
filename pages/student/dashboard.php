<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !check_role('student')) {
    redirect("/JESUS/auth/login.php");
}

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.*, r.room_number, b.name as block_name 
        FROM students s 
        LEFT JOIN rooms r ON s.room_id = r.id 
        LEFT JOIN blocks b ON r.block_id = b.id 
        WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Check if student record exists
if (!$student) {
    // Handle the case where student record doesn't exist
    $_SESSION['error'] = "Student record not found. Please contact administration.";
    redirect("/JESUS/auth/logout.php");
    exit;
}

// Get equipment status
$sql = "SELECT * FROM equipment WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$result = $stmt->get_result();
$equipment = $result->fetch_assoc();

// If equipment record doesn't exist, create one
if (!$equipment) {
    $sql = "INSERT INTO equipment (student_id) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    
    // Get the newly created equipment record
    $sql = "SELECT * FROM equipment WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Student Dashboard</h2>
            <p>Welcome, <?php echo $_SESSION['name']; ?>!</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $_SESSION['email']; ?></p>
                    <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
                    <p><strong>Admission Number:</strong> <?php echo $student['admission_number']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Room Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($student['room_id']): ?>
                        <p><strong>Room Number:</strong> <?php echo $student['room_number']; ?></p>
                        <p><strong>Block:</strong> <?php echo $student['block_name']; ?></p>
                        <p><strong>Mattress Number:</strong> <?php echo $student['mattress_number'] ? $student['mattress_number'] : 'Not assigned yet'; ?></p>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Room will be assigned after payment verification.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p><strong>College Fee:</strong> 
                            <?php if ($student['college_fee_paid']): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php elseif ($student['college_fee_document']): ?>
                                <span class="badge bg-warning">Pending Verification</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Not Uploaded</span>
                            <?php endif; ?>
                        </p>
                        
                        <p><strong>Hostel Fee:</strong> 
                            <?php if ($student['hostel_fee_paid']): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php elseif ($student['hostel_fee_document']): ?>
                                <span class="badge bg-warning">Pending Verification</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Not Uploaded</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if (!$student['college_fee_paid'] || !$student['hostel_fee_paid']): ?>
                        <a href="/JESUS/pages/student/payments.php" class="btn btn-primary">Manage Payments</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Equipment Verification</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                <i class="fas <?php echo $equipment['net'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                <strong>Mosquito Net</strong>
                            </p>
                            <p>
                                <i class="fas <?php echo $equipment['bucket'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                <strong>Bucket</strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <i class="fas <?php echo $equipment['broom'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                <strong>Broom</strong>
                            </p>
                            <p>
                                <i class="fas <?php echo $equipment['toilet_bowl'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                <strong>Toilet Bowl</strong>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($equipment['verified_by']): ?>
                        <div class="alert alert-success mt-3">
                            <small>Verified on: <?php echo date('F j, Y, g:i a', strtotime($equipment['verified_at'])); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <small>Equipment will be verified by the warden during check-in.</small>
                        </div>
                    <?php endif; ?>
                    
                    <a href="/JESUS/pages/student/equipment.php" class="btn btn-primary">Equipment Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($student['room_id']): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get maintenance requests for this student's room
                    $sql = "SELECT mr.*, b.name as block_name, r.room_number 
                            FROM maintenance_requests mr
                            JOIN blocks b ON mr.block_id = b.id
                            JOIN rooms r ON mr.room_id = r.id
                            WHERE mr.room_id = ? AND mr.reported_by = ?
                            ORDER BY mr.created_at DESC LIMIT 5";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $student['room_id'], $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Issue Type</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $request['issue_type']; ?></td>
                                            <td><?php echo $request['description']; ?></td>
                                            <td>
                                                <?php 
                                                    $status_class = '';
                                                    switch ($request['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'in_progress':
                                                            $status_class = 'bg-info';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No maintenance requests found.</p>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                        Report Maintenance Issue
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Request Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Report Maintenance Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/JESUS/pages/student/maintenance-request.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="issue_type" class="form-label">Issue Type</label>
                            <select class="form-select" id="issue_type" name="issue_type" required>
                                <option value="">Select Issue Type</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Furniture">Furniture</option>
                                <option value="Structural">Structural</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="room_id" value="<?php echo $student['room_id']; ?>">
                        <input type="hidden" name="block_id" value="<?php echo $student['room_id'] ? $student['block_id'] : ''; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>


