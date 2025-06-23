<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a block manager
if (!is_logged_in() || !check_role('block_manager')) {
    redirect("/JESUS/auth/login.php");
}

// Get block manager information
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.id as student_id, b.id as block_id, b.name as block_name
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

// Check if maintenance request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/block-manager/maintenance.php");
}

$request_id = (int)$_GET['id'];

// Get maintenance request details
$sql = "SELECT mr.*, r.room_number, b.name as block_name, u.name as reported_by_name,
        CASE 
            WHEN mr.status = 'pending' THEN 'Pending'
            WHEN mr.status = 'in_progress' THEN 'In Progress'
            WHEN mr.status = 'completed' THEN 'Completed'
            WHEN mr.status = 'rejected' THEN 'Rejected'
        END as status_text
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        JOIN blocks b ON mr.block_id = b.id
        JOIN users u ON mr.reported_by = u.id
        WHERE mr.id = ? AND mr.block_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $block_manager['block_id']);
$stmt->execute();
$result = $stmt->get_result();

// Check if maintenance request exists and belongs to the block manager's block
if ($result->num_rows == 0) {
    redirect("/JESUS/pages/block-manager/maintenance.php");
}

$request = $result->fetch_assoc();

// Include header
include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/block-manager/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/JESUS/pages/block-manager/maintenance.php">Maintenance Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Request #<?php echo $request_id; ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Maintenance Request #<?php echo $request_id; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Request Details</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($request['status'] == 'pending') ? 'warning' : 
                                                (($request['status'] == 'in_progress') ? 'info' : 
                                                (($request['status'] == 'completed') ? 'success' : 'danger')); 
                                        ?>">
                                            <?php echo $request['status_text']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Issue Type</th>
                                    <td><?php echo $request['issue_type']; ?></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><?php echo nl2br(htmlspecialchars($request['description'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Reported By</th>
                                    <td><?php echo $request['reported_by_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Date Reported</th>
                                    <td><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Location Details</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Block</th>
                                    <td><?php echo $request['block_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Room</th>
                                    <td><?php echo $request['room_number']; ?></td>
                                </tr>
                            </table>
                            
                            <?php if (!empty($request['admin_notes'])): ?>
                                <h6 class="mt-4">Admin Notes</h6>
                                <div class="alert alert-info">
                                    <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="/JESUS/pages/block-manager/maintenance.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Maintenance Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>