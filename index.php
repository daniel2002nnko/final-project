<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1 class="display-4">Welcome to Hostel Management System</h1>
            <p class="lead">Efficient management of hostel accommodations, payments, and maintenance</p>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-graduate fa-4x mb-3 text-primary"></i>
                    <h3 class="card-title">Students</h3>
                    <p class="card-text">Register, upload payment documents, and manage your hostel accommodation.</p>
                    <a href="/JESUS/auth/register.php" class="btn btn-primary">Register Now</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-shield fa-4x mb-3 text-primary"></i>
                    <h3 class="card-title">Warden</h3>
                    <p class="card-text">Manage students, verify payments, assign mattresses, and generate reports.</p>
                    <a href="/JESUS/auth/login.php" class="btn btn-primary">Login</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-4x mb-3 text-primary"></i>
                    <h3 class="card-title">Block Manager</h3>
                    <p class="card-text">Submit maintenance requests and report facility issues to the warden.</p>
                    <a href="/JESUS/auth/login.php" class="btn btn-primary">Login</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">About Our Hostel</h4>
                </div>
                <div class="card-body">
                    <p>Our hostel accommodation system provides comfortable living spaces for students with the following features:</p>
                    <ul>
                        <li>Four blocks with 50 rooms each</li>
                        <li>Automated room assignment system</li>
                        <li>Equipment verification process</li>
                        <li>Efficient maintenance request handling</li>
                        <li>Secure payment verification</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
