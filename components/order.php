<?php 
session_start();

// Define base URL for AJAX, assuming order.php is in components/
$baseUrl = '/systemcutie/'; 

// ===== Check if logged in =====
if(!isset($_SESSION['user_id'])){ // Check for user_id
    header("Location: login.php");
    exit();
}

// ============================================================================
// NOTIFICATION LOGIC BLOCK (Copied from index.php)
// Since this file is in components/, it must use relative paths for includes
// and define its own DB connections if needed.
// ============================================================================

// --- Start of Notification Data Fetch (Simplified for this component) ---
// Note: In a real system, the logic to fetch $unreadCount should be
// moved to a common function or included file. I am re-implementing it here.
$notificationDB = new mysqli("localhost", "root", "", "notification");
$unreadCount = 0; // Initialize unread count

if (!$notificationDB->connect_error) {
    $user_id = $_SESSION['user_id'];
    
    // Query 1: Get unread count for the badge
    $countSql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status != 'read'";
    $countStmt = $notificationDB->prepare($countSql);
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $unreadCount = $countResult->fetch_assoc()['unread_count'] ?? 0;
    $countStmt->close();
    
    $notificationDB->close();
} else {
    // If connection fails, count remains 0.
    error_log("Notification DB connection failed in order.php: " . $notificationDB->connect_error);
}
// --- End of Notification Data Fetch ---


// ===== DB CONNECTIONS =====
$infoDB = new mysqli("localhost", "root", "", "printease");

// ===== Connection Check =====
if($infoDB->connect_error){
    die("❌ Database connection error!");
}

// ===== Get logged-in user's details from session =====
$email = $_SESSION['user'];
$displayName = $_SESSION['user_name'] ?? $email;

// Define component path for internal links
$componentPath = '../components/'; 

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📦 My Order Tracking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../style.css?v=5">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    body { 
        background:#f8f9fa; 
        font-family:'Poppins', sans-serif; 
    }
    .order-card { 
        background:white; 
        border-radius:10px; 
        padding:20px; 
        margin-bottom:20px; 
        box-shadow:0 0 5px rgba(0,0,0,0.1);
    }
    .progress-step {
        display:inline-block; 
        padding:10px 15px; 
        border-radius:8px; 
        background:#e9ecef; 
        margin-right:8px; 
        font-weight:600;
    }
    .active {
        background:#0d6efd; 
        color:white;
    }
    .badge { 
        font-size: 0.7rem; 
    }
    .status-icon { 
        cursor:pointer; 
        transition: transform 0.2s; 
    }
    .status-icon:hover { 
        transform: scale(1.1); 
    }
    .selected-tab { 
        border-bottom:3px solid #0d6efd; 
        padding-bottom:4px; 
    }
    
    /* ✅ FIX: Order tracking content container */
    .order-content-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .order-header h3 {
        margin: 0;
        font-size: 1.75rem;
        color: #2f6f56;
    }
    
    .order-header .btn {
        padding: 8px 20px;
    }
    
    #status-icons {
        margin: 20px 0;
    }
    
    #orders-area {
        margin-top: 20px;
    }
    
    /* NOTIFICATION STYLES (Copied from index.php) */
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
</style>
</head>

<body>
<div class="sidebar">
    <div class="logo">
      <img src="../image/logo.png" alt="Printease Logo">
      <h2>PRINTEASE</h2>
    </div>

    <ul class="menu">
      <li><a href="../index.php"><i class="fa-solid fa-table-cells icon" style = "padding-right: 10px;"></i> <span class="label"> Dashboard</span></a></li>
      <li><a href="../components/product.php"><i class="fa-solid fa-box icon" style = "padding-right: 10px;"></i> <span class="label"> Product</span></a></li>
      <li><a href="../components/order.php" class="active"><i class="fa-solid fa-credit-card icon" style = "padding-right: 10px;"></i> <span class="label"> Orders</span></a></li>
      <li>
  <a href="../components/notifications.php">
    <i class="fa-solid fa-bell icon" style = "padding-right: 10px;"></i>
    <span class="label">Notifications</span>
    <span id="notifCount" class="notif-badge"></span>
  </a>
</li>
    <li><a href="../components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>

      <li><a href="../components/profile.php"><i class="fa-solid fa-user icon" style="padding-right: 10px;"></i> <span class="label"> Profile</span></a></li>
    </ul>

    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="topbar">
      <form method="GET" action="">
      <div class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="search" placeholder="Search...">
  </div>
    </form>

<?php
// Map keywords/variations to actual pages
$search_map = [
    "dashboard" => "dashboard/index.php",
    "home" => "dashboard/index.php",
    
    "product" => "components/product.php",
    "products" => "components/product.php",
    
    "orders" => "components/orders.php",
    "my orders" => "components/orders.php",
    
    "files" => "components/files.php",
    "documents" => "components/files.php",
    
    "profile" => "components/profile.php",
    "account" => "components/profile.php",
    
    "print" => "components/print.php",
    "printing" => "components/print.php",
    "photo print" => "components/print.php",
    
    "laminate" => "components/laminate.php",
    "lamination" => "components/laminate.php",
    
    "sticker" => "components/sticker.php",
    "stickers" => "components/sticker.php",
];

if (isset($_GET['search'])) {
    $search_input = strtolower(trim($_GET['search'])); // make case-insensitive

    if (array_key_exists($search_input, $search_map)) {
        // Correct pathing since order.php is in components/
        header("Location: ../" . $search_map[$search_input]); 
        exit();
    } else {
        $error_message = "Invalid search. Please use a valid keyword.";
    }
}
?>

    <div class="top-buttons">
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
					
					<a href="logout.php" class="logout-link">
						<i class="fa-solid fa-sign-out-alt"></i> Logout
					</a>
				</div>
			</div>

      </div>
    </div>

    <div class="order-content-wrapper">
        <div class="order-header">
            <h3>📦 My Order Tracking</h3>
            <a href="../index.php" class="btn" style="background-color: #3d7763; border-color: #3d7763; color: white;">
    <i class="fas fa-arrow-left me-2"></i>Back
</a>
        </div>

        <p>Welcome, <strong><?= htmlspecialchars($displayName) ?></strong></p>

        <div id="status-icons"></div>

        <div id="orders-area">
            <div class="alert alert-secondary">Loading your orders...</div>
        </div>
    </div>
  </div>

  <div class="logout-modal" id="logoutModal">
    <div class="logout-box">
      <i class="fa-solid fa-print"></i>
      
      <div class="logout-actions">
        <a href="logout.php">Logout</a>
        <button class="btn no" id="cancelLogout">no</button>
      </div>
    </div>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentStatus = "Pending"; // 🔹 Default main tab is Pending
let selectedStatus = "Pending"; // 🔹 Default selected is also Pending

// Define constants for JS use
const BASE_URL = '<?php echo $baseUrl; ?>';
const componentPath = '<?php echo $componentPath; ?>';


// ===== NOTIFICATION LOGIC (Copied from index.php) =====
document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationBadge = document.getElementById('notificationBadge');

    if (notificationIcon) {
        notificationIcon.addEventListener('click', function(e) {
            // 1. Prevent immediate navigation
            e.preventDefault(); 
            
            const targetUrl = e.currentTarget.href; // Get the target navigation URL (notifications.php)
            
            // 2. Client-side: Immediately clear the badge
            if (notificationBadge) {
                notificationBadge.textContent = '0';
                // Use the data attribute logic from index.php styles
                notificationBadge.setAttribute('data-count', '0'); 
            }
            
            // 3. Server-side: Send AJAX request to index.php to mark notifications as read
            // Target is BASE_URL/index.php (which is the root index.php)
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
});
// ===== END NOTIFICATION LOGIC =====

// ===== LOAD ORDER COUNTS =====
function loadCounts() {
    $.ajax({
        url: "fetch_counts.php",
        method: "GET",
        success: function(response) {
            $("#status-icons").html(response);

            // Re-apply selected-tab highlight after re-render
            $(".status-btn").removeClass("selected-tab");
            const btn = $("#status-icons").find(`.status-btn[data-status='${selectedStatus}']`);
            if (btn.length) btn.addClass("selected-tab");
        }
    });
}

// ===== LOAD ORDERS BY STATUS =====
function loadOrders(status = "Pending") {
    currentStatus = status;
    $.ajax({
        url: "fetch_orders.php",
        method: "GET",
        data: { status: status },
        success: function(response) {
            $("#orders-area").html(response);
        },
        error: function() {
            $("#orders-area").html("<div class='alert alert-danger text-center'>Error loading orders.</div>");
        }
    });
}

// ===== INITIAL LOAD =====
loadCounts();
loadOrders("Pending"); // 🔹 Default display is Pending orders

// ===== AUTO REFRESH EVERY 5 SECONDS =====
setInterval(() => {
    loadCounts();
    loadOrders(currentStatus);
}, 5000);

// ===== HANDLE ICON CLICK (Filtering) =====
$(document).on("click", ".status-btn", function() {
    const status = $(this).data("status");

    // 🔁 Toggle behavior — clicking same tab returns to Pending
    if (selectedStatus === status) {
        selectedStatus = "Pending";
        currentStatus = "Pending";
        $(".status-btn").removeClass("selected-tab");
        $("#status-icons").find(`.status-btn[data-status='Pending']`).addClass("selected-tab");
        loadOrders("Pending");
    } else {
        selectedStatus = status;
        currentStatus = status;
        $(".status-btn").removeClass("selected-tab");
        $(this).addClass("selected-tab");
        loadOrders(status);
    }
});

// ===== HANDLE MARK AS RECEIVED BUTTON CLICK (Status Update) =====
$(document).on("click", ".mark-received-btn", function() {
    // ✅ FIX: Grab the correct IDs from data attributes
    // data-id is the DATABASE ID (used for AJAX)
    // data-display-id is the PRETTY ID (used for the alert)
    const dbId = $(this).data("id"); 
    const displayId = $(this).data("display-id");
    
    const button = $(this);
    
    // ✅ FIX: Use displayId for the alert so it doesn't say #undefined
    if (confirm(`Are you sure you want to mark Order #${displayId} as Received?`)) {
        // Use .html() to handle the icon disappearance during loading
        button.html("Updating...").prop('disabled', true); 
        
        // AJAX request to update the database status via update_order_status.php
        $.ajax({
            url: "update_order_status.php", 
            method: "POST",
            data: { 
                order_id: dbId, // ✅ Send the actual Database ID to PHP
                new_status: 'Received' 
            },
            dataType: 'json', // Expecting JSON response
            success: function(response) {
                if (response.success) {
                    alert(`Order #${displayId} successfully marked as Received.`);
                    // Switch to the Received tab and reload data
                    selectedStatus = "Received";
                    currentStatus = "Received";
                    $(".status-btn").removeClass("selected-tab");
                    
                    // Reload counts/orders to instantly reflect the change
                    loadCounts(); 
                    loadOrders("Received");
                } else {
                    alert(`Failed to update status: ${response.message}`);
                    // Restore original button state
                    button.html('<i class="fas fa-box-open me-1"></i> Mark as Received').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert("Error marking order as received. Server or network issue.");
                // Restore original button state
                button.html('<i class="fas fa-box-open me-1"></i> Mark as Received').prop('disabled', false);
            }
        });
    }
});
</script>

</body>
</html>

<?php 
$infoDB->close();
?>