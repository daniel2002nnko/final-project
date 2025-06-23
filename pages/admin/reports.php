<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    redirect("/JESUS/login.php");
}

// Get current year or selected year
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Generate yearly report data
function generateYearlyReport($conn, $year) {
    $report = [];
    
    // Get student statistics
    $sql = "SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN room_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_rooms,
            SUM(CASE WHEN college_fee_paid = 1 THEN 1 ELSE 0 END) as college_fees_paid,
            SUM(CASE WHEN hostel_fee_paid = 1 THEN 1 ELSE 0 END) as hostel_fees_paid
            FROM students
            WHERE YEAR(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['students'] = $result->fetch_assoc();
    
    // Get maintenance statistics
    $sql = "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
            FROM maintenance_requests
            WHERE YEAR(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['maintenance'] = $result->fetch_assoc();
    
    // Get maintenance requests by month
    $sql = "SELECT 
            MONTH(created_at) as month,
            COUNT(*) as total_requests
            FROM maintenance_requests
            WHERE YEAR(created_at) = ?
            GROUP BY MONTH(created_at)
            ORDER BY MONTH(created_at)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['monthly_maintenance'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get room occupancy statistics
    $sql = "SELECT 
            b.name as block_name,
            COUNT(r.id) as total_rooms,
            SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) as occupied_rooms
            FROM blocks b
            LEFT JOIN rooms r ON b.id = r.block_id
            LEFT JOIN students s ON r.id = s.room_id
            GROUP BY b.id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['room_occupancy'] = $result->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

// Generate the report
$report = generateYearlyReport($conn, $year);

// Get available years for the dropdown
$sql = "SELECT DISTINCT YEAR(created_at) as year FROM students ORDER BY year DESC";
$result = $conn->query($sql);
$available_years = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-line"></i> Yearly Reports</h2>
            <p class="text-muted">View automatically generated yearly reports for the hostel management system.</p>
        </div>
        <div class="col-md-4 text-end">
            <form method="get" class="d-flex">
                <select name="year" class="form-select me-2" onchange="this.form.submit()">
                    <?php foreach ($available_years as $yr): ?>
                        <option value="<?php echo $yr['year']; ?>" <?php echo ($yr['year'] == $year) ? 'selected' : ''; ?>>
                            <?php echo $yr['year']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Annual Report for <?php echo $year; ?></h5>
        </div>
        <div class="card-body">
            <!-- Student Statistics -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4>Student Statistics</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['students']['total_students'] ?? 0; ?></h3>
                                    <p class="mb-0">Total Students</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['students']['assigned_rooms'] ?? 0; ?></h3>
                                    <p class="mb-0">Assigned Rooms</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['students']['college_fees_paid'] ?? 0; ?></h3>
                                    <p class="mb-0">College Fees Paid</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['students']['hostel_fees_paid'] ?? 0; ?></h3>
                                    <p class="mb-0">Hostel Fees Paid</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Statistics -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4>Maintenance Statistics</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['maintenance']['total_requests'] ?? 0; ?></h3>
                                    <p class="mb-0">Total Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['maintenance']['pending_requests'] ?? 0; ?></h3>
                                    <p class="mb-0">Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['maintenance']['in_progress_requests'] ?? 0; ?></h3>
                                    <p class="mb-0">In Progress</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report['maintenance']['completed_requests'] ?? 0; ?></h3>
                                    <p class="mb-0">Completed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Occupancy Chart -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4>Room Occupancy by Block</h4>
                    <canvas id="roomOccupancyChart" height="100"></canvas>
                </div>
            </div>

            <!-- Monthly Maintenance Requests Chart -->
            <div class="row">
                <div class="col-md-12">
                    <h4>Monthly Maintenance Requests</h4>
                    <canvas id="maintenanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <a href="/JESUS/pages/admin/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Room Occupancy Chart
    const roomOccupancyData = <?php echo json_encode($report['room_occupancy']); ?>;
    const roomOccupancyCtx = document.getElementById('roomOccupancyChart').getContext('2d');
    
    new Chart(roomOccupancyCtx, {
        type: 'bar',
        data: {
            labels: roomOccupancyData.map(item => item.block_name),
            datasets: [
                {
                    label: 'Total Rooms',
                    data: roomOccupancyData.map(item => item.total_rooms),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Occupied Rooms',
                    data: roomOccupancyData.map(item => item.occupied_rooms),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Monthly Maintenance Requests Chart
    const monthlyData = <?php echo json_encode($report['monthly_maintenance']); ?>;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const maintenanceCtx = document.getElementById('maintenanceChart').getContext('2d');
    
    // Prepare data for all months (even if no requests)
    const monthlyRequests = Array(12).fill(0);
    monthlyData.forEach(item => {
        monthlyRequests[item.month - 1] = parseInt(item.total_requests);
    });
    
    new Chart(maintenanceCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Maintenance Requests',
                data: monthlyRequests,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>

