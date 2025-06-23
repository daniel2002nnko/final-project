<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !check_role('student')) {
    redirect("/auth/login.php");
}

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT s.* FROM students s WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Get equipment status
$sql = "SELECT e.*, u.name as verified_by_name 
        FROM equipment e 
        LEFT JOIN users u ON e.verified_by = u.id 
        WHERE e.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$result = $stmt->get_result();
$equipment = $result->fetch_assoc();
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Equipment Verification</h2>
            <p>All students must have the following equipment for hostel accommodation.</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Required Equipment</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Equipment</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Mosquito Net</td>
                                    <td>Standard size mosquito net for single bed</td>
                                    <td>
                                        <?php if ($equipment['net']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Bucket</td>
                                    <td>Plastic bucket for water storage</td>
                                    <td>
                                        <?php if ($equipment['bucket']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Broom</td>
                                    <td>Standard cleaning broom</td>
                                    <td>
                                        <?php if ($equipment['broom']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Toilet Bowl</td>
                                    <td>Plastic toilet bowl for cleaning</td>
                                    <td>
                                        <?php if ($equipment['toilet_bowl']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($equipment['verified_by']): ?>
                        <div class="alert alert-success mt-3">
                            <p><i class="fas fa-check-circle"></i> Your equipment has been verified by <?php echo $equipment['verified_by_name']; ?> on <?php echo date('F j, Y, g:i a', strtotime($equipment['verified_at'])); ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <p><i class="fas fa-info-circle"></i> Equipment verification will be done by the warden during check-in. Please ensure you have all the required items.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Equipment Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-info-circle text-primary"></i> All equipment must be in good condition
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-info-circle text-primary"></i> Equipment will be checked during room allocation
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-info-circle text-primary"></i> Students without required equipment will not be allocated rooms
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-info-circle text-primary"></i> Equipment can be purchased from the campus store
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-info-circle text-primary"></i> Keep your equipment clean and in good condition
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Need Help?</h5>
                </div>
                <div class="card-body">
                    <p>If you have any questions about the required equipment, please contact the hostel office:</p>
                    <p><i class="fas fa-phone"></i> +123-456-7890</p>
                    <p><i class="fas fa-envelope"></i> hostel@example.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>