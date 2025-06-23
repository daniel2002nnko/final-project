<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/JESUS/index.php">Hostel Management</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (!isset($_SESSION['role'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/JESUS/index.php">Home</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/verify-payments.php">Payments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/manage-block-managers.php">Block Managers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/maintenance.php">Maintenance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/reports.php">Reports</a>
                    </li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'warden'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/warden/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/warden/manage-block-managers.php">Block Managers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/admin/maintenance.php">Maintenance</a>
                    </li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/student/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/student/profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/student/payments.php">Payments</a>
                    </li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'block_manager'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/block-manager/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/pages/block-manager/maintenance.php">Maintenance</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo $_SESSION['name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/JESUS/auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JESUS/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>










