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

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("/JESUS/pages/block-manager/students.php");
}

$student_id = (int)$_GET['id'];

// Get student details, ensuring they belong to the block manager's block
$sql = "SELECT s.*, u.name, u.email, r.room_number, b.name as block_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN rooms r ON s.room_id = r.id
        JOIN blocks b ON r.block_id = b.id
        WHERE s.id = ? AND b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $block_manager['block_id']);
$stmt->execute();
$result = $stmt->get_result();

// Check if student exists and belongs to the block manager's block
if ($result->num_rows == 0) {
    redirect("/JESUS/pages/block-manager/students.php");
}

$student = $result->fetch_assoc();

// Get maintenance requests submitted by this student
$sql = "SELECT mr.*, r.room_number, 
        CASE 
            WHEN mr.status = 'pending' THEN 'Pending'
            WHEN mr.status = 'in_progress' THEN 'In Progress'
            WHEN mr.status = 'completed' THEN 'Completed'
            WHEN mr.status = 'rejected' THEN 'Rejected'
        END as status_text
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        WHERE mr.reported_by = ? AND mr.block_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student['user_id'], $block_manager['block_id']);
$stmt->execute();
$result = $stmt->get_result();
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);

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
                    <li class="breadcrumb-item"><a href="/JESUS/pages/block-manager/students.php">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $student['name']; ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                        <h4 class="mt-3"><?php echo $student['name']; ?></h4>
                        <p class="text-muted"><?php echo $student['admission_number']; ?></p>
                    </div>
                    
                    <table class="table">
                        <tr>
                            <th>Email:</th>
                            <td><?php echo $student['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Room:</th>
                            <td><?php echo $student['room_number']; ?></td>
                        </tr>
                        <tr>
                            <th>Block:</th>
                            <td><?php echo $student['block_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Year of Study:</th>
                            <td><?php echo $student['year_of_study'] ?? 'Not specified'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Maintenance Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (count($maintenance_requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Issue Type</th>
                                        <th>Room</th>
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
                                            <td><?php echo $request['room_number']; ?></td>
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
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No maintenance requests found for this student.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Contact Student</h5>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" placeholder="Enter subject">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="3" placeholder="Enter your message"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="button" class="btn btn-success" onclick="alert('Message functionality will be implemented in a future update.')">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <a href="/JESUS/pages/block-manager/students.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Students List
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

