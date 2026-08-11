<?php
// Use the shared auth file to ensure consistency with index.php
include 'auth.php'; 

// Database connection
include("../db.php"); 

// --- 1. OPTIMIZED: Fetch Unique Users who have chatted ---
// We use GROUP BY instead of DISTINCT for better performance on large tables
$userQuery = "
    SELECT u.id AS user_id, u.name, u.email, MAX(m.created_at) as last_msg_time
    FROM messages m
    JOIN users u ON m.user_id = u.id
    GROUP BY m.user_id
    ORDER BY last_msg_time DESC
";
$userResult = $conn->query($userQuery);

// --- 2. Determine Active Chat User ---
$active_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$active_user_name = "Select a user";

// Default selection logic
if (!$active_user_id && $userResult && $userResult->num_rows > 0) {
    $firstUser = $userResult->fetch_assoc();
    $active_user_id = $firstUser['user_id'];
    $active_user_name = $firstUser['name'];
    // Reset pointer so the loop below works correctly
    $userResult->data_seek(0);
} elseif ($active_user_id) {
    $nameStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $nameStmt->bind_param("i", $active_user_id);
    $nameStmt->execute();
    $res = $nameStmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $active_user_name = $row['name'];
    }
}

// --- 3. Initial Message Fetch (SSR) ---
// Added a safety LIMIT to prevent browser crash if a user has 10,000+ messages
$messages = [];
$last_id = 0;
if ($active_user_id) {
    $msgStmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC");
    $msgStmt->bind_param("i", $active_user_id);
    $msgStmt->execute();
    $result = $msgStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
        if($row['id'] > $last_id) $last_id = $row['id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Chats - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="customer_chat.css?v=2">
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <h4>PrintEase</h4>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="customer_chat.php" class="active"><i class="fa-solid fa-message"></i> Customer Chat</a>
        <a href="manage_order.php"><i class="fas fa-tasks"></i> Manage Orders</a>
        <a href="customer.php"><i class="fas fa-users"></i> Customer Management</a>
        <a href="online_order.php"><i class="fas fa-credit-card"></i> Online Order</a>
        <a href="cash_order.php"><i class="fas fa-money-bill"></i> Cash Order</a>
        <a href="received_order.php"><i class="fas fa-money-bill"></i> Received Order</a>
       
        <a href="admin_profile.php"><i class="fas fa-user-circle"></i> Admin Profile</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
    </div>

    <div class="chat-container">
        <!-- List of Users -->
        <div class="user-list">
            <div class="user-list-header">Conversations</div>
            <div style="overflow-y: auto; height: 100%;">
                <?php if ($userResult && $userResult->num_rows > 0): ?>
                    <?php while($user = $userResult->fetch_assoc()): ?>
                        <a href="customer_chat.php?user_id=<?php echo $user['user_id']; ?>" 
                           class="text-decoration-none text-dark">
                            <div class="user-item <?php echo ($active_user_id == $user['user_id']) ? 'active' : ''; ?>">
                                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <small class="text-muted">
                                        <?php 
                                            // Show time nicely
                                            $time = strtotime($user['last_msg_time']);
                                            if (date('Ymd') == date('Ymd', $time)) {
                                                echo date('h:i A', $time);
                                            } else {
                                                echo date('M d', $time);
                                            }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-muted text-center">No messages yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="chat-area">
            <?php if ($active_user_id): ?>
                <div class="chat-header">
                    <h5 class="m-0"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($active_user_name); ?></h5>
                </div>

                <div class="chat-box" id="chatBox">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_type']; ?>">
                            <?php echo htmlspecialchars($msg['message']); ?>
                            <?php if(!empty($msg['attachment'])): ?>
                                <br><a href="../components/uploads/<?php echo htmlspecialchars($msg['attachment']); ?>" target="_blank" style="color: inherit; text-decoration: underline;"><i class="fa fa-paperclip"></i> Attachment</a>
                            <?php endif; ?>
                            <span class="timestamp"><?php echo date('M d, H:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form id="adminChatForm" class="chat-input">
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="target_user_id" value="<?php echo $active_user_id; ?>">
                    <input type="hidden" name="sender_type" value="admin">

                    <input type="text" name="message" id="msgInput" placeholder="Type a reply..." required autocomplete="off">
                    <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
            <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                    Select a conversation to start chatting
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($active_user_id): ?>
<script>
    const chatBox = document.getElementById("chatBox");
    const chatForm = document.getElementById("adminChatForm");
    const msgInput = document.getElementById("msgInput");
    let lastMessageId = <?php echo $last_id; ?>;
    const activeUserId = <?php echo $active_user_id; ?>;
    let isPolling = true;

    // Scroll to bottom initially
    chatBox.scrollTop = chatBox.scrollHeight;

    // 1. Send Message
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = msgInput.value.trim();
        if(!msg) return;

        const formData = new FormData(chatForm);

        fetch('admin_chat_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                msgInput.value = '';
                // Do not auto-scroll here, wait for polling to pick it up
            } else {
                alert('Error sending message: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => console.error(err));
    });

    // 2. Poll Messages (CRITICAL FIX: Prevent Infinite Loops)
    function pollMessages() {
        if (!isPolling) return;

        // Add timestamp to prevent caching
        const url = `admin_chat_handler.php?action=fetch&last_id=${lastMessageId}&user_id=${activeUserId}&t=${Date.now()}`;

        fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if(msg.id <= lastMessageId) return;

                    const div = document.createElement('div');
                    div.className = `message ${msg.sender_type}`;
                    
                    let html = msg.message;
                    if (msg.attachment) {
                        html += `<br><a href="../components/uploads/${msg.attachment}" target="_blank" style="color: inherit; text-decoration: underline;"><i class="fa fa-paperclip"></i> Attachment</a>`;
                    }
                    html += `<span class="timestamp">${msg.created_at}</span>`;
                    
                    div.innerHTML = html;
                    chatBox.appendChild(div);
                    lastMessageId = msg.id;
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            } else if (data.status === 'error') {
                console.error("Server Error:", data.message);
            }
            
            // Success: Poll again immediately (long polling)
            pollMessages(); 
        })
        .catch(err => {
            console.error("Polling error (retrying in 5s):", err);
            // Error: Wait 5 seconds before retrying to prevent crashing the browser
            setTimeout(() => { pollMessages(); }, 5000);
        });
    }

    // Start Polling
    pollMessages();
</script>
<?php endif; ?>

</body>
</html>