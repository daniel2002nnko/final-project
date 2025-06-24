<?php include 'includes/header.php'; ?>

<!-- Custom CSS for homepage only -->
<style>
    :root {
        --primary-color: #6a1b9a;
        --secondary-color: #9c27b0;
        --accent-color: #ce93d8;
        --light-color: #f3e5f5;
        --dark-color: #4a148c;
    }
    
    body {
        background-color: #f8f9fa;
    }
    
    .hero-section {
        background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
        color: white;
        padding: 5rem 0;
        border-radius: 0 0 50px 50px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .feature-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
        transition: transform 0.3s;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.2);
    }
    
    .btn-custom-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-custom-primary:hover {
        background-color: var(--dark-color);
        border-color: var(--dark-color);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.4);
    }
    
    .btn-custom-outline {
        background-color: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        padding: 0.6rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-custom-outline:hover {
        background-color: var(--primary-color);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.4);
    }
    
    .about-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .about-card .card-header {
        background-color: var(--primary-color);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.2rem;
    }
    
    .testimonial-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin: 1rem 0.5rem;
        padding: 1.5rem;
        background-color: white;
    }
    
    .testimonial-img {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent-color);
    }
    
    .stat-card {
        background-color: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .stat-icon {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
    }
    
    .stat-title {
        color: #6c757d;
        font-size: 1rem;
    }
    
    .footer-custom {
        background-color: var(--dark-color);
        color: white;
        padding: 3rem 0 1.5rem;
        margin-top: 5rem;
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Welcome to Our Hostel Management System</h1>
                <p class="lead mb-4">A comprehensive solution for efficient management of hostel accommodations, payments, and maintenance requests.</p>
                <div class="d-flex gap-3">
                    <a href="/JESUS/auth/register.php" class="btn btn-custom-primary btn-lg">Get Started</a>
                    <a href="#about" class="btn btn-custom-outline btn-lg">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="https://img.freepik.com/free-vector/college-university-students-group-young-happy-people-standing-isolated-white-background_575670-66.jpg" alt="Students" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 mt-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold">Our Services</h2>
                <p class="lead text-muted">Designed for students, wardens, and block managers</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-user-graduate feature-icon"></i>
                        <h3 class="card-title">For Students</h3>
                        <p class="card-text">Register, upload payment documents, and manage your hostel accommodation with ease.</p>
                        <a href="/JESUS/auth/register.php" class="btn btn-custom-primary mt-3">Register Now</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-user-shield feature-icon"></i>
                        <h3 class="card-title">For Wardens</h3>
                        <p class="card-text">Manage students, verify payments, assign rooms, and generate comprehensive reports.</p>
                        <a href="/JESUS/auth/login.php" class="btn btn-custom-primary mt-3">Login</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-tools feature-icon"></i>
                        <h3 class="card-title">For Block Managers</h3>
                        <p class="card-text">Submit maintenance requests and report facility issues to the warden efficiently.</p>
                        <a href="/JESUS/auth/login.php" class="btn btn-custom-primary mt-3">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold">Our Facilities</h2>
                <p class="lead text-muted">Providing comfortable living spaces for students</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-building stat-icon"></i>
                    <div class="stat-number">4</div>
                    <div class="stat-title">Hostel Blocks</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-door-open stat-icon"></i>
                    <div class="stat-number">200</div>
                    <div class="stat-title">Total Rooms</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-wifi stat-icon"></i>
                    <div class="stat-number">100%</div>
                    <div class="stat-title">WiFi Coverage</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-shield-alt stat-icon"></i>
                    <div class="stat-number">24/7</div>
                    <div class="stat-title">Security</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card about-card">
                    <div class="card-header">
                        <h4 class="mb-0">About Our Hostel</h4>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <p>Our hostel accommodation system provides comfortable living spaces for students with the following features:</p>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Four blocks with 50 rooms each
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Automated room assignment system
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Equipment verification process
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Efficient maintenance request handling
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Secure payment verification
                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-6 mt-4 mt-lg-0">
                                <img src="https://img.freepik.com/free-photo/modern-residential-building_1268-14735.jpg" alt="Hostel Building" class="img-fluid rounded-3 shadow">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold">What Our Students Say</h2>
                <p class="lead text-muted">Hear from those who have experienced our services</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Student" class="testimonial-img me-3">
                        <div>
                            <h5 class="mb-0">Sarah Johnson</h5>
                            <small class="text-muted">Computer Science</small>
                        </div>
                    </div>
                    <p class="mb-0">"The hostel management system has made my life so much easier. I can submit maintenance requests and track my payments all in one place!"</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/men/44.jpg" alt="Student" class="testimonial-img me-3">
                        <div>
                            <h5 class="mb-0">Michael Chen</h5>
                            <small class="text-muted">Engineering</small>
                        </div>
                    </div>
                    <p class="mb-0">"The room assignment process was smooth and efficient. I appreciate how quickly maintenance issues are resolved through the system."</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Student" class="testimonial-img me-3">
                        <div>
                            <h5 class="mb-0">Aisha Patel</h5>
                            <small class="text-muted">Business Administration</small>
                        </div>
                    </div>
                    <p class="mb-0">"As a new student, the system made it easy to register and secure my accommodation. The interface is user-friendly and intuitive."</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow" style="border-radius: 20px; background: linear-gradient(135deg, var(--primary-color), var(--dark-color));">
                    <div class="card-body p-5 text-center text-white">
                        <h2 class="fw-bold mb-4">Ready to Get Started?</h2>
                        <p class="lead mb-4">Join our hostel management system today and experience hassle-free accommodation management.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="/JESUS/auth/register.php" class="btn btn-light btn-lg px-4">Register Now</a>
                            <a href="/JESUS/auth/login.php" class="btn btn-outline-light btn-lg px-4">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About DIT Section -->
<section class="py-5 bg-light" id="about-dit">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold">About Dar Es Salaam Institute of Technology</h2>
                <p class="lead text-muted">Excellence in Technical Education and Training</p>
            </div>
        </div>
        
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="images/dit.jpg" alt="DIT Campus" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4 text-primary">Our Institution</h3>
                        <p>Dar es Salaam Institute of Technology (DIT) is a premier technical institution located in Tanzania, dedicated to providing high-quality education in engineering, technology, and technical fields.</p>
                        
                        <h5 class="mt-4 mb-3 text-primary">Our Mission</h5>
                        <p>To provide quality technical education and training, research, and consultancy services that meet the technological needs of Tanzania and beyond.</p>
                        
                        <h5 class="mt-4 mb-3 text-primary">Our Vision</h5>
                        <p>To be a center of excellence in technical education and training, research, and innovation in Tanzania and beyond.</p>
                        
                        <div class="mt-4">
                            <a href="https://www.dit.ac.tz/" target="_blank" class="btn btn-custom-primary">
                                <i class="fas fa-external-link-alt me-2"></i> Visit DIT Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hostel System Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold">DIT Hostel Management System</h2>
                <p class="lead text-muted">Streamlining accommodation for our students</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 text-primary">System Overview</h4>
                        <p>The DIT Hostel Management System is designed to streamline the process of hostel allocation, fee payment verification, and maintenance management for students residing in campus accommodations.</p>
                        <p>Our system serves multiple stakeholders including students, wardens, and block managers, providing a comprehensive solution for all hostel-related activities.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 text-primary">Key Benefits</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item border-0 ps-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Simplified room allocation process
                            </li>
                            <li class="list-group-item border-0 ps-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Digital payment verification
                            </li>
                            <li class="list-group-item border-0 ps-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Efficient maintenance request handling
                            </li>
                            <li class="list-group-item border-0 ps-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Real-time availability updates
                            </li>
                            <li class="list-group-item border-0 ps-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Transparent communication between students and administration
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4 text-primary">Hostel Facilities at DIT</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-building me-2 text-primary"></i> Accommodation</h5>
                                <p>DIT provides comfortable and secure accommodation for students across four blocks, each designed to meet the needs of different student groups.</p>
                                
                                <h5 class="mt-4"><i class="fas fa-wifi me-2 text-primary"></i> Internet Connectivity</h5>
                                <p>All hostel blocks are equipped with high-speed WiFi, ensuring students have access to online resources for their studies.</p>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><i class="fas fa-shield-alt me-2 text-primary"></i> Security</h5>
                                <p>24/7 security personnel and CCTV surveillance ensure the safety of all residents and their belongings.</p>
                                
                                <h5 class="mt-4"><i class="fas fa-utensils me-2 text-primary"></i> Amenities</h5>
                                <p>Common areas, study rooms, and recreational facilities are available to enhance the student living experience.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer-custom">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="text-uppercase mb-4">Hostel Management</h5>
                <p>Our system provides a comprehensive solution for managing hostel accommodations, payments, and maintenance requests.</p>
            </div>
            
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="text-uppercase mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/JESUS/index.php" class="text-white text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="/JESUS/auth/register.php" class="text-white text-decoration-none">Register</a></li>
                    <li class="mb-2"><a href="/JESUS/auth/login.php" class="text-white text-decoration-none">Login</a></li>
                </ul>
            </div>
            
            <div class="col-lg-4">
                <h5 class="text-uppercase mb-4">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Dar Es Salaam Institute of Technolgy</li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> +255 715 076 076</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> danieljoshuannko@g,ail</li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 bg-light">
        
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>



