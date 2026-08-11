<?php
// fetch_notif_count.php
session_start();
header('Content-Type: text/plain; charset=utf-8');

// If user_id not set, return 0
if (!isset($_SESSION['user_id'])) {
    echo 0;
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// DB connection (notifications DB)
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'notification';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    // On DB error return 0 (you can change to show an error during debugging)
    error_log("fetch_notif_count.php DB connection error: " . $conn->connect_error);
    echo 0;
    exit;
}

// Prepared statement to count unread notifications for this user
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status = 'unread'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("fetch_notif_count.php prepare failed: " . $conn->error);
    echo 0;
    $conn->close();
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$count = 0;
if ($res && ($row = $res->fetch_assoc())) {
    $count = (int) $row['unread_count'];
}

echo $count;

$stmt->close();
$conn->close();
exit;
