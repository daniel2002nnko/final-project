<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/admin/students.php");
}

$student_id = (int)$_GET['id'];

// Get student information
$sql = "SELECT s.*, u.email, r.room_number, b.name as block_name 
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

// Get maintenance requests
$sql = "SELECT mr.*, b.name as block_name, r.room_number 
        FROM maintenance_requests mr
        JOIN blocks b ON mr.block_id = b.id
        JOIN rooms r ON mr.room_id = r.id
        WHERE mr.reported_by = ?
        ORDER BY mr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/students.php">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Student Details</li>
                </ol>
            </nav>
            
            <h2>Student Details</h2>
            <p>Viewing detailed information for <?php echo $student['first_name'] . ' ' . $student['last_name']; ?></p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Full Name:</strong> <?php echo $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                    <p><strong>Class:</strong> <?php echo $student['class']; ?></p>
                    <p><strong>Admission Number:</strong> <?php echo $student['admission_number']; ?></p>
                    <p><strong>Registered On:</strong> <?php echo date('F j, Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="assign-room.php?id=<?php echo $student['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-home"></i> Assign Room
                        </a>
                        <a href="verify-payment.php?id=<?php echo $student['id']; ?>" class="btn btn-success">
                            <i class="fas fa-money-bill"></i> Verify Payments
                        </a>
                        <a href="assign-mattress.php?id=<?php echo $student['id']; ?>" class="btn btn-info">
                            <i class="fas fa-bed"></i> Assign Mattress
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Room Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($student['room_id']): ?>
                        <p><strong>Room Number:</strong> <?php echo $student['room_number']; ?></p>
                        <p><strong>Block:</strong> <?php echo $student['block_name']; ?></p>
                        <p><strong>Mattress Number:</strong> 
                            <?php echo $student['mattress_number'] ? $student['mattress_number'] : 'Not assigned'; ?>
                            <a href="/JESUS/pages/admin/assign-mattress.php?id=<?php echo $student_id; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-edit"></i> Assign
                            </a>
                        </p>
                        <p><strong>Date Assigned:</strong> 
                            <?php 
                            if (isset($student['created_at'])) {
                                echo date('F j, Y', strtotime($student['created_at'])); 
                            } else {
                                echo "Not available";
                            }
                            ?>
                        </p>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Room not assigned yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Status</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>College Fee:</strong> 
                        <?php if ($student['college_fee_paid']): ?>
                            <span class="badge bg-success">Verified</span>
                        <?php elseif ($student['college_fee_document']): ?>
                            <span class="badge bg-warning">Pending Verification</span>
                            <a href="/JESUS/uploads/payments/<?php echo $student['college_fee_document']; ?>" target="_blank" class="btn btn-sm btn-info ms-2">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Uploaded</span>
                        <?php endif; ?>
                    </p>
                    
                    <p>
                        <strong>Hostel Fee:</strong> 
                        <?php if ($student['hostel_fee_paid']): ?>
                            <span class="badge bg-success">Verified</span>
                        <?php elseif ($student['hostel_fee_document']): ?>
                            <span class="badge bg-warning">Pending Verification</span>
                            <a href="/JESUS/uploads/payments/<?php echo $student['hostel_fee_document']; ?>" target="_blank" class="btn btn-sm btn-info ms-2">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Uploaded</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Equipment Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($equipment): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td>Mosquito Net</td>
                                        <td>
                                            <?php if (isset($equipment['net']) && $equipment['net']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Verified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bucket</td>
                                        <td>
                                            <?php if (isset($equipment['bucket']) && $equipment['bucket']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Verified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Broom</td>
                                        <td>
                                            <?php if (isset($equipment['broom']) && $equipment['broom']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Verified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Toilet Bowl</td>
                                        <td>
                                            <?php if (isset($equipment['toilet_bowl']) && $equipment['toilet_bowl']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Verified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (isset($equipment['verified_by']) && $equipment['verified_by']): ?>
                            <p class="mt-2"><small>Verified by: <?php echo isset($equipment['verified_by_name']) ? $equipment['verified_by_name'] : 'Admin'; ?></small></p>
                            <p><small>Verified on: <?php echo date('F j, Y', strtotime($equipment['verified_at'])); ?></small></p>
                        <?php endif; ?>
                        
                        <a href="verify-equipment.php?id=<?php echo $student['id']; ?>" class="btn btn-primary mt-2">
                            <i class="fas fa-check-circle"></i> Verify Equipment
                        </a>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No equipment record found for this student.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($maintenance_requests) > 0): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Room</th>
                                    <th>Block</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenance_requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['id']; ?></td>
                                        <td><?php echo $request['issue_type']; ?></td>
                                        <td><?php echo $request['description']; ?></td>
                                        <td><?php echo $request['room_number']; ?></td>
                                        <td><?php echo $request['block_name']; ?></td>
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
                                        <td>
                                            <a href="maintenance-details.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Reset Student Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="reset-password.php" method="post">
                <div class="modal-body">
                    <p>Are you sure you want to reset the password for <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>?</p>
                    <p>A new random password will be generated and sent to the student's email address.</p>
                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>



