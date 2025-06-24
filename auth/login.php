<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill all required fields";
    } else {
        // Check if email exists
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    redirect("/JESUS/pages/admin/dashboard.php");
                } elseif ($user['role'] == 'student') {
                    redirect("/JESUS/pages/student/dashboard.php");
                } elseif ($user['role'] == 'block_manager') {
                    redirect("/JESUS/pages/block-manager/dashboard.php");
                } elseif ($user['role'] == 'warden') {
                    redirect("/JESUS/pages/warden/dashboard.php");
                }
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "No account found with that email";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Custom CSS for login page -->
<style>
    :root {
        --primary-color: #6a1b9a;
        --secondary-color: #9c27b0;
        --accent-color: #ce93d8;
        --light-color: #f3e5f5;
        --dark-color: #4a148c;
        --error-color: #f44336;
    }
    
    body {
        background-color: #f8f9fa;
    }
    
    .login-container {
        margin-top: 4rem;
        margin-bottom: 4rem;
    }
    
    .login-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .login-row {
        min-height: 550px;
    }
    
    .login-left {
        background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
        color: white;
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .login-left h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }
    
    .login-left p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }
    
    .login-feature {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .login-feature-icon {
        background-color: rgba(255, 255, 255, 0.2);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    
    .login-right {
        padding: 3rem;
        background-color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    
    .login-header h3 {
        color: var(--dark-color);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .login-header p {
        color: #6c757d;
    }
    
    .form-floating {
        margin-bottom: 1.5rem;
    }
    
    .form-floating > .form-control {
        padding: 1.5rem 1rem;
        height: calc(3.5rem + 2px);
        border-radius: 10px;
        border: 1px solid #ced4da;
    }
    
    .form-floating > .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(106, 27, 154, 0.25);
    }
    
    .form-floating > label {
        padding: 1rem;
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
        z-index: 5;
    }
    
    .login-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .forgot-password {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .forgot-password:hover {
        color: var(--dark-color);
        text-decoration: underline;
    }
    
    .btn-login {
        background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
        border: none;
        border-radius: 10px;
        padding: 0.75rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s;
        margin-bottom: 1.5rem;
    }
    
    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.4);
    }
    
    .login-divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
    }
    
    .login-divider::before,
    .login-divider::after {
        content: "";
        flex: 1;
        border-bottom: 1px solid #dee2e6;
    }
    
    .login-divider span {
        padding: 0 1rem;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .social-login {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .social-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dee2e6;
        background-color: white;
        color: #6c757d;
        transition: all 0.3s;
    }
    
    .social-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .social-btn.google:hover {
        color: #DB4437;
    }
    
    .social-btn.facebook:hover {
        color: #4267B2;
    }
    
    .social-btn.twitter:hover {
        color: #1DA1F2;
    }
    
    .register-link {
        text-align: center;
        margin-top: 1rem;
    }
    
    .register-link a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .register-link a:hover {
        color: var(--dark-color);
        text-decoration: underline;
    }
    
    .alert-custom-danger {
        background-color: #ffebee;
        color: var(--error-color);
        border: none;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 991.98px) {
        .login-left {
            display: none;
        }
    }
</style>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card login-card">
                <div class="row g-0 login-row">
                    <!-- Left side with gradient background -->
                    <div class="col-lg-6 login-left">
                        <h2>Welcome Back!</h2>
                        <p>Log in to access your hostel management dashboard and manage your accommodation needs.</p>
                        
                        <div class="login-features">
                            <div class="login-feature">
                                <div class="login-feature-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div>Secure account access</div>
                            </div>
                            
                            <div class="login-feature">
                                <div class="login-feature-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div>Manage your accommodation</div>
                            </div>
                            
                            <div class="login-feature">
                                <div class="login-feature-icon">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div>Track payments and requests</div>
                            </div>
                            
                            <div class="login-feature">
                                <div class="login-feature-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div>24/7 support access</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right side with login form -->
                    <div class="col-lg-6 login-right">
                        <div class="login-header">
                            <h3>Login to Your Account</h3>
                            <p>Enter your credentials to access your dashboard</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-custom-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                            </div>
                            
                            <div class="form-floating input-icon-container">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                                <span class="input-icon" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </span>
                            </div>
                            
                            <div class="login-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                                <a href="reset-password.php" class="forgot-password">Forgot Password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            
                            <div class="login-divider">
                                <span>OR</span>
                            </div>
                            
                            <div class="social-login">
                                <button type="button" class="social-btn google">
                                    <i class="fab fa-google"></i>
                                </button>
                                <button type="button" class="social-btn facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </button>
                                <button type="button" class="social-btn twitter">
                                    <i class="fab fa-twitter"></i>
                                </button>
                            </div>
                            
                            <div class="register-link">
                                <p>Don't have an account? <a href="register.php">Register here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('password-toggle');
        
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

