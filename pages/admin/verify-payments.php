<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !check_role('admin')) {
    redirect("/JESUS/auth/login.php");
}

// Handle search and filters
$search = '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize_input($_GET['search']);
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build the query based on filters
$count_sql = "SELECT COUNT(*) as total FROM students s 
              JOIN users u ON s.user_id = u.id
              WHERE ";

$sql = "SELECT s.*, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        WHERE ";

// Apply filters
if ($filter == 'college') {
    $count_sql .= "s.college_fee_paid = 0 AND s.college_fee_document IS NOT NULL";
    $sql .= "s.college_fee_paid = 0 AND s.college_fee_document IS NOT NULL";
} elseif ($filter == 'hostel') {
    $count_sql .= "s.hostel_fee_paid = 0 AND s.hostel_fee_document IS NOT NULL";
    $sql .= "s.hostel_fee_paid = 0 AND s.hostel_fee_document IS NOT NULL";
} else {
    $count_sql .= "(s.college_fee_paid = 0 AND s.college_fee_document IS NOT NULL) OR 
                  (s.hostel_fee_paid = 0 AND s.hostel_fee_document IS NOT NULL)";
    $sql .= "(s.college_fee_paid = 0 AND s.college_fee_document IS NOT NULL) OR 
            (s.hostel_fee_paid = 0 AND s.hostel_fee_document IS NOT NULL)";
}

// Apply search if provided
if (!empty($search)) {
    $count_sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($count_sql);
}

// Execute count query
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the main query
$sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";

// Prepare and execute the main query
if (!empty($search)) {
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$pending_payments = $result->fetch_all(MYSQLI_ASSOC);

$success = '';
$error = '';

// Handle session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/JESUS/pages/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Verify Payments</li>
                </ol>
            </nav>
            
            <h2>Verify Student Payments</h2>
            <p>Review and verify student payment documents for college and hostel fees.</p>
            
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
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="" method="get" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search by name, admission number, or email" value="<?php echo $search; ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?filter=<?php echo $filter; ?>" class="btn btn-secondary ms-2">Clear</a>
                <?php endif; ?>
                
                <?php if (!empty($search) || $filter != 'all'): ?>
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-6">
            <div class="btn-group float-end" role="group">
                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?filter=all<?php echo !empty($search) ? '&search='.$search : ''; ?>" class="btn btn-<?php echo $filter == 'all' ? 'primary' : 'outline-primary'; ?>">All Pending</a>
                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?filter=college<?php echo !empty($search) ? '&search='.$search : ''; ?>" class="btn btn-<?php echo $filter == 'college' ? 'primary' : 'outline-primary'; ?>">College Fee</a>
                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?filter=hostel<?php echo !empty($search) ? '&search='.$search : ''; ?>" class="btn btn-<?php echo $filter == 'hostel' ? 'primary' : 'outline-primary'; ?>">Hostel Fee</a>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Pending Payment Verifications</h5>
        </div>
        <div class="card-body">
            <?php if (count($pending_payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Admission No.</th>
                                <th>Email</th>
                                <th>College Fee</th>
                                <th>Hostel Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_payments as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><?php echo $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']; ?></td>
                                    <td><?php echo $student['admission_number']; ?></td>
                                    <td><?php echo $student['email']; ?></td>
                                    <td>
                                        <?php if ($student['college_fee_paid']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php elseif ($student['college_fee_document']): ?>
                                            <span class="badge bg-warning">Pending</span>
                                            <a href="/JESUS/assets/uploads/payments/<?php echo $student['college_fee_document']; ?>" target="_blank" class="btn btn-sm btn-info ms-1">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['hostel_fee_paid']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php elseif ($student['hostel_fee_document']): ?>
                                            <span class="badge bg-warning">Pending</span>
                                            <a href="/JESUS/assets/uploads/payments/<?php echo $student['hostel_fee_document']; ?>" target="_blank" class="btn btn-sm btn-info ms-1">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="verify-payment.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-check-circle"></i> Verify
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
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
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
                        No pending payments found matching your search criteria.
                    <?php else: ?>
                        No pending payments to verify at this time.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>