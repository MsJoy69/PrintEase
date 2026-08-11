<?php
session_start();

// Define base URL
$baseUrl = '/systemcutie/'; 
$componentPath = $baseUrl . 'components/';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// ============================================================================
// AJAX HANDLER: MARK NOTIFICATIONS READ
// This block handles the POST request from the client-side to update the DB.
// It must run before any HTML output.
// ============================================================================
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    
    include("db.php"); // Assuming db.php is available in the current directory or included paths
    $user_id = $_SESSION['user_id'];
    $response = ['success' => false];

    // Database connection setup (reusing logic for the 'notification' database)
    $notificationDB = new mysqli("localhost", "root", "", "notification");

    if (!$notificationDB->connect_error) {
        // SQL to mark ALL unread notifications for the user as 'read'
        $sql = "UPDATE notifications SET status = 'read' WHERE user_id = ? AND status != 'read'";
        $stmt = $notificationDB->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $response = ['success' => $success, 'message' => $success ? 'Notifications marked as read.' : 'Update failed.'];
            $stmt->close();
        } else {
            error_log("SQL prepare failed: " . $notificationDB->error);
            $response['message'] = 'Internal error during SQL preparation.';
        }
        
        $notificationDB->close();
    } else {
        error_log("Notification DB connection failed: " . $notificationDB->connect_error);
        $response['message'] = 'Database connection failed.';
    }
    
    // Respond to the AJAX call and terminate script execution
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); 
}

// ============================================================================
// LANDING PAGE - IF NOT LOGGED IN (Guest View)
// ============================================================================
if (!$isLoggedIn) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintEase - Your Printing Solution</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=2">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
       
        /* Hamburger Menu Styles */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: #2f6f56;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        .sidebar {
            transition: transform 0.3s ease;
        }

        .logout { 
            padding: 20px; 
            border-top: 1px solid rgba(255,255,255,0.1); 
            text-align: center; 
        }
        .logout a { 
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            display: block; 
            padding: 10px; 
            border-radius: 8px; 
            transition: background 0.3s;
        }
        .logout a:hover { 
            background: rgba(255,255,255,0.1); 
        }

        .main-content { 
            margin-left: 260px; 
            flex: 1; 
            padding: 40px 30px 30px 30px; 
            transition: margin-left 0.3s ease;
        }
        .topbar { 
            background: white; 
            padding: 20px 30px; 
            border-radius: 15px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px;
            margin-top: 20px;
            gap: 15px;
        }
        .search-box { 
            flex: 1; 
            max-width: 500px; 
            position: relative; 
        }
        .search-box input { 
            width: 100%; 
            padding: 12px 20px 12px 45px; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 15px; 
            transition: all 0.3s ease;
        }
        .search-box input:focus { 
            outline: none; 
            border-color: #2f6f56; 
            box-shadow: 0 0 0 3px rgba(47,111,86,0.1); 
        }
        .search-box i { 
            position: absolute; 
            left: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #94a3b8; 
            font-size: 16px; 
        }
        .auth-buttons { 
            display: flex; 
            gap: 15px;
        }
        .btn { 
            padding: 12px 30px; 
            border: none; 
            border-radius: 10px; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-decoration: none; 
            display: inline-block;
            white-space: nowrap;
        }
        .btn-login { 
            background: #2f6f56; 
            color: white; 
        }
        .btn-login:hover { 
            background: #255945; 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(47,111,86,0.3); 
        }
        .btn-signup { 
            background: white; 
            color: #2f6f56; 
            border: 2px solid #2f6f56; 
        }
        .btn-signup:hover { 
            background: #2f6f56; 
            color: white; 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(47,111,86,0.3); 
        }
        .hero { 
            background: linear-gradient(135deg, #e8eef0 0%, #d4dfe3 100%); 
            padding: 80px 30px; 
            border-radius: 20px; 
            margin-bottom: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            text-align: center; 
        }
        .hero h1 { 
            font-size: 48px; 
            margin-bottom: 20px; 
            font-weight: 700; 
            color: #2d3748;
        }
        .hero p { 
            font-size: 20px; 
            margin-bottom: 40px; 
            line-height: 1.6; 
            color: #4a5568; 
            max-width: 800px; 
            margin-left: auto; 
            margin-right: auto;
        }
        .hero-buttons { 
            display: flex; 
            gap: 15px; 
            justify-content: center; 
            align-items: center; 
            flex-wrap: wrap;
        }
        .hero-cta { 
            padding: 16px 40px; 
            background: #2f6f56; 
            color: white; 
            border: none; 
            border-radius: 50px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease;
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            text-decoration: none; 
        }
        .hero-cta:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(47,111,86,0.3); 
            background: #3d7f66; 
        }
        .hero-cta-secondary { 
            padding: 16px 40px; 
            background: transparent; 
            color: #2f6f56; 
            border: 2px solid #2f6f56; 
            border-radius: 50px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            text-decoration: none;
        }
        .hero-cta-secondary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(47,111,86,0.2); 
            background: rgba(47,111,86,0.05); 
        }
        .features { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 40px; 
        }
        .feature-card { 
            background: white; 
            padding: 35px; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
            transition: all 0.3s ease; 
        }
        .feature-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
        }
        .feature-icon { 
            width: 60px; 
            height: 60px; 
            background: linear-gradient(135deg, #2f6f56 0%, #3b8e6a 100%); 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 20px; 
        }
        .feature-icon i { 
            font-size: 28px; 
            color: white; 
        }
        .feature-card h3 { 
            font-size: 22px; 
            margin-bottom: 12px; 
            color: #1f4d3a; 
        }
        .feature-card p { 
            color: #64748b; 
            line-height: 1.6; 
            font-size: 15px; 
        }
        .services { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
        }
        .services h2 { 
            font-size: 32px; 
            margin-bottom: 30px; 
            color: #1f4d3a; 
        }
        .service-list { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
        }
        .service-item { 
            padding: 25px; 
            background: #f8fafc; 
            border-radius: 12px; 
            border-left: 4px solid #2f6f56; 
            transition: all 0.3s ease; 
        }
        .service-item:hover { 
            background: #f1f5f9; 
            transform: translateX(5px); 
        }
        .service-item i { 
            font-size: 24px; 
            color: #2f6f56; 
            margin-bottom: 10px; 
            display: block; 
        }
        .service-item h4 { 
            font-size: 18px; 
            margin-bottom: 8px; 
            color: #1f4d3a; 
        }
        .service-item p { 
            color: #64748b; 
            font-size: 14px; 
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .hamburger {
                display: flex;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-top: 80px;
            }

            .topbar {
                flex-direction: row;
                justify-content: flex-end;
                padding: 15px;
            }

            .auth-buttons {
                width: auto;
            }

            .hero {
                padding: 60px 20px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .services {
                padding: 30px 20px;
            }

            .services h2 {
                font-size: 24px;
            }

            .service-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 24px;
            }

            .hero p {
                font-size: 14px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }

            .hero-cta, .hero-cta-secondary {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="<?php echo $baseUrl; ?>image/logo.png" alt="Printease Logo"> 
            <h2>PRINTEASE</h2>
        </div>

        <ul class="menu">
            <li><a href="<?php echo $componentPath; ?>login.php" class="active menu-item"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
            <li><a href="<?php echo $componentPath; ?>login.php" class="menu-item"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
            <li><a href="<?php echo $componentPath; ?>login.php" class="menu-item"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
            <li><a href="<?php echo $componentPath; ?>login.php" class="menu-item"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
            <li><a href="<?php echo $componentPath; ?>login.php" class="menu-item"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
            <li><a href="<?php echo $componentPath; ?>login.php" class="menu-item"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
        </ul>

        <div class="logout">
            <a href="<?php echo $componentPath; ?>login.php" id="sidebarLogoutTrigger">Signup</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div style="flex: 1;"></div>
            <div class="auth-buttons">
                <a href="<?php echo $componentPath; ?>login.php" class="btn btn-login">LOGIN</a>
                <a href="<?php echo $componentPath; ?>login.php" class="btn btn-signup">SIGNUP</a>
            </div>
        </div>

        <div class="hero">
            <h1>Printease: Your Fast and Easy Printing Partner</h1>
            <p>Get custom prints, stickers, and Magazines designed.</p>
            <div class="hero-buttons">
                <a href="<?php echo $componentPath; ?>login.php" class="hero-cta">Get Started Now</a>
                <a href="<?php echo $componentPath; ?>login.php" class="hero-cta-secondary">View Products</a>
            </div>
        </div>

        <div class="services">
            <h2>Our Services</h2>
            <div class="service-list">
                <div class="service-item">
                    <i class="fa-solid fa-image"></i>
                    <h4>Photo Printing</h4>
                    <p>High-quality prints from ₱10</p>
                </div>
                <div class="service-item">
                    <i class="fa-solid fa-certificate"></i>
                    <h4>Lamination</h4>
                    <p>Protect your prints from ₱15</p>
                </div>
                <div class="service-item">
                    <i class="fa-solid fa-tag"></i>
                    <h4>Custom Stickers</h4>
                    <p>Die-cut stickers from ₱20</p>
                </div>
                <div class="service-item">
                    <i class="fa-solid fa-envelope"></i>
                    <h4>Postcards</h4>
                    <p>Premium postcards from ₱30</p>
                </div>
                <div class="service-item">
                    <i class="fa-solid fa-book"></i>
                    <h4>Magazines</h4>
                    <p>Professional binding from ₱150</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hamburger Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');

        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('active')) {
                hamburger.classList.remove('active');
                sidebar.classList.remove('active');
            }
        });

        // Close sidebar when clicking a menu item on mobile
        const menuItems = document.querySelectorAll('.sidebar .menu a');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    hamburger.classList.remove('active');
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
<?php
    exit();
}

// ============================================================================
// DASHBOARD - IF LOGGED IN (Authenticated User View)
// ============================================================================

include("db.php");

$user_id = $_SESSION['user_id'];
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

// Get recent orders
$paymentDB = new mysqli("localhost", "root", "", "payment");
if ($paymentDB->connect_error) {
    error_log("Payment DB connection failed: " . $paymentDB->connect_error);
    $recentOrders = [];
} else {
    $sql = "SELECT order_id, order_type, status FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 4";
    $stmt = $paymentDB->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentOrders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $paymentDB->close();
}

// Get notifications
$notificationDB = new mysqli("localhost", "root", "", "notification");
$latestNotifications = [];
$unreadCount = 0; // Initialize unread count

if ($notificationDB->connect_error) {
    error_log("Notification DB connection failed: " . $notificationDB->connect_error);
} else {
    // Query 1: Get unread count for the badge
    $countSql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status != 'read'";
    $countStmt = $notificationDB->prepare($countSql);
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $unreadCount = $countResult->fetch_assoc()['unread_count'] ?? 0;
    $countStmt->close();

    // Query 2: Get latest notifications for the dashboard list (regardless of read status)
    $sql = "SELECT message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3";
    $stmt = $notificationDB->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $type = (strpos($row['message'], 'Completed') !== false) ? 'success' : 'info';

        $timezone = new DateTimeZone('Asia/Manila');
        
        $now = new DateTime('now', $timezone);        
        $notificationTime = new DateTime($row['created_at'], $timezone);    

        $interval = $now->diff($notificationTime);    

        if ($interval->y > 0) {
            $time_ago = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
        } elseif ($interval->m > 0) {
            $time_ago = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d > 0) {
            $time_ago = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        } elseif ($interval->h > 0) {
            $time_ago = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } elseif ($interval->i > 0) {
            $time_ago = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } else {
            $time_ago = 'Just now';
        }

        $latestNotifications[] = [
            'message' => $row['message'],
            'type' => $type,
            'time' => $time_ago
        ];
    }

    $stmt->close();
    $notificationDB->close();
}

// Profile image
$placeholderImage = "https://via.placeholder.com/30/2f6f56/ffffff?text=U";
$profileImage = $placeholderImage;
$sessionProfilePic = $_SESSION['profile_pic'] ?? '';

if (!empty($sessionProfilePic)) {
    $rawPath = $sessionProfilePic;
    if (strpos($rawPath, 'http://') === false && strpos( $rawPath, 'https://') === false) {
        $profileImage = $baseUrl . $rawPath;
    } else {
        $profileImage = $rawPath;
    }
}

// Featured products
$featuredProducts = [
    ['name' => 'Print', 'price' => 10, 'link' => 'product.php?p=cards'],
    ['name' => 'Laminate Picture', 'price' => 15, 'link' => 'product.php?p=posters'],
    ['name' => 'Sticker', 'price' => 20, 'link' => 'product.php?p=sticker'],
    ['name' => 'Post Card', 'price' => 30, 'link' => 'postcard.php?p=postcard'],
    ['name' => 'Magazine', 'price' => 150, 'link' => 'magazine.php?p=magazine'],
];

// Product keywords used by JS search function
$productKeywords = [
    'sticker' => 'sticker',
    'laminate' => 'laminate',
    'print' => 'printpic', 
    'picture' => 'printpic', 
    'profile' => 'profile',
    'order' => 'order', 
    'postcard' => 'postcard',
    'magazine' => 'magazine',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Printease - Customer Dashboard</title>
<link rel="stylesheet" href="style.css?v=2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    /* Hamburger Menu Styles */
    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 10px;
        z-index: 1001;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: fixed;
        top: 20px;
        left: 20px;
    }

    /* Notification Icon Styles */
    .notification-icon {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: #f1f5f9;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        margin-right: 15px;
    }

    .notification-icon:hover {
        background: #e2e8f0;
        transform: scale(1.05);
    }

    .notification-icon i {
        font-size: 20px;
        color: #2f6f56;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 11px;
        font-weight: 700;
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
    }
    
    /* Hide badge if count is 0 */
    .notification-badge[data-count="0"], .notification-badge:empty {
        display: none;
    }


    .top-buttons {
        display: flex;
        align-items: center;
    }

    .hamburger span {
        width: 25px;
        height: 3px;
        background: #2f6f56;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -7px);
    }

    .sidebar {
        transition: transform 0.3s ease;
    }

    /* Product Not Found Modal Styling */
    #productNotFoundModal .modal-content {
        max-width: 450px;
        background-color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: fadeInDown 0.3s ease-out;
    }

    #productNotFoundModal .error-icon {
        font-size: 3.5rem;
        color: #2f6f56;
        margin-bottom: 20px;
        display: block;
    }

    #productNotFoundModal h2 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
    }

    #productNotFoundModal p {
        color: #64748b;
        margin-bottom: 25px;
        line-height: 1.5;
    }
    
    #productNotFoundModal strong {
        color: #fff;
        font-weight: 600;
        background-color: #3b8e6a;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.9em;
    }

    #productNotFoundModal .modal-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    #productNotFoundModal .btn-close-modal,
    #productNotFoundModal .btn-browse-product {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    #productNotFoundModal .btn-close-modal {
        background: #e2e8f0;
        color: #475569;
    }

    #productNotFoundModal .btn-close-modal:hover {
        background: #cbd5e1;
    }

    #productNotFoundModal .btn-browse-product {
        background: #2f6f56;
        color: white;
    }

    #productNotFoundModal .btn-browse-product:hover {
        background: #255945;
        transform: translateY(-1px);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .hamburger {
            display: flex;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transform: translateX(-100%);
            overflow-y: auto;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
            padding-top: 80px;
        }

        .topbar {
            flex-wrap: wrap;
            padding: 15px;
        }

        .search-box {
            order: 2;
            max-width: 100%;
            margin-top: 15px;
        }

        .top-buttons {
            order: 1;
        }

        .dashboard-banner {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }

        .dashboard-banner h2 {
            font-size: 1.4rem !important;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .orders-table {
            font-size: 14px;
        }

        .orders-table th,
        .orders-table td {
            padding: 10px 5px;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 15px;
            padding-top: 70px;
        }

        .dashboard-banner h2 {
            font-size: 1.2rem !important;
        }

        .dashboard-banner p {
            font-size: 0.9rem;
        }

        .banner-cta {
            padding: 10px 20px;
            font-size: 14px;
        }

        .card-panel {
            padding: 20px;
        }

        .card-panel h3 {
            font-size: 1.1rem;
        }

        .orders-table {
            font-size: 12px;
        }

        .product-summary-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>
</head>
<body>

<!-- Hamburger Menu -->
<div class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="<?php echo $baseUrl; ?>image/logo.png" alt="Printease Logo">
        <h2>PRINTEASE</h2>
    </div>
    

    <ul class="menu">
        <li><a href="<?php echo $baseUrl; ?>index.php" class="active"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
        <li><a href="<?php echo $componentPath; ?>product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
        <li><a href="<?php echo $componentPath; ?>order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
        <li><a href="<?php echo $componentPath; ?>notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
        <li><a href="<?php echo $componentPath; ?>message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
        <li><a href="<?php echo $componentPath; ?>profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
    </ul>

    <div class="logout">
        <a href="<?php echo $baseUrl; ?>components/logout.php" id="sidebarLogoutTrigger">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search orders, products, or profile..." autocomplete="off">
            
            <div class="search-dropdown" id="searchDropdown">
                <div id="searchHistoryList"></div>
                
                <div class="dropdown-section-title">Popular Suggestions</div>
                <a class="dropdown-item suggestion-item" data-query="Sticker Order" href="#">
                    <i class="fa-solid fa-fire"></i> Sticker Order
                </a>
                <a class="dropdown-item suggestion-item" data-query="Recent Order Status" href="#">
                    <i class="fa-solid fa-truck"></i> Recent Order Status
                </a>
                <a class="dropdown-item suggestion-item" data-query="Profile Details" href="#">
                    <i class="fa-solid fa-user"></i> Profile Details
                </a>
            </div>
        </div>

        <div class="top-buttons">
            <!-- NOTIFICATION ICON: Display count based on $unreadCount -->
            <a href="<?php echo $componentPath; ?>notifications.php" class="notification-icon" id="notificationIcon">
                <i class="fa-solid fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" data-count="<?php echo $unreadCount; ?>"><?php echo $unreadCount; ?></span>
            </a>
            <div class="account-dropdown">
                <div class="account-trigger">
                    <span><?php echo htmlspecialchars($displayName); ?></span>
                </div>
                
                <div class="dropdown-menu">
                    <a href="<?php echo $componentPath; ?>profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                    <a href="<?php echo $componentPath; ?>product.php"><i class="fa-solid fa-box"></i> Product Catalog</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo $baseUrl; ?>components/logout.php" class="logout-link">
                        <i class="fa-solid fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-banner">
        <div>
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Welcome to Printease, <?php echo htmlspecialchars($displayName); ?>!</h2>
            <p style="opacity: 0.9;">Your all-in-one shop for custom prints, stickers, and more. Let's create something amazing today!</p>
        </div>
        <a href="<?php echo $componentPath; ?>product.php" class="banner-cta">
            Place New Order <i class="fa-solid fa-plus-circle"></i>
        </a>
    </div>

    <div class="dashboard-grid">
        
        <div class="card-panel orders-tracking">
            <h3>Your Recent Orders (Tracking)</h3>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($order['status']);
                                        $statusClass = strtolower($status);
                                        echo "<span class='status $statusClass'>" . $status . "</span>";
                                    ?>
                                </td>
                                <td><a href="<?php echo $componentPath; ?>order.php?id=<?php echo $order['order_id']; ?>" style="color: #2f6f56; text-decoration: none; font-weight: 500;">Track</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #777;">You have not placed any orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                   <a href="<?php echo $componentPath; ?>order.php" class="small-link" style="color: #2f6f56; text-decoration: underline; font-size: 0.9rem;">View All Orders <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="card-panel notifications-summary">
            <h3>Latest Notifications</h3>
            <div class="notif-list">
                <?php if (count($latestNotifications) > 0): ?>
                    <?php foreach ($latestNotifications as $notification): ?>
                        <div class="notif-item">
                            <strong class="text-<?= $notification['type'] ?>"><?php echo htmlspecialchars($notification['message']); ?></strong>
                            <small><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($notification['time']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777; font-size: 0.9rem;">No new alerts.</p>
                <?php endif; ?>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                   <a href="<?php echo $componentPath; ?>notifications.php" class="small-link" style="color: #2f6f56; text-decoration: underline; font-size: 0.9rem;">View All Alerts <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="card-panel featured-products-summary">
            <h3>Featured Products</h3>
            <div class="product-list">
                <?php foreach ($featuredProducts as $product): ?>
                    <a href="<?php echo $componentPath . $product['link']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-summary-item">
                            <span class="product-info"><i class="fa-solid fa-tag" style="margin-right: 5px; color: #3b8e6a;"></i><?php echo htmlspecialchars($product['name']); ?></span>
                            <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                   <a href="<?php echo $componentPath; ?>product.php" class="small-link" style="color: #2f6f56; text-decoration: underline; font-size: 0.9rem;">Browse Catalog <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="logoutModal">
    <div class="logout-modal-content">
        <i class="fa-solid fa-door-open logout-icon"></i>
        <h2>Are you sure you want to log out?</h2>
        <div class="modal-actions">
            <a href="<?php echo $baseUrl; ?>components/logout.php" class="btn-yes-logout">Yes, Log Out</a>
            <button class="btn-no-cancel" id="cancelLogout">No, Stay</button>
        </div>
    </div>
</div>

<!-- Product Not Found Modal -->
<div class="modal-overlay" id="productNotFoundModal" style="display: none;">
    <div class="modal-content">
        <h2>NO PRODUCT FOUND</h2>
        <p>We currently do not have a product matching '<strong><span id="notFoundQuery"></span></strong>'.</p>
        <div class="modal-actions">
            <button class="btn-close-modal" id="closeNotFoundModal">Close</button>
            <a href="<?php echo $componentPath; ?>product.php" class="btn-browse-product">Browse Products</a>
        </div>
    </div>
</div>

<script>
    const COMPONENT_PATH = '<?php echo $componentPath; ?>';
    const BASE_URL = '<?php echo $baseUrl; ?>'; // Define base URL for fetch target
    const PRODUCT_KEYWORDS = <?php echo json_encode($productKeywords); ?>;
    

    
    document.addEventListener('DOMContentLoaded', function() {

        // Hamburger Menu Toggle
		const hamburger = document.getElementById('hamburger');
		const sidebar = document.getElementById('sidebar');

		if (hamburger && sidebar) {
			hamburger.addEventListener('click', function() {
				hamburger.classList.toggle('active');
				sidebar.classList.toggle('active');
			});

			// Close sidebar when clicking outside
			document.addEventListener('click', function(e) {
				if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('active')) {
					hamburger.classList.remove('active');
					sidebar.classList.remove('active');
				}
			});

			// Close sidebar when clicking a menu item on mobile
			const menuItems = document.querySelectorAll('.sidebar .menu a');
			menuItems.forEach(item => {
				item.addEventListener('click', function() {
					if (window.innerWidth <= 768) {
						hamburger.classList.remove('active');
						sidebar.classList.remove('active');
					}
				});
			});
		}
        // Modal/Logout Logic
        <?php if ($isLoggedIn): ?>
            const logoutModal = document.getElementById('logoutModal');
            const productNotFoundModal = document.getElementById('productNotFoundModal');
            const notFoundQuerySpan = document.getElementById('notFoundQuery');
            const closeNotFoundModalButton = document.getElementById('closeNotFoundModal');
            
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationBadge = document.getElementById('notificationBadge');

            // === NOTIFICATION LOGIC (AJAX TO INDEX.PHP IMPLEMENTED) ===
            if (notificationIcon) {
                notificationIcon.addEventListener('click', function(e) {
                    // 1. Prevent immediate navigation
                    e.preventDefault(); 
                    
                    const targetUrl = e.currentTarget.href; // Get the target navigation URL (notifications.php)
                    
                    // 2. Client-side: Immediately clear the badge
                    if (notificationBadge) {
                        notificationBadge.textContent = '0';
                        notificationBadge.style.display = 'none'; 
                    }
                    
                    // 3. Server-side: Send AJAX request to index.php to mark notifications as read
                    fetch(BASE_URL + 'index.php', { 
                        method: 'POST',
                        headers: {
                            // Important for PHP to recognize action=mark_read in $_POST
                            'Content-Type': 'application/x-www-form-urlencoded' 
                        },
                        body: 'action=mark_read' 
                    })
                    .then(response => {
                        // Check if the server response indicates success (200 OK)
                        if (response.ok) {
                             // 4. Navigate only after server processing
                            window.location.href = targetUrl; 
                        } else {
                            // Handle potential HTTP errors or server errors
                            console.error('Server error marking notifications as read:', response.statusText);
                            // Navigate anyway so the user can still see the page
                            window.location.href = targetUrl; 
                        }
                    })
                    .catch(error => {
                        // Handle network errors
                        console.error('Network error during notification update:', error);
                        // Navigate anyway
                        window.location.href = targetUrl;
                    });
                });
            }
            // === END OF NOTIFICATION LOGIC ===

            // Logout Modal Handlers
            const logoutTriggers = document.querySelectorAll('a[href*="components/logout.php"]');
            const cancelLogoutButton = document.getElementById('cancelLogout');
            
            logoutTriggers.forEach(trigger => {
                if (!trigger.classList.contains('btn-yes-logout')) {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        logoutModal.style.display = 'flex';
                    });
                }
            });

            cancelLogoutButton.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });

            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.style.display = 'none';
                }
            });
            
            // Product Not Found Modal Handler
            closeNotFoundModalButton.addEventListener('click', function() {
                productNotFoundModal.style.display = 'none';
                searchInput.focus();
            });

            productNotFoundModal.addEventListener('click', function(e) {
                if (e.target === productNotFoundModal) {
                    productNotFoundModal.style.display = 'none';
                    searchInput.focus();
                }
            });

            // Search Dropdown and History Logic
            const searchInput = document.getElementById('searchInput');
            const searchDropdown = document.getElementById('searchDropdown');
            const searchHistoryList = document.getElementById('searchHistoryList');
            const MAX_HISTORY = 3;
            const HISTORY_KEY = 'printease_search_history';

            function loadHistory() {
                const history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                searchHistoryList.innerHTML = '';

                if (history.length > 0) {
                    const title = document.createElement('div');
                    title.className = 'dropdown-section-title';
                    title.textContent = 'Recent Searches (Max ' + MAX_HISTORY + ')';
                    searchHistoryList.appendChild(title);

                    history.forEach(query => {
                        const item = document.createElement('a');
                        item.className = 'dropdown-item history';
                        item.href = '#';
                        item.setAttribute('data-query', query);
                        item.innerHTML = `<i class="fa-solid fa-history"></i> ${query}`;
                        
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            searchInput.value = query;
                            handleSearch(query);
                        });
                        searchHistoryList.appendChild(item);
                    });
                }
            }

            function saveToHistory(query) {
                query = query.trim();
                if (!query) return;

                let history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                
                history = history.filter(item => item.toLowerCase() !== query.toLowerCase());
                history.unshift(query);

                if (history.length > MAX_HISTORY) {
                    history = history.slice(0, MAX_HISTORY);
                }

                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
                loadHistory();
            }

            function handleSearch(query) {
                query = query.trim();
                if (!query) return;

                saveToHistory(query);
                searchDropdown.style.display = 'none';
                
                const lowerQuery = query.toLowerCase();
                let targetFile = '';
                
                // Check for product keyword match
                for (const [keyword, filename] of Object.entries(PRODUCT_KEYWORDS)) {
                    if (lowerQuery.includes(keyword)) {
                        targetFile = filename;
                        break;
                    }
                }

                if (targetFile) {
                    window.location.href = `${COMPONENT_PATH}${targetFile}.php`;
                } else {
                    notFoundQuerySpan.textContent = query;
                    productNotFoundModal.style.display = 'flex';
                }
            }

            searchInput.addEventListener('focus', function() {
                loadHistory();
                searchDropdown.style.display = 'block';
            });

            document.addEventListener('click', function(e) {
                const searchBox = document.querySelector('.search-box');
                if (!searchBox.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSearch(searchInput.value);
                }
            });

            document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const query = item.getAttribute('data-query');
                    searchInput.value = query;
                    handleSearch(query);
                });
            });

            loadHistory();
        <?php endif; ?>
    });
</script>

</body>
</html>