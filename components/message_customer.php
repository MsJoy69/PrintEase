<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("../db.php"); 

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$profileImage = $_SESSION['profile_pic'] ?? "../image/defaultPic.png";

// We still fetch initial messages to render the page quickly on load (SSR)
// This makes the UI feel faster than waiting for the first AJAX call.
$messages = [];
$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Messages</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="message_customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../image/logo.png" alt="Printease Logo">
            <h2>PRINTEASE</h2>
        </div>
        <ul class="menu">
            <li><a href="../index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
            <li><a href="product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
            <li><a href="order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
            <li><a href="notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
            <li><a href="message_customer.php" class="active"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
        </ul>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search...">
            </div>
            <div class="top-buttons">
         		<div class="account-dropdown">
                    <div class="account-trigger">
                        <span><?php echo htmlspecialchars($displayName); ?></span>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="<?php echo $componentPath; ?>profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                        <a href="<?php echo $componentPath; ?>product.php"><i class="fa-solid fa-box"></i> Product Catalog</a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="logout.php" class="logout-link">
                            <i class="fa-solid fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
			    </div>
            </div>
        </div>

        <!-- MESSAGING UI CONTAINER -->
        <div class="message-container">
            
            <div class="contacts-sidebar">
                <div class="contacts-header" style="padding: 20px;">
                    <h3>Support</h3>
                </div>
                <div class="contacts-list">
                    <div class="contact-item active" style="padding: 15px; background: #f0f7f4; border-left: 4px solid #2f6f56; display: flex; gap: 10px; align-items: center;">
                        <div style="width: 40px; height: 40px; background: #2f6f56; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div class="contact-info">
                            <h4 style="margin: 0; font-size: 1rem;">Admin Support</h4>
                            <p style="margin: 0; font-size: 0.8rem; color: #666;">Always here to help</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDEBAR: Chat Window -->
            <div class="chat-area">
                
                <div class="chat-header">
                    <div class="chat-user-info" style="display: flex; gap: 10px; align-items: center;">
                        <img src="../image/logo.png" onerror="this.src='../image/defaultPic.png'" alt="Admin" style="width: 35px; height: 35px; border-radius: 50%;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem;">PrintEase Admin</h3>
                            <span style="font-size: 0.8rem; color: #2f6f56;">● Online</span>
                        </div>
                    </div>
                </div>

                <!-- Messages Display -->
                <div class="chat-messages" id="chatMessages">
                    <!-- Determine the last ID for JS to pick up -->
                    <?php 
                    $last_id = 0;
                    if (empty($messages)): ?>
                        <div id="no-msg-placeholder" style="text-align: center; color: #aaa; margin-top: 20px;">No messages yet. Start the conversation!</div>
                    <?php endif; ?>

                    <?php foreach($messages as $msg): 
                        if($msg['id'] > $last_id) $last_id = $msg['id'];
                    ?>
                        <div class="message-bubble <?php echo htmlspecialchars($msg['sender_type']); ?>">
                            <?php echo htmlspecialchars($msg['message']); ?>
                            
                            <?php if(!empty($msg['attachment'])): ?>
                                <br><a href="../components/uploads/<?php echo htmlspecialchars($msg['attachment']); ?>" target="_blank" style="color: inherit; text-decoration: underline; font-size: 0.9em;">
                                    <i class="fa-solid fa-paperclip"></i> View Attachment
                                </a>
                            <?php endif; ?>

                            <span class="message-time"><?php echo date("h:i A", strtotime($msg['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Input Area -->
                <div class="chat-input-area">
                    <form id="chatForm" enctype="multipart/form-data" class="chat-form">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="target_user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="sender_type" value="user">
                        
                        <label for="attachment" class="file-upload-label" title="Attach File">
                            <i class="fa-solid fa-paperclip"></i>
                        </label>
                        <input type="file" name="attachment" id="attachment" style="display: none;" onchange="updateFileStatus(this)">
                        
                        <input type="text" name="message" id="messageInput" placeholder="Type a message..." autocomplete="off">
                        
                        <button type="submit" class="send-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                    <div id="file-status" style="font-size: 0.8rem; color: #2f6f56; margin-left: 45px; margin-top: 5px; display: none;"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- AJAX & Long Polling Script -->
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const fileInput = document.getElementById('attachment');
        let lastMessageId = <?php echo $last_id; ?>;
        let isPolling = true;

        // --- 1. AbortController to FIX LAG ---
        // This allows us to kill the connection immediately when you switch pages
        let pollingController = new AbortController();
        
        // Auto-scroll on load
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // 2. Send Message via AJAX
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageVal = messageInput.value.trim();
            const hasFile = fileInput.files.length > 0;

            if (!messageVal && !hasFile) return;

            const formData = new FormData(chatForm);

            fetch('../chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Clear inputs
                    messageInput.value = '';
                    fileInput.value = '';
                    updateFileStatus(fileInput);
                    // The message will appear via the polling mechanism
                } else {
                    alert("Error sending message");
                }
            })
            .catch(err => console.error("Error:", err));
        });

        // 3. Long Polling Function (Optimized)
        function pollMessages() {
            if(!isPolling) return;

            // Reset the controller for this new request
            pollingController = new AbortController();
            const signal = pollingController.signal;

            // Add timestamp to bypass cache
            const url = `../chat_handler.php?action=fetch&last_id=${lastMessageId}&user_id=<?php echo $user_id; ?>&t=${Date.now()}`;

            fetch(url, { signal: signal })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.messages.length > 0) {
                    
                    const placeholder = document.getElementById('no-msg-placeholder');
                    if (placeholder) placeholder.remove();

                    data.messages.forEach(msg => {
                        if(msg.id <= lastMessageId) return; 
                        appendMessage(msg);
                        lastMessageId = msg.id; 
                    });
                    
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(err => {
                // Ignore errors caused by us aborting the request on page switch
                if (err.name === 'AbortError') {
                    console.log('Polling stopped for navigation.');
                    return;
                }
                
                console.error("Polling Error:", err);
                // Wait 5s on error before retry
                setTimeout(pollMessages, 5000);
            })
            .then(() => {
                // If we are still on the page, poll again
                if (isPolling) pollMessages();
            });
        }

        function appendMessage(msg) {
            const div = document.createElement('div');
            div.className = `message-bubble ${msg.sender_type}`;
            
            let html = msg.message;
            if (msg.attachment) {
                html += `<br><a href="../components/uploads/${msg.attachment}" target="_blank" style="color: inherit; text-decoration: underline; font-size: 0.9em;">
                            <i class="fa-solid fa-paperclip"></i> View Attachment
                         </a>`;
            }
            html += `<span class="message-time">${msg.created_at}</span>`;
            
            div.innerHTML = html;
            chatMessages.appendChild(div);
        }

        function updateFileStatus(input) {
            const statusDiv = document.getElementById('file-status');
            if (input.files && input.files[0]) {
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<i class="fa-solid fa-file"></i> ' + input.files[0].name;
            } else {
                statusDiv.style.display = 'none';
            }
        }

        // --- 4. STOP POLLING WHEN LEAVING PAGE ---
        // This is the magic fix for the "stuck" page issue
        window.addEventListener('beforeunload', () => {
            isPolling = false;
            if (pollingController) {
                pollingController.abort(); // Kill the connection immediately
            }
        });

        // Start Polling
        pollMessages();

    </script>
</body>
</html>