<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Display success/error messages if set in session
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get counts for dashboard
$sql = "SELECT 
        (SELECT COUNT(*) FROM students) as total_students,
        (SELECT COUNT(*) FROM students WHERE room_id IS NOT NULL) as assigned_rooms,
        (SELECT COUNT(*) FROM students WHERE college_fee_paid = 0 AND college_fee_document IS NOT NULL) as pending_college_payments,
        (SELECT COUNT(*) FROM students WHERE hostel_fee_paid = 0 AND hostel_fee_document IS NOT NULL) as pending_hostel_payments,
        (SELECT COUNT(*) FROM maintenance_requests WHERE status = 'pending') as pending_maintenance,
        (SELECT COUNT(*) FROM blocks) as total_blocks,
        (SELECT COUNT(*) FROM rooms) as total_rooms,
        (SELECT COUNT(*) FROM rooms WHERE id NOT IN (SELECT room_id FROM students WHERE room_id IS NOT NULL)) as available_rooms";
$result = $conn->query($sql);
$counts = $result->fetch_assoc();

// Get recent students
$sql = "SELECT s.*, u.email, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN blocks b ON r.block_id = b.id
        ORDER BY s.created_at DESC LIMIT 5";
$result = $conn->query($sql);
$recent_students = $result->fetch_all(MYSQLI_ASSOC);

// Get pending payments
$sql = "SELECT s.*, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        WHERE (s.college_fee_paid = 0 AND s.college_fee_document IS NOT NULL) 
           OR (s.hostel_fee_paid = 0 AND s.hostel_fee_document IS NOT NULL)
        ORDER BY s.created_at DESC LIMIT 5";
$result = $conn->query($sql);
$pending_payments = $result->fetch_all(MYSQLI_ASSOC);

// Get recent maintenance requests
$sql = "SELECT mr.*, s.first_name, s.last_name, r.room_number, b.name as block_name 
        FROM maintenance_requests mr
        JOIN students s ON mr.reported_by = s.user_id
        JOIN rooms r ON mr.room_id = r.id
        JOIN blocks b ON mr.block_id = b.id
        ORDER BY mr.created_at DESC LIMIT 5";
$result = $conn->query($sql);
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get room occupancy by block
$sql = "SELECT b.name as block_name, 
        COUNT(r.id) as total_rooms,
        COUNT(s.id) as occupied_rooms
        FROM blocks b
        LEFT JOIN rooms r ON b.id = r.block_id
        LEFT JOIN students s ON r.id = s.room_id
        GROUP BY b.id";
$result = $conn->query($sql);
$room_occupancy = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Admin Dashboard</h2>
            <p>Welcome, <?php echo $_SESSION['name']; ?>! Here's an overview of the hostel management system.</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Students</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['total_students']; ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="students.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Assigned Rooms</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['assigned_rooms']; ?></h2>
                        </div>
                        <i class="fas fa-home fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="rooms.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Pending Payments</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['pending_college_payments'] + $counts['pending_hostel_payments']; ?></h2>
                        </div>
                        <i class="fas fa-money-bill fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="verify-payments.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Maintenance Requests</h5>
                            <h2 class="mt-2 mb-0"><?php echo $counts['pending_maintenance']; ?></h2>
                        </div>
                        <i class="fas fa-tools fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="maintenance.php" class="text-white stretched-link">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Yearly Reports</h5>
                            <h2 class="mt-2 mb-0"><i class="fas fa-chart-line"></i></h2>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="reports.php" class="text-white stretched-link">View Reports</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Room Availability -->
    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Room Availability</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Rooms</h5>
                                    <h2 class="mb-0"><?php echo $counts['total_rooms']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Available Rooms</h5>
                                    <h2 class="mb-0"><?php echo $counts['available_rooms']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Occupancy by Block</h6>
                        <?php foreach ($room_occupancy as $block): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo $block['block_name']; ?></span>
                                    <span><?php echo $block['occupied_rooms']; ?>/<?php echo $block['total_rooms']; ?></span>
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
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">College Fee</h5>
                                    <div class="d-flex justify-content-between">
                                        <span>Pending:</span>
                                        <span><?php echo $counts['pending_college_payments']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Hostel Fee</h5>
                                    <div class="d-flex justify-content-between">
                                        <span>Pending:</span>
                                        <span><?php echo $counts['pending_hostel_payments']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($pending_payments) > 0): ?>
                        <div class="mt-3">
                            <h6>Recent Pending Payments</h6>
                            <div class="list-group">
                                <?php foreach ($pending_payments as $payment): ?>
                                    <a href="student-details.php?id=<?php echo $payment['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></h6>
                                            <small><?php echo date('M d', strtotime($payment['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <?php if ($payment['college_fee_paid'] == 0 && $payment['college_fee_document']): ?>
                                                <span class="badge bg-warning">College Fee</span>
                                            <?php endif; ?>
                                            <?php if ($payment['hostel_fee_paid'] == 0 && $payment['hostel_fee_document']): ?>
                                                <span class="badge bg-warning">Hostel Fee</span>
                                            <?php endif; ?>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="verify-payments.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Students</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_students) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recent_students as $student): ?>
                                <a href="student-details.php?id=<?php echo $student['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                                        <small><?php echo date('M d', strtotime($student['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?php echo $student['admission_number']; ?> | 
                                        <?php echo $student['class']; ?>
                                    </p>
                                    <small>
                                        <?php if ($student['room_id']): ?>
                                            Room: <?php echo $student['room_number']; ?>, Block: <?php echo $student['block_name']; ?>
                                        <?php else: ?>
                                            <span class="text-warning">Room not assigned</span>
                                        <?php endif; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-end">
                            <a href="students.php" class="btn btn-sm btn-primary">View All Students</a>
                        </div>
                    <?php else: ?>
                        <p>No students registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (count($maintenance_requests) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($maintenance_requests as $request): ?>
                                <a href="maintenance-details.php?id=<?php echo $request['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo $request['issue_type']; ?></h6>
                                        <small><?php echo date('M d', strtotime($request['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        Room: <?php echo $request['room_number']; ?>, Block: <?php echo $request['block_name']; ?>
                                    </p>
                                    <small>
                                        <?php 
                                            $status_class = '';
                                            switch ($request['status']) {
                                                case 'pending':
                                                    $status_class = 'text-warning';
                                                    break;
                                                case 'in_progress':
                                                    $status_class = 'text-info';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'text-success';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'text-danger';
                                                    break;
                                            }
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                        | Reported by: <?php echo $request['first_name'] . ' ' . $request['last_name']; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-end">
                            <a href="maintenance.php" class="btn btn-sm btn-primary">View All Requests</a>
                        </div>
                    <?php else: ?>
                        <p>No maintenance requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="verify-payments.php" class="btn btn-success btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-money-bill fa-3x mb-3"></i>
                                <span>Verify Payments</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="assign-rooms.php" class="btn btn-primary btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-home fa-3x mb-3"></i>
                                <span>Assign Rooms</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="maintenance.php" class="btn btn-warning btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-tools fa-3x mb-3"></i>
                                <span>Maintenance</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage-block-managers.php" class="btn btn-info btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                                <i class="fas fa-user-shield fa-3x mb-3"></i>
                                <span>Manage Block Managers</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Create Block Manager Modal -->
<div class="modal fade" id="createBlockManagerModal" tabindex="-1" aria-labelledby="createBlockManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="createBlockManagerModalLabel"><i class="fas fa-user-shield"></i> Create Block Manager</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/JESUS/pages/admin/create-block-manager.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student to Assign as Block Manager</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Select a student --</option>
                            <?php
                            // Get eligible students for block managers
                            $bm_sql = "SELECT s.id, u.name, s.admission_number, r.room_number, b.name as block_name 
                                    FROM students s 
                                    JOIN users u ON s.user_id = u.id 
                                    LEFT JOIN rooms r ON s.room_id = r.id 
                                    LEFT JOIN blocks b ON r.block_id = b.id 
                                    WHERE u.role = 'student' AND s.room_id IS NOT NULL
                                    ORDER BY b.name, r.room_number
                                    LIMIT 20";
                            $bm_result = $conn->query($bm_sql);
                            $eligible_students = $bm_result->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($eligible_students as $student): 
                            ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['name']; ?> (<?php echo $student['admission_number']; ?>) - 
                                    Block: <?php echo $student['block_name'] ?? 'N/A'; ?>, Room: <?php echo $student['room_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only students with assigned rooms can be block managers.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-user-shield"></i> Create Block Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>





