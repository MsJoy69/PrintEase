<?php
// chat_handler.php
session_start();
include("db.php"); 

// Helper function to return JSON
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// 1. Authenticate
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['id'] ?? null;

// Prevent Session Locking - CRITICAL for Long Polling
session_write_close();

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$current_user_id) {
    jsonResponse(['status' => 'error', 'message' => 'Not logged in']);
}

// --- HANDLE SENDING MESSAGES ---
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    
    // If admin is sending, they must provide target_user_id via POST
    // If user is sending, the target defaults to themselves
    $target_user_id = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : $current_user_id;
    $sender_type = $_POST['sender_type'] ?? 'user'; 
    
    $attachmentPath = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        // FIX: Removed the leading "/" to make it relative to this file
        // This will save to: [Folder containing chat_handler.php]/components/uploads/
        $targetDir = "components/uploads/";
        
        // Ensure directory exists
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachmentPath = $fileName;
        } else {
            // Optional: Debugging line if it still fails
            // jsonResponse(['status' => 'error', 'message' => 'Failed to move file. Check permissions.']);
        }
    }

    if (!empty($message) || $attachmentPath) {
        // Prepare statement once
        $stmt = $conn->prepare("INSERT INTO messages (user_id, sender_type, message, attachment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $target_user_id, $sender_type, $message, $attachmentPath);
        
        if ($stmt->execute()) {
            jsonResponse(['status' => 'success']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'DB Error']);
        }
    }
    jsonResponse(['status' => 'error', 'message' => 'Empty message']);
}

// --- HANDLE LONG POLLING (FETCH) ---
if ($action === 'fetch') {
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    set_time_limit(0);

    $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    $time_limit = 20; 
    $start_time = time();

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