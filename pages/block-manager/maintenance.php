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

$success = '';
$error = '';

// Handle form submission for new maintenance request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create') {
    $room_id = (int)$_POST['room_id'];
    $issue_type = $_POST['issue_type'];
    $description = $_POST['description'];
    
    // Get block_id from room_id
    $room_sql = "SELECT block_id FROM rooms WHERE id = ?";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    $room = $room_result->fetch_assoc();
    $block_id = $room['block_id'];
    
    // Insert maintenance request
    $sql = "INSERT INTO maintenance_requests (room_id, block_id, issue_type, description, reported_by, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $room_id, $block_id, $issue_type, $description, $user_id);
    
    if ($stmt->execute()) {
        $success = "Maintenance request has been submitted successfully.";
    } else {
        $error = "Error submitting maintenance request: " . $conn->error;
    }
}

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
            mr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $block_id);
$stmt->execute();
$result = $stmt->get_result();
$maintenance_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get rooms in the block for the dropdown
$sql = "SELECT r.id, r.room_number, 
        CASE WHEN s.id IS NOT NULL THEN CONCAT(r.room_number, ' - ', u.name) ELSE r.room_number END as room_display
        FROM rooms r
        LEFT JOIN students s ON r.id = s.room_id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE r.block_id = ?
        ORDER BY r.room_number";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $block_id);
$stmt->execute();
$result = $stmt->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);

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
                    <li class="breadcrumb-item active" aria-current="page">Maintenance Requests</li>
                </ol>
            </nav>
        </div>
    </div>
    
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
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Submit Maintenance Request</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room_id" class="form-label">Room</label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">-- Select Room --</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>">
                                                <?php echo $room['room_display']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_type" class="form-label">Issue Type</label>
                                    <select class="form-select" id="issue_type" name="issue_type" required>
                                        <option value="">-- Select Issue Type --</option>
                                        <option value="Plumbing">Plumbing</option>
                                        <option value="Electrical">Electrical</option>
                                        <option value="Furniture">Furniture</option>
                                        <option value="Cleaning">Cleaning</option>
                                        <option value="Security">Security</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <div class="form-text">Please provide detailed information about the issue.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Maintenance Requests for Block <?php echo $block_manager['block_name']; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($maintenance_requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Room</th>
                                        <th>Issue Type</th>
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
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No maintenance requests found for your block.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>