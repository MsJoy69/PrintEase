<?php
session_start();

// ✅ Connect to the NOTIFICATION database
$conn = new mysqli("localhost", "root", "", "notification");
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// ⚠️ NEW: Connect to the PAYMENT database to fetch order details
$paymentConn = new mysqli("localhost", "root", "", "payment");
if($paymentConn->connect_error){
    die("Payment Connection failed: " . $paymentConn->connect_error);
}


// ✅ Make sure user_id exists in the session
if (!isset($_SESSION['user_id'])) {
    // If not logged in, show an error and stop
    die("You must be logged in to see notifications. <a href='login.php'>Login here</a>");
}

$user_id = $_SESSION['user_id'];

$email = $_SESSION['user'];
$displayName = $_SESSION['user_name'] ?? $email;

// ✅ Mark all unread notifications as read when user visits this page
$conn->query("UPDATE notifications SET status='read' WHERE user_id='$user_id' AND status='unread'");

// ✅ Fetch all notifications for the current user, grouped by order
$result = $conn->query("SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY order_id DESC, created_at DESC");

// ⚠️ TINANGGAL ANG function resolve_image_path_client() DAHIL HINDI NA KAILANGAN ANG DESIGN FILE

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔔 Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=5">
    <link rel="stylesheet" href="../css/notifications.css?v=2">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body { 
            background: #f8f9fa; 
            font-family: 'Poppins', sans-serif; 
        }
        .notification-card { 
            background: white; 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 15px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border-left-width: 5px;
            border-left-style: solid;
            transition: opacity 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer; /* ✅ Add cursor pointer */
        }
        .notification-card:hover { /* ✅ Add hover effect */
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        /* --- Status Border Colors --- */
        .border-success { border-left-color: #198754; }
        .border-primary { border-left-color: #0d6efd; }
        .border-warning { border-left-color: #ffc107; }
        .border-danger  { border-left-color: #dc3545; }
        .border-info    { border-left-color: #0dcaf0; }
        .border-secondary { border-left-color: #6c757d; }

        .notification-card.read {
             opacity: 0.7; /* Fade out read notifications */
        }
        .notification-card p {
            margin-bottom: 0.25rem;
        }
        .notification-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px; /* Allocate space for icon */
            text-align: center;
        }
        .order-separator {
            font-weight: 600;
            color: #6c757d;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        /* ✅ FIX: Notification content container */
        .notification-content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1.75rem;
            color: #2f6f56;
        }
        
        .notification-header .btn {
            padding: 8px 20px;
        }
        
        .notifications-area {
            margin-top: 20px;
        }
        
        /* ⚠️ NEW: Modal Styles for Order Details */
        #orderDetailsModal .modal-body h6 {
            color: #0d6efd;
            margin-top: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        #orderDetailsModal .modal-body p {
            margin-bottom: 5px;
        }
        .payment-breakdown-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .payment-breakdown-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .payment-breakdown-table td:first-child {
            font-weight: 600;
            color: #555;
        }
        .payment-breakdown-table td:last-child {
            text-align: right;
            color: #0d6efd;
            font-weight: 600;
        }
        .payment-breakdown-table tr:last-child td {
            border-bottom: none;
            padding-top: 12px;
            border-top: 2px solid #0d6efd;
            font-weight: 700;
            font-size: 16px;
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
      <li><a href="../components/order.php"><i class="fa-solid fa-credit-card icon" style = "padding-right: 10px;"></i> <span class="label"> Orders</span></a></li>
      <li>
  <a href="../components/notifications.php" class="active">
    <i class="fa-solid fa-bell icon" style = "padding-right: 10px;"></i>
    <span class="label">Notifications</span>
    <span id="notifCount" class="notif-badge"></span>
  </a>
</li>
            <li><a href="../components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>

      <li><a href="../components/profile.php"><i class="fa-solid fa-user icon" style="padding-right: 10px;"></i> <span class="label"> Profile</span></a></li>
    </ul>

    <div class="logout">
      <a href="logout.php" id="logout-btn">Logout</a>
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
    
    "orders" => "components/order.php",
    "my orders" => "components/order.php",
    
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
    
    "received" => "components/order_received.php",
    "received orders" => "components/order_received.php",
];

if (isset($_GET['search'])) {
    $search_input = strtolower(trim($_GET['search'])); // make case-insensitive

    if (array_key_exists($search_input, $search_map)) {
        header("Location: " . $search_map[$search_input]);
        exit();
    } else {
        $error_message = "Invalid search. Please use a valid keyword.";
    }
}
?>


    <div class="top-buttons">

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

    <div class="notification-content-wrapper">
        <div class="notification-header">
            <h3><i class="fas fa-bell"></i> Your Notifications</h3>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
        <p>Welcome, <strong><?= htmlspecialchars($displayName) ?></strong></p>
        <p class="mt-2">All your recent updates are listed below.</p>
        
        <div class="notifications-area">
            <?php if ($result->num_rows > 0): ?>
                <?php 
                $current_order_id = null; // Variable to track the order ID
                ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Display a separator if the order ID changes
                    if ($row['order_id'] !== $current_order_id) {
                        echo "<h5 class='order-separator mt-4 mb-3'>Updates for Orders</h5>";
                        $current_order_id = $row['order_id'];
                    }
                    
                    // ✅ Fetch Order Details from payments table for modal
                    $notif_ref = $row['order_id'];
                    $order_info = [];
                    
                    // ⚠️ UPDATED: Robust query to check BOTH the new 'order_id' (string) AND the legacy 'id' (int)
                    // This ensures the modal works even if the notification table still stores the old integer ID
                    $order_stmt = $paymentConn->prepare("SELECT * FROM payments WHERE (order_id = ? OR id = ?) AND user_id = ?"); 
                    
                    if ($order_stmt) {
                        // Bind as strings (ssi) - MySQL handles the comparison correctly even for Int columns
                        $order_stmt->bind_param("ssi", $notif_ref, $notif_ref, $user_id);
                        $order_stmt->execute();
                        $order_res = $order_stmt->get_result();
                        $order_info = $order_res->fetch_assoc() ?? [];
                        $order_stmt->close();
                    }
                    
                    // Prepare data attributes for the modal
                    $order_data_attrs = '';
                    if (!empty($order_info)) {
                        // ✅ CRITICAL: Use the Order ID from the payments table (the string format) for display
                        // This ensures that even if the lookup was via ID (int), the modal shows the new String ID
                        $display_order_id = $order_info['order_id'];

                        $expectedDate = $order_info['expected_date'];
                        $displayDate = 'N/A';
                        if (!empty($expectedDate) && $expectedDate != '0000-00-00') {
                            try {
                                $dateTime = new DateTime($expectedDate);
                                $displayDate = $dateTime->format('M d, Y');
                            } catch (Exception $e) {
                                $displayDate = htmlspecialchars($expectedDate);
                            }
                        }
                        
                        // ⚠️ TANGGALIN ang Design File B64 data attribute
                        
                        $order_data_attrs = 
                            " data-bs-toggle='modal' 
                              data-bs-target='#orderDetailsModal'
                              data-order-id='{$display_order_id}'
                              data-customer-name='" . htmlspecialchars($order_info['customer_name'] ?? $displayName) . "'
                              data-email='" . htmlspecialchars($order_info['email'] ?? $email) . "'
                              data-contact='" . htmlspecialchars($order_info['contact'] ?? 'N/A') . "'
                              data-address='" . htmlspecialchars($order_info['address'] ?? 'N/A') . "'
                              data-order-type='" . htmlspecialchars($order_info['order_type'] ?? '-') . "'
                              data-quantity='" . htmlspecialchars($order_info['quantity'] ?? '1') . "'
                              data-total-price='₱" . number_format($order_info['total_price'] ?? 0, 2) . "'
                              data-downpayment-amount='" . ($order_info['downpayment_amount'] ?? 0) . "'
                              data-expected-date='" . htmlspecialchars($displayDate) . "'
                              data-order-details='" . htmlspecialchars($order_info['order_details'] ?? '{}', ENT_QUOTES, 'UTF-8') . "'";
                    }
                    
                    ?>
                    <?php
                        $message = htmlspecialchars($row['message']);
                        
                        $status_color_class = 'text-secondary';
                        $status_icon_class = 'fas fa-bell';
                        $border_class = 'border-secondary';
                        $is_completed = false; 

                        if (stripos($message, 'processing') !== false) {
                            $status_color_class = 'text-primary';
                            $status_icon_class = 'fas fa-sync-alt fa-spin'; 
                            $border_class = 'border-primary';
                        } elseif (stripos($message, 'completed') !== false) {
                            $status_color_class = 'text-success';
                            $status_icon_class = 'fas fa-check-circle'; 
                            $border_class = 'border-success';
                            $is_completed = true;
                        } elseif (stripos($message, 'pending') !== false) {
                            $status_color_class = 'text-warning';
                            $status_icon_class = 'fas fa-clock';
                            $border_class = 'border-warning';
                        } elseif (stripos($message, 'cancelled') !== false || stripos($message, 'failed') !== false) {
                            $status_color_class = 'text-danger';
                            $status_icon_class = 'fas fa-times-circle';
                            $border_class = 'border-danger';
                        } elseif (stripos($message, 'received') !== false) { // Logic for 'Received' status notification
                            $status_color_class = 'text-primary'; 
                            $status_icon_class = 'fas fa-clipboard-check';
                            $border_class = 'border-primary';
                        } elseif (stripos($message, 'order') !== false || stripos($message, 'new') !== false) {
                            $status_color_class = 'text-info';
                            $status_icon_class = 'fas fa-box-open';
                            $border_class = 'border-info';
                        }
                    ?>
                    <div class="notification-card <?= $row['status'] ?> <?= $border_class ?>"
                        <?= $order_data_attrs ?>> <div class="d-flex align-items-center">
                            <div class="notification-icon">
                                 <i class="<?= $status_icon_class ?> <?= $status_color_class ?>"></i>
                            </div>
                            <div>
                                <p class="mb-1"><?= $message ?></p>
                                <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></small>
                                
                                <?php if ($is_completed): ?>
                                    <p class="mb-0 mt-1 text-danger small fw-bold">
                                        <i class="fas fa-store me-1"></i> You can now claim this
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    You have no notifications yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
  </div>

  <div class="logout-modal" id="logoutModal">
    <div class="logout-box">
     <!-- <i class="fa-solid fa-print"></i> -->
      <!--<h2>ARE YOU SURE YOU WANT TO<br>LOG OUT?</h2>-->
      <div class="logout-actions">
       <!-- <a href="logout.php" class="btn yes">yes</a> -->
      <!--  <button class="btn no" id="cancelLogout">no</button> -->
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderDetailsModalLabel">Order Details for <span id="modalCustomerName"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6><strong>Booking Details</strong></h6>
                <p><strong>Order ID:</strong> <span id="modalOrderId"></span></p>
                <p><strong>Customer Name:</strong> <span id="modalCustomerName2"></span></p>
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <p><strong>Contact:</strong> <span id="modalContact"></span></p>
                <p><strong>Notes:</strong> <span id="modalAddress"></span></p>
              </div>
              <div class="col-md-6">
                <h6><strong>Service Details</strong></h6>
                <p><strong>Service Needed:</strong> <span id="modalOrderType"></span></p>
                <div id="modalServiceSpecifics" class="ps-3 mb-2"></div>
                <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                
                <div id="modalPaymentBreakdown" style="display: none;">
                  <table class="payment-breakdown-table">
                    <tr>
                      <td>Total Amount:</td>
                      <td id="modalDetailTotalPrice">₱0.00</td>
                    </tr>
                    <tr>
                      <td>Downpayment:</td>
                      <td id="modalDetailDownpayment">₱0.00</td>
                    </tr>
                    <tr>
                      <td>Remaining Balance:</td>
                      <td id="modalDetailRemaining">₱0.00</td>
                    </tr>
                  </table>
                </div>
                
                <p id="modalSimpleTotalPrice"><strong>Total Price:</strong> <span id="modalTotalPriceValue"></span></p>
                
                <p><strong>Expected Date:</strong> <span id="modalExpectedDate"></span></p>
                <hr>
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // ✅ Order Details Modal JavaScript Logic (Modified from manage_order.php)
    const orderDetailsModal = document.getElementById('orderDetailsModal');
    if (orderDetailsModal) {
        orderDetailsModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;

            const orderId = button.getAttribute('data-order-id');
            const customerName = button.getAttribute('data-customer-name');
            const email = button.getAttribute('data-email');
            const contact = button.getAttribute('data-contact');
            const address = button.getAttribute('data-address');
            const orderType = button.getAttribute('data-order-type');
            const quantity = button.getAttribute('data-quantity');
            const totalPrice = button.getAttribute('data-total-price');
            const downpaymentAmount = parseFloat(button.getAttribute('data-downpayment-amount')) || 0;
            const expectedDate = button.getAttribute('data-expected-date');
            const orderDetailsJson = button.getAttribute('data-order-details');
            
            // ⚠️ TANGGALIN ang Design File B64 variable
            
            orderDetailsModal.querySelector('#modalCustomerName').textContent = customerName;
            orderDetailsModal.querySelector('#modalOrderId').textContent = orderId;
            orderDetailsModal.querySelector('#modalCustomerName2').textContent = customerName;
            orderDetailsModal.querySelector('#modalEmail').textContent = email;
            orderDetailsModal.querySelector('#modalContact').textContent = contact;
            orderDetailsModal.querySelector('#modalAddress').textContent = address;
            orderDetailsModal.querySelector('#modalOrderType').textContent = orderType;
            orderDetailsModal.querySelector('#modalQuantity').textContent = quantity;
            orderDetailsModal.querySelector('#modalExpectedDate').textContent = expectedDate;
            
            // Handle payment breakdown display
            const paymentBreakdownDiv = orderDetailsModal.querySelector('#modalPaymentBreakdown');
            const simpleTotalPriceP = orderDetailsModal.querySelector('#modalSimpleTotalPrice');
            
            if (downpaymentAmount > 0) {
                // Show breakdown table
                paymentBreakdownDiv.style.display = 'block';
                simpleTotalPriceP.style.display = 'none';
                
                const totalValue = parseFloat(totalPrice.replace('₱', '').replace(',', ''));
                const remaining = totalValue - downpaymentAmount;
                
                orderDetailsModal.querySelector('#modalDetailTotalPrice').textContent = totalPrice;
                orderDetailsModal.querySelector('#modalDetailDownpayment').textContent = '₱' + downpaymentAmount.toFixed(2);
                orderDetailsModal.querySelector('#modalDetailRemaining').textContent = '₱' + remaining.toFixed(2);
            } else {
                // Show simple total price
                paymentBreakdownDiv.style.display = 'none';
                simpleTotalPriceP.style.display = 'block';
                orderDetailsModal.querySelector('#modalTotalPriceValue').textContent = totalPrice;
            }
            
            // Handle Service Specifics
            const specificsContainer = orderDetailsModal.querySelector('#modalServiceSpecifics');
            specificsContainer.innerHTML = '';
            try {
                const details = JSON.parse(orderDetailsJson);
                for (const [key, value] of Object.entries(details)) {
                    if (value) {
                        const p = document.createElement('p');
                        p.style.margin = '0';
                        p.style.fontSize = '0.9rem';
                        // Clean up keys for display
                        const displayKey = key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                        p.innerHTML = `<strong>${displayKey}:</strong> ${value}`;
                        specificsContainer.appendChild(p);
                    }
                }
            } catch (e) {
                console.error("Could not parse order details JSON:", e);
                specificsContainer.innerHTML = '<p class="text-muted">No specific details available.</p>';
            }

            // ⚠️ TANGGALIN ang Design File Link Logic
        });
    }
    </script>
</body>
</html>
<?php 
$conn->close();
$paymentConn->close(); // ✅ Close the payment connection
?>