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

// Initialize variables
$block_id = $block_manager['block_id'];
$search = '';
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Build the query
$sql = "SELECT s.id, u.name, u.email, s.admission_number, r.room_number
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN rooms r ON s.room_id = r.id
        WHERE r.block_id = ?";

$params = [$block_id];
$types = "i";

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ? OR r.room_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

// Count total records for pagination
$count_sql = str_replace("SELECT s.id, u.name, u.email, s.admission_number, r.room_number", "SELECT COUNT(*) as total", $sql);
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get students with pagination
$sql .= " ORDER BY r.room_number LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

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
                    <li class="breadcrumb-item active" aria-current="page">Students</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users"></i> Students in Block <?php echo $block_manager['block_name']; ?></h2>
            <p class="text-muted">Manage and view all students in your block.</p>
        </div>
        <div class="col-md-4">
            <form method="get" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Student List</h5>
        </div>
        <div class="card-body">
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Admission Number</th>
                                <th>Email</th>
                                <th>Room Number</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['name']; ?></td>
                                    <td><?php echo $student['admission_number']; ?></td>
                                    <td><?php echo $student['email']; ?></td>
                                    <td><?php echo $student['room_number']; ?></td>
                                    <td>
                                        <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
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
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo (!empty($search)) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($search)) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo (!empty($search)) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No students found in your block<?php echo (!empty($search)) ? ' matching "' . htmlspecialchars($search) . '"' : ''; ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>



