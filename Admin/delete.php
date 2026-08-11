<?php
include('../db.php');
include 'auth.php'; 

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
header("Location: customer.php");
exit();
?>
