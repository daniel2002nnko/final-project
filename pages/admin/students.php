<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Handle search
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize_input($_GET['search']);
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total number of students
$count_sql = "SELECT COUNT(*) as total FROM students s 
              JOIN users u ON s.user_id = u.id";
              
if (!empty($search)) {
    $count_sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?";
    $search_param = "%$search%";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($count_sql);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get students with pagination
$sql = "SELECT s.*, u.email, r.room_number, b.name as block_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN blocks b ON r.block_id = b.id";

if (!empty($search)) {
    $sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?";
    $sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $records_per_page, $offset);
} else {
    $sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Student Management</h2>
            <p>View and manage all student information.</p>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="" method="get" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search by name, admission number, or email" value="<?php echo $search; ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary ms-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <a href="export-students.php" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Student List</h5>
        </div>
        <div class="card-body">
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Admission No.</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Room</th>
                                <th>Block</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><?php echo $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']; ?></td>
                                    <td><?php echo $student['admission_number']; ?></td>
                                    <td><?php echo $student['email']; ?></td>
                                    <td><?php echo $student['class']; ?></td>
                                    <td>
                                        <?php if ($student['room_id']): ?>
                                            <?php echo $student['room_number']; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['room_id']): ?>
                                            <?php echo $student['block_name']; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['college_fee_paid'] && $student['hostel_fee_paid']): ?>
                                            <span class="badge bg-success">Fully Paid</span>
                                        <?php elseif ($student['college_fee_paid'] || $student['hostel_fee_paid']): ?>
                                            <span class="badge bg-warning">Partially Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="student-details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="assign-room.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-home"></i>
                                        </a>
                                        <a href="verify-payment.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <a href="/JESUS/pages/admin/assign-mattress.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-info" title="Assign Mattress">
                                            <i class="fas fa-bed"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <?php if (!empty($search)): ?>
                        No students found matching your search criteria.
                    <?php else: ?>
                        No students registered yet.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
