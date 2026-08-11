<?php
session_start();

// ===== DATABASE CONNECTION =====
$conn = new mysqli("localhost", "root", "", "payment");
if ($conn->connect_error) {
    die("<p class='text-danger text-center'>Database connection failed!</p>");
}

// ===== GET CURRENT USER =====
$username = $_SESSION['user'] ?? 'demo_user';
$status = $_POST['status'] ?? '';

if (empty($status)) {
    echo "<p class='text-center text-muted'>Please select a status.</p>";
    exit();
}

// ===== FETCH ORDERS BASED ON STATUS =====
$stmt = $conn->prepare("
    SELECT id, order_type, total_price, status, created_at 
    FROM payments 
    WHERE username = ? AND status = ?
    ORDER BY id DESC
");
$stmt->bind_param("ss", $username, $status);
$stmt->execute();
$result = $stmt->get_result();

// ===== DISPLAY RESULTS =====
if ($result->num_rows > 0) {
    echo "<h5 class='mb-3 text-capitalize'>$status Orders</h5>";
    echo "<div class='list-group'>";
    while ($row = $result->fetch_assoc()) {
        echo "
        <div class='list-group-item d-flex justify-content-between align-items-center'>
            <div>
                <strong>Order #{$row['id']}</strong><br>
                <small>Type: {$row['order_type']}</small><br>
                <small>Total: ₱" . number_format($row['total_price'], 2) . "</small><br>
                <small>Date: {$row['created_at']}</small>
            </div>
            <span class='badge bg-secondary'>{$row['status']}</span>
        </div>
        ";
    }
    echo "</div>";
} else {
    echo "<p class='text-center text-muted'>No orders found under <strong>$status</strong>.</p>";
}

// ===== CLEANUP =====
$stmt->close();
$conn->close();
?>
