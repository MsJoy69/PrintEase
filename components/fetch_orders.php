<?php
session_start();

if(!isset($_SESSION['user'])){
    echo "<div class='alert alert-warning text-center'>Please log in to view your orders.</div>";
    exit();
}

$paymentDB = new mysqli("localhost", "root", "", "payment");
$infoDB = new mysqli("localhost", "root", "", "printease");

$username = $_SESSION['user'];
$statusFilter = $_GET['status'] ?? 'All';

$infoQuery = $infoDB->prepare("SELECT name FROM info WHERE username = ?");
$infoQuery->bind_param("s", $username);
$infoQuery->execute();
$infoResult = $infoQuery->get_result()->fetch_assoc();
$name = $infoResult['name'] ?? $username;
$like = "%$name%";

// 🔑 UPDATED: Added 'order_id' to the SELECT list
if ($statusFilter === "All") {
    $orderQuery = $paymentDB->prepare("
        SELECT id, order_id, order_type, total_price, status 
        FROM payments 
        WHERE username = ? OR customer_name LIKE ?
        ORDER BY id DESC
    ");
    $orderQuery->bind_param("ss", $username, $like);
} else {
    // This logic handles the new 'Received' status filter automatically
    $orderQuery = $paymentDB->prepare("
        SELECT id, order_id, order_type, total_price, status 
        FROM payments 
        WHERE (username = ? OR customer_name LIKE ?) AND status = ?
        ORDER BY id DESC
    ");
    $orderQuery->bind_param("sss", $username, $like, $statusFilter);
}

$orderQuery->execute();
$orders = $orderQuery->get_result();

if($orders->num_rows == 0){
    echo "<div class='alert alert-secondary text-center'>No $statusFilter orders found.</div>";
    exit();
}

while($order = $orders->fetch_assoc()){
    $status = htmlspecialchars($order['status']);
    
    // 🔑 UPDATED: Logic to show string ID (e.g. 2025...) if exists, else DB ID
    $dbId = htmlspecialchars($order['id']);
    $displayId = !empty($order['order_id']) ? htmlspecialchars($order['order_id']) : $dbId;
    
    // Base progress steps (Pending, Processing, Completed)
    $progressSteps = "
        <span class='progress-step " . ($status == 'Pending' ? 'active' : '') . "'>Pending</span>
        <span class='progress-step " . ($status == 'Processing' ? 'active' : '') . "'>Processing</span>
        <span class='progress-step " . ($status == 'Completed' ? 'active' : '') . "'>Completed</span>
    ";
    
    $actionElement = "";
    
    if ($status === 'Completed') {
        // If Completed, show the 'Mark as Received' button
        // 🔑 CONFIRMED: These are the attributes accessed by order.php JavaScript
        $actionElement = "
            <button 
                class='btn btn-success btn-sm mark-received-btn' 
                data-id='{$dbId}'
                data-display-id='{$displayId}'
                style='margin-left: 10px; font-weight: 600;'
            >
                <i class='fas fa-box-open me-1'></i> Mark as Received
            </button>
        ";
    } elseif ($status === 'Received') {
        // If Received, show the 'Received' progress step
        $progressSteps .= "<span class='progress-step active'>Received</span>";
    }

    echo "
    <div class='order-card' id='order-card-{$dbId}'>
        <h5>Order #{$displayId} — " . htmlspecialchars($order['order_type']) . "</h5>
        <p>Total: ₱" . number_format($order['total_price'], 2) . "</p>
        <div class='d-flex align-items-center'>
            {$progressSteps}
            {$actionElement}
        </div>
    </div>
    ";
}

$orderQuery->close();
$infoQuery->close();
$paymentDB->close();
$infoDB->close();
?>