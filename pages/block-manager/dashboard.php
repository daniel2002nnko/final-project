<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a block manager
if (!is_logged_in() || !check_role('block_manager')) {
    redirect("/JESUS/auth/login.php");
}

// Get block manager information
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.id as student_id, s.admission_number, r.room_number, b.id as block_id, b.name as block_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN rooms r ON s.room_id = r.id
        JOIN blocks b ON r.block_id = b.id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$block_manager = $result->fetch_assoc();

// Get maintenance requests for the block
$block_id = $block_manager['block_id'];
$sql = "SELECT mr.*, r.room_number, u.name as reported_by_name, 
        CASE 
            WHEN mr.status = 'pending' THEN 'Pending'
            WHEN mr.status = 'in_progress' THEN 'In Progress'
            WHEN mr.status = 'completed' THEN 'Completed'
            WHEN mr.status = 'rejected' THEN 'Rejected'
        END as status_text
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        JOIN users u ON mr.reported_by = u.id
        WHERE mr.block_id = ?
        ORDER BY 
            CASE 
                WHEN mr.status = 'pending' THEN 1
                WHEN mr.status = 'in_progress' THEN 2
                WHEN mr.status = 'completed' THEN 3
                WHEN mr.status = 'rejected' THEN 4
            END,
            mr.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $block_id);
$stmt->execute();
$result = $stmt->get_result();
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get students in the block
$sql = "SELECT s.id, u.name, s.admission_number, r.room_number
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN rooms r ON s.room_id = r.id
        WHERE r.block_id = ?
        ORDER BY r.room_number";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $block_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Block Manager Dashboard</h2>
                            <p class="text-muted">Welcome, <?php echo $_SESSION['name']; ?></p>
                        </div>
                        <div class="text-end">
                            <h5>Block: <?php echo $block_manager['block_name']; ?></h5>
                            <p class="mb-0">Room: <?php echo $block_manager['room_number']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Stats -->
        <div class="col-md-4">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Students</h5>
                            <h2 class="mt-2 mb-0"><?php echo count($students); ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="students.php" class="text-white stretched-link">View Students</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Maintenance Requests</h5>
                            <h2 class="mt-2 mb-0"><?php echo count($maintenance_requests); ?></h2>
                        </div>
                        <i class="fas fa-tools fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="maintenance.php" class="text-white stretched-link">View Requests</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Create Request</h5>
                            <h2 class="mt-2 mb-0"><i class="fas fa-plus-circle"></i></h2>
                        </div>
                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="create-maintenance.php" class="text-white stretched-link">Create New</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Maintenance Requests -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (count($maintenance_requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Room</th>
                                        <th>Issue</th>
                                        <th>Reported By</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenance_requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo $request['room_number']; ?></td>
                                            <td><?php echo $request['issue_type']; ?></td>
                                            <td><?php echo $request['reported_by_name']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo ($request['status'] == 'pending') ? 'warning' : 
                                                        (($request['status'] == 'in_progress') ? 'info' : 
                                                        (($request['status'] == 'completed') ? 'success' : 'danger')); 
                                                ?>">
                                                    <?php echo $request['status_text']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <a href="view-maintenance.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="maintenance.php" class="btn btn-primary">View All Requests</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No maintenance requests found for your block.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Students in Block -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Students in Block <?php echo $block_manager['block_name']; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Admission Number</th>
                                        <th>Room Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['admission_number']; ?></td>
                                            <td><?php echo $student['room_number']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="students.php" class="btn btn-success">View All Students</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No students found in your block.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>