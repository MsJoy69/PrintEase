<?php  
include 'auth.php'; 

$conn = new mysqli("localhost", "root", "", "payment");
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}
$status = 'Completed';
$result = $conn->query("SELECT * FROM payments WHERE status='$status' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Completed Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
<h2 class="text-center mb-4">✅ Completed Orders</h2>
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-success">
<tr>
    <th>#</th>
    <th>Customer</th>
    <th>Order Type</th>
    <th>Total Price</th>
    <th>Payment Proof</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?></td>
    <td><?= htmlspecialchars($row['order_type'] ?? '-') ?></td>
    <td>₱<?= number_format($row['total_price'], 2) ?></td>
    <td>
        <?php if(!empty($row['payment_proof'])): ?>
            <a href="uploads/<?= htmlspecialchars($row['payment_proof']) ?>" target="_blank">
                <img src="uploads/<?= htmlspecialchars($row['payment_proof']) ?>" style="max-width:80px;border-radius:6px;">
            </a>
        <?php else: ?>
            <span class="text-muted">None</span>
        <?php endif; ?>
    </td>
    <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="text-muted">No completed orders found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</body>
</html>
<?php $conn->close(); ?>
