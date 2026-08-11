<?php
session_start();
$conn = new mysqli("localhost", "root", "", "payment");

$username = $_SESSION['user'] ?? '';
if(!$username) exit("Not logged in.");

// Fetch only Completed orders
$stmt = $conn->prepare("
    SELECT id, product_name, quantity, order_date 
    FROM payments 
    WHERE username=? AND status='Completed'
    ORDER BY order_date DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>📜 Order History</h3>";

if($result->num_rows == 0){
    echo "<div class='alert alert-info'>No completed orders yet.</div>";
} else {
    while($row = $result->fetch_assoc()){
        echo "<div class='order-card'>";
        echo "<h5>Order #{$row['id']} - {$row['product_name']}</h5>";
        echo "<p>Quantity: {$row['quantity']}</p>";
        echo "<p>Order Date: {$row['order_date']}</p>";
        echo "</div>";
    }
}

$stmt->close();
$conn->close();
?>
