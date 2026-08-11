<?php
// admin_chat_handler.php
// PLACE THIS FILE INSIDE THE 'ADMIN' FOLDER
session_start();
include("../db.php"); // Go up one level to find db.php

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// 1. Authenticate
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true;
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['id'] ?? ($is_admin ? 999999 : null);

session_write_close(); // Prevent locking

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$current_user_id) {
    jsonResponse(['status' => 'error', 'message' => 'Not logged in.']);
}

// --- HANDLE SENDING MESSAGES ---
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    
    $target_user_id = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;
    $sender_type = $_POST['sender_type'] ?? 'admin'; 
    
    $attachmentPath = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $targetDir = "../components/uploads/"; 
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachmentPath = $fileName;
        }
    }

    if ((!empty($message) || $attachmentPath) && $target_user_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, sender_type, message, attachment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $target_user_id, $sender_type, $message, $attachmentPath);
        
        if ($stmt->execute()) {
            jsonResponse(['status' => 'success']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'DB Error: ' . $conn->error]);
        }
    }
    jsonResponse(['status' => 'error', 'message' => 'Empty message or missing target user']);
}

// --- HANDLE LONG POLLING (FETCH) ---
if ($action === 'fetch') {
    header("Cache-Control: no-cache, must-revalidate");
    set_time_limit(0);

    $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    // SAFETY CHECK: If no user ID is passed, wait 2 seconds before returning.
    // This prevents the JS client from entering a rapid infinite loop if the ID is missing.
    if ($target_user_id == 0) {
        sleep(2);
        jsonResponse(['status' => 'success', 'messages' => []]);
    }

    $time_limit = 20; 
    $start_time = time();

    // Prepare statement once
    $stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? AND id > ? ORDER BY created_at ASC");
    $stmt->bind_param("ii", $target_user_id, $last_id);

    while (time() - $start_time < $time_limit) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        $new_messages = [];
        while ($row = $result->fetch_assoc()) {
            $new_messages[] = [
                'id' => $row['id'],
                'message' => htmlspecialchars($row['message']),
                'sender_type' => $row['sender_type'],
                'attachment' => $row['attachment'],
                'created_at' => date("h:i A", strtotime($row['created_at']))
            ];
        }

        if (!empty($new_messages)) {
            jsonResponse(['status' => 'success', 'messages' => $new_messages]);
        }

        sleep(1);
        clearstatcache();
    }

    jsonResponse(['status' => 'success', 'messages' => []]);
}
?>