<?php
session_start();
// This include should point to your database connection file.
// Ensure 'db_connection.php' is correctly set up.
include 'db_connection.php'; 
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify the hashed password
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                // Redirect to the admin dashboard, e.g., 'index.php' in an 'admin' folder
                header('Location: index.php'); 
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
    } else {
        // Generic error for security
        $error = "An error occurred. Please try again later.";
    }
}
// It's good practice to close the connection, but do it after you are completely done with it.
// $conn->close(); // This might be closed too early if you have more logic below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintEase - Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Define Elegant Green Colors */
        :root {
            --printease-green: #38a169; /* Soft, professional green */
            --printease-green-dark: #2f855a;
            --link-color: #2b6cb0; 
            --light-bg: #f7f9fb;
            --white: #ffffff;
            --text-color: #2d3748;
            --input-border: #e2e8f0;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* General Reset and Body Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif; 
        }

        body {
            background-color: var(--light-bg); 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Main Container */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 90%;
            max-width: 1000px; 
            padding: 40px 20px;
        }

        /* Left Section: Logo and Slogan */
        .left-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            flex-basis: 55%;
            padding-right: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 60px; 
            color: var(--printease-green); 
            margin-right: 15px;
        }

        .logo span {
            font-size: 48px; 
            font-weight: 700;
            color: var(--printease-green-dark); 
            line-height: 1;
        }

        .left-section h2 {
            font-size: 26px;
            font-weight: 400;
            color: var(--text-color);
            line-height: 1.4;
        }

        /* Right Section: Form Box */
        .right-section {
            flex-basis: 45%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Form Box Styling */
        .login-box {
            width: 100%;
            max-width: 450px; 
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            padding: 30px;
        }

        .login-box h1 {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .login-box p {
            font-size: 15px;
            text-align: center;
            color: #718096;
            margin-bottom: 25px;
        }
        
        /* ALERT STYLES */
        .alert-danger {
            color: #721c24; 
            background-color: #f8d7da; 
            border-color: #f5c6cb; 
            padding: 10px 15px;
            margin-bottom: 20px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 14px;
            text-align: left;
        }

        /* Input Styles */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            padding-left: 50px; /* Space for icon */
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        /* Icon positioning inside input group */
        .input-group i:not(.password-toggle) {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0; 
            font-size: 18px;
            z-index: 10;
        }


        .input-group input:focus {
            border-color: var(--printease-green);
            box-shadow: 0 0 0 1px var(--printease-green);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--printease-green);
        }

        /* Login Button - Green for main action */
        .login-btn {
            width: 100%;
            padding: 12px 0;
            background-color: var(--printease-green); 
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 15px;
        }

        .login-btn:hover {
            background-color: var(--printease-green-dark);
            /* Removed unnecessary hover effects from original admin CSS */
            transform: none; 
            box-shadow: none; 
        }


        /* Media Query for Mobile (Single Column) */
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }

            .left-section {
                flex-basis: auto;
                padding-right: 0;
                margin-bottom: 30px;
                align-items: center;
                text-align: center;
            }
            
            .left-section h2 {
                font-size: 20px;
            }

            .logo span {
                font-size: 40px;
            }
            
            .right-section {
                flex-basis: auto;
                width: 100%;
            }

            .login-box {
                padding: 25px;
            }
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-print"></i>
                </div>
                <span>PRINTEASE</span>
            </div>
            <h2>System Administrator Portal. Manage and oversee all platform operations.</h2>
        </div>

        <div class="right-section">
            <div class="login-box" id="loginForm">
                <h1>Admin Access</h1>
                <p>Please sign in to manage the system.</p>
                
                <?php if ($error): ?>
                    <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="admin_login.php">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" id="password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>

                    <button type="submit" name="login" class="login-btn">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility for login
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>