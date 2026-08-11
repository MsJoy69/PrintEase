<?php
session_start();
$conn = new mysqli("localhost", "root", "", "printease");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['user'];

// Example query: adjust column/table names as per your DB
$sql = "SELECT COUNT(*) AS total FROM notifications WHERE username = '$user' AND is_read = 0";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo $row['total'];
} else {
    echo 0;
}

$conn->close();
?>
