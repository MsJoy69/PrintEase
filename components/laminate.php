<?php
session_start(); // Always start session

// ✅ Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: components/login.php");
    exit();
}

$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];
$user_id = $_SESSION['user_id'] ?? null; // Use user_id for DB query

// --- NOTIFICATION DATA FETCH ---
// Logic copied from index.php/order.php to fetch the badge count
$unreadCount = 0;
if ($user_id) {
    // Note: Using @ to suppress connection errors if DB is down, for cleaner output.
    $notificationDB = @new mysqli("localhost", "root", "", "notification");
    if (!$notificationDB->connect_error) {
        $countSql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status != 'read'";
        $countStmt = $notificationDB->prepare($countSql);
        
        if ($countStmt) {
            $countStmt->bind_param("i", $user_id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $unreadCount = $countResult->fetch_assoc()['unread_count'] ?? 0;
            $countStmt->close();
        }
        $notificationDB->close();
    }
}

// Define paths for JS/HTML. laminate.php is in 'components/'
$baseAjaxPath = '../index.php'; // Target for mark_read AJAX POST
$notificationsPath = 'notifications.php'; // Target for link navigation

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Laminate Service</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="../css/laminate.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ADDED: Notification Icon Styles from index.php */
        .notification-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: #f1f5f9;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-right: 15px; /* Added spacing */
        }

        .notification-icon:hover {
            background: #e2e8f0;
            transform: scale(1.05);
        }

        .notification-icon i {
            font-size: 20px;
            color: #2f6f56;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 700;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
        }
        
        /* Hide badge if count is 0 */
        .notification-badge[data-count="0"] {
            display: none;
        }
        
        .top-buttons {
            display: flex;
            align-items: center;
        }
    </style>

</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="../image/logo.png" alt="Printease Logo">
        <h2>PRINTEASE</h2>
    </div>
    <ul class="menu">
        <li><a href="../index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
        <li><a href="product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
        <li><a href="order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
        <li><a href="notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
        <li><a href="../components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
        <li><a href="../components/profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
    </ul>
    <div class="logout">
        <a href="login.php?logout=1">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="top-buttons">
            
            <a href="<?php echo $notificationsPath; ?>" class="notification-icon" id="notificationIcon">
                <i class="fa-solid fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" data-count="<?php echo $unreadCount; ?>"><?php echo $unreadCount; ?></span>
            </a>

            <div class="account-dropdown" style="display:flex; align-items:center; gap:8px; font-weight:500; color:#2f6f56; cursor:pointer;">
                <i class="fa-solid fa-circle-user"></i>
                <span><?php echo htmlspecialchars($displayName); ?></span>
                <div class="dropdown-menu">
                    <a href="login.php?logout=1">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <a href="#" id="customBackBtn" class="back-btn-black">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <div class="laminate-wrapper">
        <h2 class="laminate-title"></h2>

        <div class="laminate-content">
            <div class="laminate-preview">
                <img id="mainLaminatePreview" class="laminate-preview-main" src="../image/Laminate.png" alt="Main Preview">
                <div class="thumbnail-row">
                    <button type="button" class="arrow">&lt;</button>
                    <img class="thumb active" src="../image/Laminate.png" onclick="changeLaminatePreview(this)">
                    <img class="thumb" src="../image/laminate(2).jpg" onclick="changeLaminatePreview(this)">
                    <img class="thumb" src="../image/laminate(3).jpg" onclick="changeLaminatePreview(this)">
                    <button type="button" class="arrow">&gt;</button>
                </div>
            </div>

            <form id="laminateForm" class="laminate-form" action="confirmation.php" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                <input type="hidden" name="orderType" value="laminate">
                <div class="form-group">
                    <label>Laminate Size</label>
                    <select id="laminateSize" name="laminateSize">
                        <option value="Select Size">Select Size</option>
                        <option value="Small">Small (2x2 inch)</option>
                        <option value="Medium">Medium (3x3 inch)</option>
                        <option value="Large">Large (4x4 inch)</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Laminate Type</label>
                        <select id="laminateType" name="laminateType">
                            <option value="Select Type">Select Type</option>
                            <option value="Glossy">Glossy</option>
                            <option value="Matte">Matte</option>
                            <option value="Soft Touch">Soft Touch</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Thickness</label>
                        <select id="thickness" name="thickness">
                            <option value="Select Thickness">Select Thickness</option>
                            <option value="3 mil">3 mil</option>
                            <option value="5 mil">5 mil</option>
                            <option value="10 mil">10 mil</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Finish</label>
                    <div class="radio-group">
                        <label><input type="radio" name="laminateFinish" checked value="Glossy"> Glossy</label>
                        <label><input type="radio" name="laminateFinish" value="Matte"> Matte</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label>Upload Design</label>
                       <input type="file" id="laminateFileInput" name="designFile[]" multiple accept=".docx, .png,.jpg,.jpeg,.pdf,.ai,.svg" >
                    </div>

                    <div class="form-group" style="flex:1;">
                        <label>Quantity</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreaseLaminateQty()">−</button>
                            <input type="number" id="laminateQuantity" name="quantity" value="1" min="1">
                            <button type="button" onclick="increaseLaminateQty()">+</button>
                        </div>
                    </div>
                </div>

                <div id="laminateUploadBox" class="upload-note">
                    Accepted file types: .docx, .png, .jpg, .pdf, .ai, .svg
                </div>

                <div class="button-row">
                    <button type="button" onclick="submitLaminateOrder('standard')" class="btn normal">Standard (2 – 3 Days)</button>
                    <button type="button" onclick="submitLaminateOrder('rush')" class="btn rush">Rush Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DESCRIPTION SECTION START -->
    <div class="description-section">
        <div class="description-header">
            <h2>DESCRIPTION</h2>
            <div class="header-line"></div>
        </div>
        
        <div class="description-content">
            <h3>Lamination Services</h3>
            <p>
                Lamination is a protective coating process that encases your documents, photos, or printed materials 
                between layers of plastic film. Our professional lamination services provide superior protection against 
                moisture, dirt, wear, and tear, while enhancing the appearance and extending the lifespan of your important 
                materials. Perfect for ID cards, certificates, menus, posters, and documents that require frequent handling.
            </p>
            
            <div class="specifications">
                <div class="spec-item">
                    <strong>Size:</strong> Small (2x2 inch), Medium (3x3 inch), Large (4x4 inch)
                </div>
                
                <div class="spec-item">
                    <strong>Laminate Type:</strong> Glossy (Most Popular), Matte, Soft Touch
                </div>
                
                <div class="spec-item">
                    <strong>Thickness:</strong> 3 mil (Standard), 5 mil (Heavy Duty), 10 mil (Extra Heavy Duty)
                </div>
                
                <div class="spec-item">
                    <strong>Finish Options:</strong> Glossy or Matte
                </div>
                
                <div class="spec-item">
                    <strong>Material Quality:</strong> Premium UV-resistant plastic film
                </div>
                
                <div class="spec-item">
                    <strong>Accepted File Formats:</strong> .jpg, .jpeg, .png, .pdf, .ai, .svg, .docx
                </div>
            </div>
            
            <div class="finishing-section">
                <h4>Lamination Options:</h4>
                <p><strong>Standard Process:</strong> Hot lamination with sealed edges for maximum protection</p>
            </div>
            
            <div class="additional-finishing">
                <h4>Laminate Type Details:</h4>
                
                <div class="finishing-option">
                    <strong>*Glossy Lamination</strong>
                    <p>
                        A shiny, reflective finish that enhances colors and provides vibrant appearance. Ideal for photos, 
                        posters, and marketing materials. Offers excellent protection against water damage and provides 
                        a professional, eye-catching look with superior color saturation.
                    </p>
                </div>
                
                <div class="finishing-option">
                    <strong>*Matte Lamination</strong>
                    <p>
                        A non-reflective, smooth finish that reduces glare and fingerprints. Perfect for documents, 
                        certificates, and materials that need to be written on. Provides an elegant, sophisticated appearance 
                        while offering the same level of protection as glossy lamination.
                    </p>
                </div>
                
                <div class="finishing-option">
                    <strong>*Soft Touch Lamination</strong>
                    <p>
                        A premium velvet-like texture that provides a luxurious feel and elegant appearance. Features a 
                        matte finish with enhanced tactile properties. Ideal for high-end business cards, invitations, 
                        and premium print materials. Resists fingerprints and scratches while providing superior durability.
                    </p>
                </div>
            </div>
            
            <div class="additional-finishing">
                <h4>Thickness Guide:</h4>
                
                <div class="finishing-option">
                    <strong>3 mil Thickness</strong>
                    <p>
                        Standard thickness suitable for everyday documents, photos, and materials with light to moderate 
                        handling. Flexible and cost-effective solution for general protection needs.
                    </p>
                </div>
                
                <div class="finishing-option">
                    <strong>5 mil Thickness</strong>
                    <p>
                        Heavy-duty protection for frequently handled materials such as menus, ID cards, and reference guides. 
                        Provides enhanced rigidity and durability for materials that require extra strength.
                    </p>
                </div>
                
                <div class="finishing-option">
                    <strong>10 mil Thickness</strong>
                    <p>
                        Extra heavy-duty lamination for maximum protection and rigidity. Ideal for outdoor signage, 
                        floor graphics, luggage tags, and materials exposed to harsh conditions. Provides the highest 
                        level of durability and longevity.
                    </p>
                </div>
            </div>
            
            <div class="delivery-info">
                <h4>Delivery Time:</h4>
                <ul>
                    <li><strong>Standard Delivery:</strong> 2-3 Business Days</li>
                    <li><strong>Rush Order:</strong> Same Day or Next Day (depending on order time and quantity)</li>
                </ul>
            </div>
            
            <div class="note-section">
                <p><em>Note: Lamination is a permanent process and cannot be reversed. Please ensure your documents are 
                final before laminating. For best results, allow freshly printed materials to dry completely before 
                lamination. Slight color shifts may occur due to the lamination process.</em></p>
            </div>
        </div>
    </div>
    <!-- DESCRIPTION SECTION END -->

</div>

<div id="cancelOrderModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>

        <h3>Cancel Order Confirmation</h3>
        <p>You have started an order. Do you want to <strong>cancel</strong> and <strong>leave</strong> this page?</p>
        <div class="modal-buttons">
            <button id="modalContinueBtn" class="modal-btn continue" onclick="continueNavigation()">YES</button>
            <button class="modal-btn stay" onclick="closeModal()">NO</button>
        </div>
    </div>
</div>

<script src="../js/laminate.js"></script>

<script>
    const BASE_AJAX_TARGET = '<?php echo $baseAjaxPath; ?>'; 
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationBadge = document.getElementById('notificationBadge');

    if (notificationIcon) {
        notificationIcon.addEventListener('click', function(e) {
            // 1. Prevent immediate navigation
            e.preventDefault(); 
            
            const targetUrl = e.currentTarget.href; // Get the target navigation URL (notifications.php)
            
            // 2. Client-side: Immediately clear the badge
            if (notificationBadge) {
                notificationBadge.textContent = '0';
                notificationBadge.setAttribute('data-count', '0'); 
            }
            
            // 3. Server-side: Send AJAX request to index.php to mark notifications as read
            fetch(BASE_AJAX_TARGET, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                body: 'action=mark_read' 
            })
            .then(response => {
                if (response.ok) {
                     // 4. Navigate only after server processing
                    window.location.href = targetUrl; 
                } else {
                    console.error('Server error marking notifications as read:', response.statusText);
                    // Navigate anyway
                    window.location.href = targetUrl; 
                }
            })
            .catch(error => {
                console.error('Network error during notification update:', error);
                // Navigate anyway
                window.location.href = targetUrl;
            });
        });
    }
</script>

</body>
</html>