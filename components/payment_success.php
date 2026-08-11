<?php
session_start();

// Redirect if no order ID in session
if (!isset($_SESSION['orderID'])) {
    header("Location: ../index.php");
    exit();
}

$orderID = $_SESSION['orderID'];

// ====== 1. FETCH ORDER DETAILS FROM DB ======
$conn = new mysqli("localhost", "root", "", "payment");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare statement to be safe
$stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->bind_param("s", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $orderRow = $result->fetch_assoc();
    
    // Decode the JSON order details
    $specificDetails = json_decode($orderRow['order_details'], true);
    
    // Prepare variables for the view
    $orderType = $orderRow['order_type'];
    $quantity = $orderRow['quantity'];
    $totalPrice = $orderRow['total_price'];
    $downpayment = $orderRow['downpayment_amount'];
    $balance = $totalPrice - $downpayment;
    $customerName = $orderRow['customer_name'];
    $customerAddress = $orderRow['address'];
    $paymentMethod = $orderRow['payment_method'];
    $dateOrdered = date("F j, Y"); 
    // NEW: Fetch Expected Date
    $estimatedCompletion = $orderRow['expected_date'];
} else {
    // Fallback if order not found
    $orderRow = [];
    $specificDetails = [];
    $estimatedCompletion = "TBA";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Success - Printease</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../style.css?v=3">
<!-- Include html2pdf library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 100;
    }

    .main-content {
        margin-left: 250px;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
    }

    .success-page-wrapper {
        background: #f5f5f5;
        min-height: calc(100vh - 61px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .success-wrapper {
        max-width: 500px;
        width: 100%;
        padding: 0;
        background-color: #fff;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .success-header {
        background: linear-gradient(135deg, #6b9d88 0%, #5a8775 100%);
        padding: 40px 30px;
        color: white;
        position: relative;
    }

    .checkmark-icon {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }

    .checkmark-icon i {
        font-size: 30px;
    }

    .success-header h1 {
        font-size: 24px;
        font-weight: 600;
        margin: 0;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .success-body {
        padding: 35px 30px;
    }

    .order-id-display {
        font-size: 14px;
        color: #666;
        margin-bottom: 25px;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        display: inline-block;
    }

    .order-id-display strong {
        color: #333;
        font-size: 16px;
    }

    .thank-you-box {
        margin-bottom: 30px;
    }

    .thank-you-box h3 {
        font-size: 20px;
        font-weight: 600;
        color: #2d5f4d;
        margin: 0 0 10px 0;
    }

    .thank-you-box p {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-print {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 20px;
        border: 2px solid #333;
        border-radius: 8px;
        background: #fff;
        color: #333;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-print:hover {
        background: #333;
        color: #fff;
    }

    .btn-back {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 20px;
        border-radius: 8px;
        background: #6b9d88;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: #5a8775;
    }

    /* ==========================
       RECEIPT TEMPLATE STYLES
       ========================== */
    #receipt-content {
        display: none;
        font-family: 'Helvetica', sans-serif;
        width: 100%;
        max-width: 800px;
        padding: 40px;
        background: white;
        color: #333;
    }

    .receipt-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .receipt-logo h2 {
        color: #6b9d88;
        margin: 0;
        font-size: 28px;
    }

    .receipt-info {
        text-align: right;
    }

    .receipt-title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .bill-to {
        margin-bottom: 30px;
    }

    .bill-to h4 {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 14px;
        text-transform: uppercase;
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }

    .receipt-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        color: #666;
        border-bottom: 2px solid #eee;
    }

    .receipt-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    .receipt-totals {
        width: 300px;
        margin-left: auto;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 14px;
    }

    .total-row.final {
        border-top: 2px solid #eee;
        margin-top: 10px;
        padding-top: 15px;
        font-weight: bold;
        font-size: 18px;
        color: #6b9d88;
    }

    .receipt-footer {
        margin-top: 50px;
        text-align: center;
        font-size: 12px;
        color: #999;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    
    .spec-list {
        font-size: 12px;
        color: #555;
        margin-top: 5px;
        list-style: none;
        padding: 0;
    }
    .spec-list li {
        margin-bottom: 4px;
        display: block; /* List vertically for clarity */
    }
    .spec-list li.separator {
        border-top: 1px dashed #eee;
        margin: 8px 0;
    }

    @media(max-width:600px){
        .main-content { margin-left: 0; }
        .sidebar { display: none; }
        .success-wrapper { margin: 20px; }
        .action-buttons { flex-direction: column; }
    }
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../image/logo.png" alt="Printease Logo">
            <h2>PRINTEASE</h2>
        </div>

        <ul class="menu">
            <li><a href="/systemcutie/index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
            <li><a href="/systemcutie/components/product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
            <li><a href="/systemcutie/components/order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
            <li><a href="/systemcutie/components/notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
            <li><a href="/systemcutie/components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
            <li><a href="/systemcutie/components/profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
        </ul>

        <div class="logout">
            <a href="#" id="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Success Page Content -->
        <div class="success-page-wrapper">
            <div class="success-wrapper">
                <div class="success-header">
                    <div class="checkmark-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h1>Payment Successful!</h1>
                </div>

                <div class="success-body">
                    <div class="thank-you-box">
                        <h3>Thank you for your purchase!</h3>
                        <p>Your order has been placed successfully. You can download your official receipt below.</p>
                    </div>

                    <div class="order-id-display">
                        Order ID: <strong>#<?php echo htmlspecialchars($orderID); ?></strong>
                    </div>

                    <div class="action-buttons">
                        <!-- Trigger PDF Generation -->
                        <button class="btn-print" onclick="generatePDF()">
                            <i class="fa-solid fa-file-pdf"></i>
                            Download Receipt
                        </button>
                        <a href="../index.php" class="btn-back">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===========================================
         HIDDEN RECEIPT TEMPLATE (Used for PDF)
         =========================================== -->
    <?php if ($result->num_rows > 0): ?>
    <div id="receipt-content">
        <div class="receipt-header">
            <div class="receipt-logo">
                <h2>PRINTEASE</h2>
                <p style="margin:5px 0 0; color:#666; font-size:12px;">Custom Printing Solutions</p>
            </div>
            <div class="receipt-info">
                <div class="receipt-title">OFFICIAL RECEIPT</div>
                <p style="margin: 5px 0; font-size:14px;"><strong>Order #:</strong> <?php echo htmlspecialchars($orderID); ?></p>
                <p style="margin: 0; font-size:14px;"><strong>Date:</strong> <?php echo $dateOrdered; ?></p>
                <!-- NEW: ESTIMATED COMPLETION -->
                <p style="margin: 5px 0; font-size:14px; color: #d35400;"><strong>Est. Completion:</strong> <?php echo htmlspecialchars($estimatedCompletion); ?></p>
                <p style="margin: 5px 0; font-size:14px; color: #27ae60;"><strong>Status:</strong> Paid / Pending</p>
            </div>
        </div>

        <div class="bill-to">
            <h4>Bill To:</h4>
            <p style="margin:0; font-weight:bold; font-size:16px;"><?php echo htmlspecialchars($customerName); ?></p>
            <p style="margin:5px 0 0; color:#555;"><?php echo htmlspecialchars($customerAddress); ?></p>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th width="50%">Description</th>
                    <th width="15%" style="text-align:center;">Qty</th>
                    <th width="35%" style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($orderType); ?> Service</strong>
                        <?php if(!empty($specificDetails)): ?>
                            <ul class="spec-list">
                                <?php foreach($specificDetails as $key => $val): ?>
                                    <?php 
                                        // Check for separator or special formatting
                                        if ($key === '---') {
                                            echo '<li class="separator"></li>';
                                        } else {
                                    ?>
                                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($val); ?></li>
                                    <?php } endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center; vertical-align: top;"><?php echo htmlspecialchars($quantity); ?></td>
                    <td style="text-align:right; vertical-align: top;">₱<?php echo number_format($totalPrice, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($totalPrice, 2); ?></span>
            </div>
            
            <?php if($downpayment > 0): ?>
            <div class="total-row" style="color: #27ae60;">
                <span>Downpayment Paid (<?php echo htmlspecialchars($paymentMethod); ?>):</span>
                <span>- ₱<?php echo number_format($downpayment, 2); ?></span>
            </div>
            <?php endif; ?>

            <div class="total-row final">
                <span>Balance Due:</span>
                <span>₱<?php echo number_format($balance, 2); ?></span>
            </div>
            
            <div style="margin-top:10px; text-align:right; font-size:12px; color:#888;">
                Payment Method: <?php echo htmlspecialchars($paymentMethod); ?>
            </div>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your business!</p>
            <p>For questions, please contact support@printease.com</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="logout-modal" id="logoutModal">
        <div class="logout-box">
            <i class="fa-solid fa-print"></i>
            <h2>ARE YOU SURE YOU WANT TO<br>LOG OUT?</h2>
            <div class="logout-actions">
                <a href="components/login.php?logout=1" class="btn yes">yes</a>
                <button class="btn no" id="cancelLogout">no</button>
            </div>
        </div>
    </div>

    <script>
        function generatePDF() {
            const element = document.getElementById('receipt-content');
            
            element.style.display = 'block';

            const opt = {
                margin:       0.5,
                filename:     'Printease_Receipt_<?php echo $orderID; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function(){
                element.style.display = 'none';
            });
        }

        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');

        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
            });
        }

        if (cancelLogout) {
            cancelLogout.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>