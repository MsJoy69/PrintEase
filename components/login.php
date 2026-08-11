<?php
// --- ENABLE ERROR REPORTING FOR DEBUGGING ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "printeasee";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure Tables Exist
$conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS info (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(100) NOT NULL, otp VARCHAR(10) NOT NULL, expiry TIMESTAMP NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// --- EMAIL FUNCTION (PHPMailer) ---
function sendOTPEmail($recipientEmail, $otp, $type = 'reset') {
    // Check for PHPMailer files
    if (file_exists('PHPMailer/PHPMailer.php')) {
        require_once 'PHPMailer/Exception.php';
        require_once 'PHPMailer/PHPMailer.php';
        require_once 'PHPMailer/SMTP.php';
    } elseif (file_exists('../PHPMailer/PHPMailer.php')) {
        require_once '../PHPMailer/Exception.php';
        require_once '../PHPMailer/PHPMailer.php';
        require_once '../PHPMailer/SMTP.php';
    } else {
        return "Error: PHPMailer folder not found. Please download it.";
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // ============================================================
        // TODO: ENTER YOUR GMAIL CREDENTIALS HERE
        // ============================================================
        $mail->Username   = 'rnaoi090@gmail.com'; 
        $mail->Password   = 'gifp zvmr dcle qjmr'; 
        // ============================================================

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@printease.com', 'PrintEase Security');
        $mail->addAddress($recipientEmail);
        
        $mail->isHTML(true);

        if ($type === 'register') {
            $subject = 'Verify Your Account - PrintEase';
            $header = 'Account Verification';
            $bodyText = 'Welcome to PrintEase! Use the code below to verify your email address and complete your registration:';
        } else {
            $subject = 'Your Verification Code - PrintEase';
            $header = 'Password Reset';
            $bodyText = 'Use the code below to verify your identity:';
        }

        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2>$header</h2>
                <p>$bodyText</p>
                <h1 style='color: #38a169; letter-spacing: 5px;'>$otp</h1>
                <p>This code expires in 10 minutes.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: {$mail->ErrorInfo}";
    }
}

// Variables
$server_message = '';
$submitted_email = ''; 
$reset_stage = isset($_SESSION['reset_stage']) ? $_SESSION['reset_stage'] : 'email_input';
$register_stage = isset($_SESSION['register_stage']) ? $_SESSION['register_stage'] : 'input'; // New variable for Reg flow

// --- LOGOUT ---
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- CANCEL RESET ---
if(isset($_GET['cancel_reset'])){
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_stage']);
    header("Location: login.php");
    exit();
}

// --- CANCEL REGISTRATION ---
if(isset($_GET['cancel_register'])){
    unset($_SESSION['temp_user']);
    unset($_SESSION['register_stage']);
    header("Location: login.php");
    exit();
}

// --- REGISTER: STEP 1 - VALIDATE & SEND OTP ---
if(isset($_POST['register_init'])){
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Server-side Password Validation
    $uppercase = preg_match('@[A-Z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    if (empty($name) || empty($email) || empty($password)) {
        $server_message = 'All fields are required.';
    } else if (strlen($password) < 8) {
        $server_message = 'Password must be at least 8 characters long.';
    } else if (!$uppercase || !$number || !$special) {
        $server_message = 'Password must meet complexity requirements.';
    } else {
        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        
        if($stmt_check->get_result()->num_rows > 0){
            $server_message = 'Email already registered!';
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            
            // Store user data temporarily in session (hashed password for safety)
            $_SESSION['temp_user'] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];

            // Save OTP to DB (reusing password_resets table as a temporary verification table)
            $conn->query("DELETE FROM password_resets WHERE email='$email'");
            $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expiry) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
            $stmt->bind_param("ss", $email, $otp);
            
            if($stmt->execute()){
                $mailResult = sendOTPEmail($email, $otp, 'register');
                
                if($mailResult === true){
                    $_SESSION['register_stage'] = 'verify';
                    $_SESSION['server_msg'] = "Verification code sent to $email";
                    $_SESSION['server_msg_type'] = 'success';
                    header("Location: login.php"); 
                    exit();
                } else {
                    $server_message = "Failed to send email: " . $mailResult;
                }
            } else {
                $server_message = "Database Error: " . $conn->error;
            }
        }
    }
}

// --- REGISTER: STEP 2 - VERIFY OTP & CREATE ACCOUNT ---
if(isset($_POST['register_verify'])){
    if(!isset($_SESSION['temp_user'])) {
        $server_message = "Session expired. Please register again.";
        $_SESSION['register_stage'] = 'input';
    } else {
        $otp_input = implode("", $_POST['otp_digit']);
        $email = $_SESSION['temp_user']['email'];
        
        // Verify OTP
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email=? AND otp=? AND expiry > NOW()");
        $stmt->bind_param("ss", $email, $otp_input);
        $stmt->execute();
        
        if($stmt->get_result()->num_rows > 0){
            // OTP Valid -> Create Account
            $name = $_SESSION['temp_user']['name'];
            $pass = $_SESSION['temp_user']['password'];
            
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
            $stmt_insert->bind_param("sss", $name, $email, $pass);
            
            if($stmt_insert->execute()){
                // Sync with info table
                $profileStmt = $conn->prepare("INSERT INTO info (username, name, email) VALUES (?, ?, ?)");
                $profileStmt->bind_param("sss", $name, $name, $email); 
                $profileStmt->execute();
                
                // Clean up
                $conn->query("DELETE FROM password_resets WHERE email='$email'");
                unset($_SESSION['temp_user']);
                unset($_SESSION['register_stage']);
                
                $_SESSION['reg_success'] = 'Account verified and created! Please log in.';
                header("Location: login.php"); 
                exit();
            } else {
                $server_message = 'Registration failed: ' . $conn->error;
            }
        } else {
            $server_message = "Invalid or expired code. Please try again.";
        }
    }
}

// --- LOGIN ---
if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $submitted_email = $email;

    // Admin Login
    if ($email === "admin@admin.com" && $password === "admin123") {
        $_SESSION['user'] = "admin@admin.com";
        $_SESSION['user_name'] = "Admin";
        $_SESSION['user_id'] = 0; 
        header("Location: ../Admin/index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, name, password, email FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        // Support both Hash and Old Plaintext passwords
        $verified = false;
        if(password_verify($password, $row['password'])) {
            $verified = true;
        } elseif ($password === $row['password']) {
            $verified = true;
            // Auto-update to hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$newHash' WHERE id={$row['id']}");
        }

        if($verified){
            $_SESSION['user'] = $row['email']; 
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_id'] = $row['id']; 
            header("Location: ../index.php");
            exit();
        } else {
            $server_message = 'Invalid password!';
        }
    } else {
        $server_message = 'No account found with that email!';
    }
}

// --- FORGOT PASSWORD LOGIC (3 Stages) ---

// Stage 1: Send OTP
if(isset($_POST['send_otp'])){
    $email = trim($_POST['reset_email']);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if($stmt->get_result()->num_rows > 0){
        $otp = rand(100000, 999999);
        
        $conn->query("DELETE FROM password_resets WHERE email='$email'");
        
        $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expiry) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        
        $mailResult = sendOTPEmail($email, $otp, 'reset');
        if($mailResult === true){
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_stage'] = 'otp_input';
            $_SESSION['server_msg'] = "Verification code sent to $email";
            $_SESSION['server_msg_type'] = 'success';
            header("Location: login.php"); 
            exit();
        } else {
            $server_message = "Failed to send email: " . $mailResult;
        }
    } else {
        $server_message = "Email not found in our system.";
    }
}

// Stage 2: Verify OTP
if(isset($_POST['verify_otp'])){
    $otp_input = implode("", $_POST['otp_digit']);
    $email = $_SESSION['reset_email'];
    
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email=? AND otp=? AND expiry > NOW()");
    $stmt->bind_param("ss", $email, $otp_input);
    $stmt->execute();
    
    if($stmt->get_result()->num_rows > 0){
        $_SESSION['reset_stage'] = 'password_input';
        $_SESSION['server_msg'] = "Code verified. Please set your new password.";
        $_SESSION['server_msg_type'] = 'success';
        header("Location: login.php");
        exit();
    } else {
        $server_message = "Invalid or expired code. Please try again.";
    }
}

// Stage 3: Reset Password
if(isset($_POST['do_reset_password'])){
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];
    
    if($new_password !== $confirm_password){
        $server_message = 'Passwords do not match.';
    } elseif(strlen($new_password) < 8) {
        $server_message = 'Password must be at least 8 characters.';
    } else {
        $password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $password_hashed, $email);
        
        if($stmt->execute()){
            $conn->query("DELETE FROM password_resets WHERE email='$email'");
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_stage']);
            $_SESSION['reset_success'] = 'Password successfully reset! Please log in.';
            header("Location: login.php");
            exit();
        } else {
            $server_message = 'Update failed: ' . $conn->error;
        }
    }
}

// Handle Session Flash Messages
if(isset($_SESSION['server_msg'])){
    $server_message = $_SESSION['server_msg'];
    $server_msg_type = $_SESSION['server_msg_type'] ?? 'error';
    unset($_SESSION['server_msg']);
    unset($_SESSION['server_msg_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintEase - Login System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login-style.css">
    <style>
        /* Inline styles for OTP Input & Password Requirements */
        .otp-container { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
        .otp-field {
            width: 45px; height: 50px;
            text-align: center; font-size: 20px; font-weight: bold;
            border: 1px solid #e2e8f0; border-radius: 8px;
            transition: border-color 0.2s;
        }
        .otp-field:focus {
            border-color: #38a169; outline: none;
            box-shadow: 0 0 0 1px #38a169;
        }

        /* Password Requirements Styling */
        .password-requirements {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            margin-bottom: 15px;
            text-align: left;
            font-size: 0.9em;
            display: none; /* Hidden by default, shown when focused */
        }
        .password-requirements.show {
            display: block;
        }
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #4a5568;
            font-size: 0.95rem;
        }
        .requirement-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            color: #718096;
            transition: all 0.2s;
        }
        .requirement-item i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        
        /* Valid State */
        .requirement-item.valid {
            color: #38a169;
            font-weight: 600;
        }
        .requirement-item.valid .fa-circle-check { display: inline-block; }
        .requirement-item.valid .fa-circle-xmark { display: none; }
        
        /* Invalid State */
        .requirement-item.invalid {
            color: #e53e3e;
        }
        .requirement-item.invalid .fa-circle-check { display: none; }
        .requirement-item.invalid .fa-circle-xmark { display: inline-block; }

        /* Default (Empty) State */
        .requirement-item .fa-circle-check { display: none; }
        .requirement-item .fa-circle-xmark { display: inline-block; color: #cbd5e0; }

        /* Disabled Button State */
        .login-btn:disabled {
            background-color: #cbd5e0;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none !important; /* Prevent hover animations */
            box-shadow: none;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-print"></i></div>
                <span>PRINTEASE</span>
            </div>
            <h2>Print your designs, effortlessly and fast, with the easiest online printing tool.</h2>
        </div>

        <div class="right-section">
            <div class="form-container-wrapper">
                
                <!-- LOGIN FORM -->
                <div class="login-box" id="loginForm">
                    <h1>Welcome Back</h1>
                    <p>Sign in to access your print and design orders.</p>
                    <form method="POST">
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($submitted_email); ?>" required>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password" id="password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <button type="submit" name="login" class="login-btn">Log In</button>
                        <a onclick="showForgotPasswordForm()" class="forgot-password">Forgot Password?</a>
                        <div class="form-footer">Don't have an account? <a onclick="showRegisterForm()">CREATE NOW</a></div>
                    </form>
                </div>
                
                <!-- REGISTER FORM -->
                <div class="login-box form-hidden" id="registerForm">
                    
                    <!-- STAGE 1: DETAILS INPUT -->
                    <?php if($register_stage === 'input'): ?>
                        <h1>Create Your Account</h1>
                        <p>It's quick and easy to start printing with us.</p>
                        <form method="POST" id="registerFormElement">
                            <div class="input-group"><input type="text" name="name" id="regName" placeholder="Full Name" required></div>
                            <div class="input-group"><input type="email" name="email" placeholder="Email Address" required></div>
                            <div class="input-group">
                                <input type="password" name="password" placeholder="New Password" id="regPassword" required>
                                <i class="fas fa-eye password-toggle" id="toggleRegPassword"></i>
                            </div>
                            
                            <!-- Dynamic Password Requirements -->
                            <div class="password-requirements" id="passwordRequirements">
                                <h4>Your password must:</h4>
                                <div class="requirement-item" id="req-length">
                                    <i class="fas fa-circle-check"></i><i class="fas fa-circle-xmark"></i><span>Be at least 8 characters</span>
                                </div>
                                <div class="requirement-item" id="req-uppercase">
                                    <i class="fas fa-circle-check"></i><i class="fas fa-circle-xmark"></i><span>Include an uppercase letter</span>
                                </div>
                                <div class="requirement-item" id="req-number">
                                    <i class="fas fa-circle-check"></i><i class="fas fa-circle-xmark"></i><span>Include a number</span>
                                </div>
                                <div class="requirement-item" id="req-symbol">
                                    <i class="fas fa-circle-check"></i><i class="fas fa-circle-xmark"></i><span>Include a symbol</span>
                                </div>
                            </div>

                            <button type="submit" name="register_init" class="login-btn" id="regSubmitBtn" disabled>Sign Up</button>
                            <div class="form-footer"><a onclick="showLoginForm()">Already have an account? Log In</a></div>
                        </form>

                    <!-- STAGE 2: OTP VERIFICATION -->
                    <?php elseif($register_stage === 'verify'): ?>
                        <h1>Verify Account</h1>
                        <p>Enter the 6-digit code sent to <b><?php echo htmlspecialchars($_SESSION['temp_user']['email'] ?? ''); ?></b></p>
                        <form method="POST">
                            <div class="otp-container">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" required onkeyup="moveFocus(this, 'reg_otp2')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="reg_otp2" required onkeyup="moveFocus(this, 'reg_otp3')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="reg_otp3" required onkeyup="moveFocus(this, 'reg_otp4')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="reg_otp4" required onkeyup="moveFocus(this, 'reg_otp5')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="reg_otp5" required onkeyup="moveFocus(this, 'reg_otp6')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="reg_otp6" required>
                            </div>
                            <button type="submit" name="register_verify" class="login-btn">Verify & Create</button>
                            <div class="form-footer"><a href="login.php?cancel_register=1">Cancel / Back</a></div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- FORGOT PASSWORD FORM -->
                <div class="login-box form-hidden" id="forgotPasswordForm">
                    <h1>Reset Password</h1>
                    
                    <!-- STAGE 1: EMAIL INPUT -->
                    <?php if($reset_stage === 'email_input'): ?>
                        <p>Enter your email to receive a verification code.</p>
                        <form method="POST">
                            <div class="input-group">
                                <input type="email" name="reset_email" placeholder="Email Address" required>
                            </div>
                            <button type="submit" name="send_otp" class="login-btn">Send Code</button>
                            <div class="form-footer"><a onclick="showLoginForm()">Back to Log In</a></div>
                        </form>

                    <!-- STAGE 2: OTP INPUT -->
                    <?php elseif($reset_stage === 'otp_input'): ?>
                        <p>Enter the 6-digit code sent to <b><?php echo htmlspecialchars($_SESSION['reset_email']); ?></b></p>
                        <form method="POST">
                            <div class="otp-container">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" required onkeyup="moveFocus(this, 'otp2')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="otp2" required onkeyup="moveFocus(this, 'otp3')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="otp3" required onkeyup="moveFocus(this, 'otp4')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="otp4" required onkeyup="moveFocus(this, 'otp5')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="otp5" required onkeyup="moveFocus(this, 'otp6')">
                                <input type="text" name="otp_digit[]" maxlength="1" class="otp-field" id="otp6" required>
                            </div>
                            <button type="submit" name="verify_otp" class="login-btn">Verify Code</button>
                            <div class="form-footer"><a href="login.php?cancel_reset=1">Cancel</a></div>
                        </form>

                    <!-- STAGE 3: NEW PASSWORD -->
                    <?php elseif($reset_stage === 'password_input'): ?>
                        <p>Create a new secure password.</p>
                        <form method="POST" id="resetPasswordFormElement">
                            <div class="input-group">
                                <input type="password" name="new_password" placeholder="New Password" id="resetNewPassword" required>
                                <i class="fas fa-eye password-toggle" id="toggleResetNewPassword"></i>
                            </div>
                            <div class="input-group">
                                <input type="password" name="confirm_password" placeholder="Confirm Password" id="resetConfirmPassword" required>
                                <i class="fas fa-eye password-toggle" id="toggleResetConfirmPassword"></i>
                            </div>
                            <button type="submit" name="do_reset_password" class="login-btn">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Custom Alert Modal -->
    <div id="customAlertModal">
        <div class="modal-content">
            <i class="fas modal-icon" id="alertIcon"></i>
            <div class="modal-message" id="alertMessage"></div>
            <button class="modal-button" onclick="closeAlert()">OK</button>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        function closeAlert() {
            document.getElementById('customAlertModal').classList.remove('show');
        }
        
        function showAlert(message, type = 'error') {
            const modal = document.getElementById('customAlertModal');
            const icon = document.getElementById('alertIcon');
            const messageElement = document.getElementById('alertMessage');
            messageElement.textContent = message;
            
            icon.className = 'fas modal-icon'; 
            if (type === 'success') {
                icon.classList.add('fa-circle-check');
                icon.style.color = '#38a169'; 
            } else {
                icon.classList.add('fa-circle-exclamation');
                icon.style.color = '#e53e3e';
            }
            modal.classList.add('show');
        }
        
        function hideAllForms() {
            document.getElementById('loginForm').classList.add('form-hidden');
            document.getElementById('registerForm').classList.add('form-hidden');
            document.getElementById('forgotPasswordForm').classList.add('form-hidden');
        }
        function showLoginForm() { hideAllForms(); document.getElementById('loginForm').classList.remove('form-hidden'); }
        function showRegisterForm() { hideAllForms(); document.getElementById('registerForm').classList.remove('form-hidden'); }
        function showForgotPasswordForm() { hideAllForms(); document.getElementById('forgotPasswordForm').classList.remove('form-hidden'); }

        window.showLoginForm = showLoginForm;
        window.showRegisterForm = showRegisterForm;
        window.showForgotPasswordForm = showForgotPasswordForm;

        // OTP Focus Helper
        window.moveFocus = function(current, nextFieldId) {
            if (current.value.length >= 1) document.getElementById(nextFieldId).focus();
        }

        // Toggle Password Logic
        document.querySelectorAll('.password-toggle').forEach(item => {
            item.addEventListener('click', function() {
                let input = this.previousElementSibling;
                input.type = input.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        // ---------------------------------------------------------
        // DYNAMIC PASSWORD VALIDATION LOGIC
        // ---------------------------------------------------------
        const regPasswordInput = document.getElementById('regPassword');
        const requirementsBox = document.getElementById('passwordRequirements');
        const regSubmitBtn = document.getElementById('regSubmitBtn');
        
        if(regPasswordInput) {
            regPasswordInput.addEventListener('focus', function() {
                requirementsBox.classList.add('show');
            });

            regPasswordInput.addEventListener('input', function() {
                const val = this.value;
                const reqs = {
                    'req-length': val.length >= 8,
                    'req-uppercase': /[A-Z]/.test(val),
                    'req-number': /[0-9]/.test(val),
                    'req-symbol': /[!@#$%^&*(),.?":{}|<>]/.test(val) // Checks for basic symbols
                    // Alternative regex for ANY non-word char: /[\W_]/.test(val)
                };

                for (const [id, isValid] of Object.entries(reqs)) {
                    const el = document.getElementById(id);
                    if (isValid) {
                        el.classList.add('valid');
                        el.classList.remove('invalid');
                    } else {
                        // Only show invalid (red) if they have started typing something
                        if (val.length > 0) {
                            el.classList.add('invalid');
                        } else {
                            el.classList.remove('invalid');
                        }
                        el.classList.remove('valid');
                    }
                }

                // Enable/Disable Submit Button based on ALL requirements being met
                const allValid = Object.values(reqs).every(Boolean);
                if(regSubmitBtn) {
                    regSubmitBtn.disabled = !allValid;
                }
            });
        }

        // Server Message Handling
        const serverMsg = "<?php echo addslashes($server_message); ?>";
        const resetStage = "<?php echo $reset_stage; ?>";
        const registerStage = "<?php echo $register_stage; ?>";
        const regSuccess = "<?php echo isset($_SESSION['reg_success']) ? $_SESSION['reg_success'] : ''; unset($_SESSION['reg_success']); ?>";
        const resetSuccess = "<?php echo isset($_SESSION['reset_success']) ? $_SESSION['reset_success'] : ''; unset($_SESSION['reset_success']); ?>";
        const serverMsgType = "<?php echo isset($server_msg_type) ? $server_msg_type : 'error'; ?>";

        // Handle showing messages
        if (resetSuccess) {
            showAlert(resetSuccess, 'success');
            showLoginForm();
        } else if (regSuccess) {
            showAlert(regSuccess, 'success');
            showLoginForm();
        } else if (serverMsg) {
            showAlert(serverMsg, serverMsgType);
            
            // Logic to keep correct form open if there was an error/msg
            if (registerStage === 'verify') {
                showRegisterForm();
            } else if (resetStage !== 'email_input') {
                showForgotPasswordForm();
            } else if (serverMsg.includes('registered')) {
                // If msg says "Email already registered", show register form again or login
                showRegisterForm(); 
            }
        }
        
        // State Persistence on Load
        if (registerStage === 'verify') {
            showRegisterForm();
        } else if (resetStage !== 'email_input') {
            showForgotPasswordForm();
        }
    </script>
</body>
</html>