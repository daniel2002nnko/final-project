<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $middle_name = sanitize_input($_POST['middle_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $class = sanitize_input($_POST['class']);
    $admission_number = sanitize_input($_POST['admission_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email) || empty($class) || 
        empty($admission_number) || empty($password) || empty($confirm_password)) {
        $error = "Please fill all required fields";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Check if admission number already exists
            $sql = "SELECT id FROM students WHERE admission_number = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $admission_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Admission number already exists. Please contact administration if you believe this is an error.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Create user
                $name = $first_name . ' ' . $middle_name . ' ' . $last_name;
                $role = 'student'; // Default role for registration
                
                $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    
                    // Create student record
                    $sql = "INSERT INTO students (user_id, first_name, middle_name, last_name, class, admission_number) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isssss", $user_id, $first_name, $middle_name, $last_name, $class, $admission_number);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Error creating student record: " . $conn->error;
                    }
                } else {
                    $error = "Error creating user: " . $conn->error;
                }
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Custom CSS for registration page -->
<style>
    :root {
        --primary-color: #6a1b9a;
        --secondary-color: #9c27b0;
        --accent-color: #ce93d8;
        --light-color: #f3e5f5;
        --dark-color: #4a148c;
        --error-color: #f44336;
        --success-color: #4caf50;
    }
    
    body {
        background-color: #f8f9fa;
    }
    
    .registration-container {
        margin-top: 2rem;
        margin-bottom: 4rem;
    }
    
    .registration-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .registration-card .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }
    
    .registration-card .card-body {
        padding: 2rem;
    }
    
    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(106, 27, 154, 0.25);
    }
    
    .btn-register {
        background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
        border: none;
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s;
    }
    
    .btn-register:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.4);
    }
    
    .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-outline-secondary:hover {
        background-color: #f8f9fa;
        color: #495057;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .alert-custom-danger {
        background-color: #ffebee;
        color: var(--error-color);
        border: none;
        border-radius: 10px;
        padding: 1rem;
    }
    
    .alert-custom-success {
        background-color: #e8f5e9;
        color: var(--success-color);
        border: none;
        border-radius: 10px;
        padding: 1rem;
    }
    
    .form-floating > .form-control {
        padding-top: 1.625rem;
        padding-bottom: 0.625rem;
    }
    
    .form-floating > label {
        padding: 1rem;
    }
    
    .registration-progress {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .registration-progress::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #e9ecef;
        z-index: 1;
    }
    
    .progress-step {
        position: relative;
        z-index: 2;
        background-color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }
    
    .progress-step.active {
        background-color: var(--primary-color);
        color: white;
    }
    
    .progress-step-label {
        position: absolute;
        top: 35px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.8rem;
        color: #6c757d;
        white-space: nowrap;
    }
    
    .password-strength {
        height: 5px;
        border-radius: 5px;
        margin-top: 0.5rem;
        background-color: #e9ecef;
    }
    
    .password-strength-meter {
        height: 100%;
        border-radius: 5px;
        width: 0%;
        transition: width 0.3s, background-color 0.3s;
    }
    
    .login-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .login-link:hover {
        color: var(--dark-color);
        text-decoration: underline;
    }
    
    .input-icon-container {
        position: relative;
    }
    
    .input-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        right: 1rem;
        color: #6c757d;
        cursor: pointer;
    }
    
    /* New styles for enhanced UI */
    .registration-header-content {
        display: flex;
        align-items: center;
    }
    
    .registration-header-icon {
        background-color: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.5rem;
    }
    
    .registration-header-text h3 {
        margin-bottom: 0.25rem;
        font-weight: 700;
    }
    
    .registration-header-text p {
        margin-bottom: 0;
        opacity: 0.9;
    }
    
    .step-content {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-floating .form-control:focus ~ label,
    .form-floating .form-control:not(:placeholder-shown) ~ label {
        color: var(--primary-color);
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-helper-text {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .registration-benefits {
        background-color: var(--light-color);
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .benefit-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .benefit-item:last-child {
        margin-bottom: 0;
    }
    
    .benefit-icon {
        background-color: var(--primary-color);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 0.8rem;
    }
    
    .benefit-text {
        font-weight: 500;
    }
</style>

<div class="container registration-container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card registration-card">
                <div class="card-header">
                    <div class="registration-header-content">
                        <div class="registration-header-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="registration-header-text">
                            <h3 class="mb-0">Student Registration</h3>
                            <p class="mb-0">Create your account to access the hostel management system</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Registration Progress -->
                    <div class="registration-progress">
                        <div class="progress-step active">
                            1
                            <span class="progress-step-label">Personal Info</span>
                        </div>
                        <div class="progress-step">
                            2
                            <span class="progress-step-label">Academic Info</span>
                        </div>
                        <div class="progress-step">
                            3
                            <span class="progress-step-label">Account Setup</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-custom-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-custom-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registrationForm">
                        <!-- Step 1: Personal Information (visible by default) -->
                        <div id="step1" class="step-content">
                            <h4 class="mb-4" style="color: var(--primary-color);">Personal Information</h4>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                        <label for="first_name"><i class="fas fa-user me-2"></i>First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Middle Name">
                                        <label for="middle_name"><i class="fas fa-user me-2"></i>Middle Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                        <label for="last_name"><i class="fas fa-user me-2"></i>Last Name</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                                </div>
                                <div class="form-helper-text">We'll never share your email with anyone else.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="button" class="btn btn-register" onclick="nextStep(1, 2)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Academic Information (hidden by default) -->
                        <div id="step2" class="step-content" style="display: none;">
                            <h4 class="mb-4" style="color: var(--primary-color);">Academic Information</h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="class" name="class" placeholder="Class" required>
                                        <label for="class"><i class="fas fa-graduation-cap me-2"></i>Class/Course</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="admission_number" name="admission_number" placeholder="Admission Number" required>
                                        <label for="admission_number"><i class="fas fa-id-card me-2"></i>Admission Number</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="nextStep(2, 1)"><i class="fas fa-arrow-left me-2"></i> Previous</button>
                                <button type="button" class="btn btn-register" onclick="nextStep(2, 3)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Account Setup (hidden by default) -->
                        <div id="step3" class="step-content" style="display: none;">
                            <h4 class="mb-4" style="color: var(--primary-color);">Account Setup</h4>
                            
                            <div class="mb-3">
                                <div class="form-floating input-icon-container">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required onkeyup="checkPasswordStrength()">
                                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                                    <span class="input-icon" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-toggle"></i>
                                    </span>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-meter" id="password-strength-meter"></div>
                                </div>
                                <div class="form-helper-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating input-icon-container">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required onkeyup="checkPasswordMatch()">
                                    <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                                    <span class="input-icon" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm-password-toggle"></i>
                                    </span>
                                </div>
                                <div id="password-match-message"></div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">I agree to the <a href="#" class="login-link">Terms of Service</a> and <a href="#" class="login-link">Privacy Policy</a></label>
                            </div>
                            
                            <div class="registration-benefits">
                                <h5 class="mb-3" style="color: var(--primary-color);">Benefits of Registration</h5>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">Easy hostel accommodation management</div>
                                </div>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">Submit and track maintenance requests</div>
                                </div>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">Secure payment verification system</div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="nextStep(3, 2)"><i class="fas fa-arrow-left me-2"></i> Previous</button>
                                <button type="submit" class="btn btn-register"><i class="fas fa-user-plus me-2"></i> Register</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to navigate between steps
    function nextStep(currentStep, nextStep) {
        // Validate current step
        if (currentStep === 1) {
            if (!validateStep1()) return;
        } else if (currentStep === 2) {
            if (nextStep === 3 && !validateStep2()) return;
        }
        
        // Hide current step
        document.getElementById('step' + currentStep).style.display = 'none';
        
        // Show next step
        document.getElementById('step' + nextStep).style.display = 'block';
        
        // Update progress indicators
        updateProgressSteps(nextStep);
    }
    
    // Function to validate Step 1
    function validateStep1() {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (firstName === '') {
            alert('Please enter your first name');
            return false;
        }
        
        if (lastName === '') {
            alert('Please enter your last name');
            return false;
        }
        
        if (email === '') {
            alert('Please enter your email address');
            return false;
        }
        
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        return true;
    }
    
    // Function to validate Step 2
    function validateStep2() {
        const className = document.getElementById('class').value.trim();
        const admissionNumber = document.getElementById('admission_number').value.trim();
        
        if (className === '') {
            alert('Please enter your class/course');
            return false;
        }
        
        if (admissionNumber === '') {
            alert('Please enter your admission number');
            return false;
        }
        
        return true;
    }
    
    // Function to validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Function to update progress steps
    function updateProgressSteps(activeStep) {
        const steps = document.querySelectorAll('.progress-step');
        
        steps.forEach((step, index) => {
            if (index + 1 <= activeStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
    }
    
    // Function to check password strength
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const meter = document.getElementById('password-strength-meter');
        
        // Reset the meter
        meter.style.width = '0%';
        meter.style.backgroundColor = '#e9ecef';
        
        if (password.length === 0) {
            return;
        }
        
        // Calculate strength
        let strength = 0;
        
        // Length check
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 15;
        
        // Character type checks
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^A-Za-z0-9]/.test(password)) strength += 15;
        
        // Update the meter
        meter.style.width = strength + '%';
        
        // Color based on strength
        if (strength < 30) {
            meter.style.backgroundColor = '#f44336'; // Weak (red)
        } else if (strength < 60) {
            meter.style.backgroundColor = '#ff9800'; // Medium (orange)
        } else {
            meter.style.backgroundColor = '#4caf50'; // Strong (green)
        }
    }
    
    // Function to check if passwords match
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const messageElement = document.getElementById('password-match-message');
        
        if (confirmPassword.length === 0) {
            messageElement.innerHTML = '';
            return;
        }
        
        if (password === confirmPassword) {
            messageElement.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Passwords match</small>';
        } else {
            messageElement.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Passwords do not match</small>';
        }
    }
    
    // Function to toggle password visibility
    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(inputId === 'password' ? 'password-toggle' : 'confirm-password-toggle');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
</script>

<?php include '../includes/footer.php'; ?>





