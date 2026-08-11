<?php
// FILE: mark_as_received.php
// Ito ang file na nag-u-update ng order status mula 'Completed' papuntang 'Received' at nagtatala ng petsa.

session_start();
header('Content-Type: application/json');

// Check kung naka-login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "payment");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$orderId = $_POST['order_id'] ?? null;
$username = $_SESSION['user'];

if ($orderId === null) {
    echo json_encode(['success' => false, 'message' => 'Order ID is missing.']);
    $conn->close();
    exit();
}

// 1. I-verify na ang order ay COMPLETED at pagmamay-ari ng user
$checkSql = "SELECT status FROM payments WHERE id = ? AND username = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $orderId, $username);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or you do not have permission.']);
    $checkStmt->close();
    $conn->close();
    exit();
}

$order = $checkResult->fetch_assoc();
$checkStmt->close();

if ($order['status'] !== 'Completed') {
    echo json_encode(['success' => false, 'message' => 'Order status is not Completed. Current status: ' . $order['status']]);
    $conn->close();
    exit();
}

// 2. I-update ang status sa 'Received' at itala ang completion_date
// Tandaan: Dapat nauna mo nang ginawa ang SQL command na ito sa database:
// ALTER TABLE payments ADD COLUMN completion_date DATETIME NULL AFTER status;
$updateSql = "UPDATE payments SET status = 'Received', completion_date = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $orderId);

if ($updateStmt->execute()) {
    // Kumuha ng bagong petsa para i-display
    $dateReceived = date('M d, Y h:i A');
    
    echo json_encode([
        'success' => true,
        'message' => 'Order successfully marked as Received!',
        'date_received' => $dateReceived
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>