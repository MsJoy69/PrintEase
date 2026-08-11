<?php
session_start();
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Postcard Service</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="../css/postcard.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Inline Modal CSS (Copied from sticker.php for consistency) -->
    <style>
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto;
            background-color: rgba(0,0,0,0.7); display: flex; 
            align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
        }
        .modal.is-active { opacity: 1; pointer-events: auto; }
        .modal-content {
            background-color: #ffffff; position: relative; margin: auto;
            padding: 40px 30px 30px 30px; border-radius: 12px;
            width: 90%; max-width: 450px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center; transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        .modal.is-active .modal-content { transform: translateY(0); }
        .modal-close {
            position: absolute; top: 15px; right: 15px; font-size: 24px;
            color: #aaa; cursor: pointer; font-weight: 300;
        }
        .modal-close:hover { color: #333; }
        .modal-content h3 {
            margin-top: 0; color: #333; font-size: 1.6rem;
            margin-bottom: 20px; font-weight: 700;
        }
        .modal-content p {
            margin-bottom: 30px; color: #666;
            line-height: 1.6; font-size: 1.05rem;
        }
        .modal-buttons { display: flex; flex-direction: column; gap: 15px; }
        .modal-btn {
            padding: 18px 20px; border: none; border-radius: 8px;
            cursor: pointer; font-weight: 700; transition: all 0.2s ease;
            flex-grow: 1; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 1.0rem;
        }
        .modal-btn.stay { background-color: #00897b; color: white; order: 2; }
        .modal-btn.stay:hover { background-color: #00796b; box-shadow: 0 4px 10px rgba(0, 137, 123, 0.4); }
        .modal-btn.continue {
            background-color: #ffffff;
            color: #333;
            order: 1;
            border: 2px solid #00796b;
        }
        .modal-btn.continue:hover {
            background-color: #00796b;
            box-shadow: #00796b 0 4px 10px rgba(0, 121, 107, 0.4);
            color: #ffffff;
        }
        @media (min-width: 600px) {
            .modal-buttons { flex-direction: row; gap: 15px; }
            .modal-btn { order: initial !important; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <img src="../image/logo.png" alt="Printease Logo">
        <h2>PRINTEASE</h2>
    </div>
    <ul class="menu">
        <li><a href="#" onclick="checkCancellation('../index.php')"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
        <li><a href="#" onclick="checkCancellation('product.php')"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
        <li><a href="#" onclick="checkCancellation('order.php')"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
        <li><a href="#" onclick="checkCancellation('notifications.php')"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
         <li><a href="<?php echo $componentPath; ?>message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
        <li><a href="#" onclick="checkCancellation('../components/profile.php')"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
    </ul>
    <div class="logout">
        <a href="components/login.php?logout=1">Logout</a>
    </div>
</div>

<!-- Main content -->
<div class="main-content">
    <div class="topbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="top-buttons">
            <div class="account-dropdown">
                <i class="fa-solid fa-circle-user"></i>
                <span><?php echo htmlspecialchars($displayName); ?></span>
                <div class="dropdown-menu">
                    <a href="components/login.php?logout=1">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button (Matches Sticker Design) -->
    <a href="#" onclick="checkCancellation('product.php')" class="back-btn-black">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <!-- Postcard Wrapper -->
    <div class="postcard-wrapper">
        <div class="postcard-content">
            <!-- Left Side: Image Preview -->
            <div class="postcard-preview">
                <img id="mainPostcardPreview" class="main-preview" src="../image/postcard.jpg" alt="Main Preview">
                <div class="thumbnail-row">
                    <button class="arrow">&lt;</button>
                    <img class="thumb active" src="../image/postcard.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/postcard1.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/Postcard3.jpg" onclick="changePreview(this)">
                    <button class="arrow">&gt;</button>
                </div>
            </div>

            <!-- Right Side: Form -->
            <form id="postcardForm" class="postcard-form" action="confirmation.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="orderType" value="postcard">

                <!-- Type -->
                <div class="form-group">
                    <label>Postcard Type</label>
                    <select id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option>Photo Postcard</option>
                        <option>Art / Illustration Postcard</option>
                        <option>Business / Promotional Postcard</option>
                        <option>Travel / Scenic Postcard</option>
                        <option>Vintage / Retro Postcard</option>
                        <option>Custom Message Postcard</option>
                        <option>Event Invitation Postcard</option>
                        <option>Holiday / Greeting Postcard</option>
                    </select>
                </div>

                <!-- Size -->
                <div class="form-group">
                    <label>Size</label>
                    <select id="size" name="size" onchange="toggleCustomSize()" required>
                        <option value="">Select Size</option>
                        <option>4" x 6" (Standard)</option>
                        <option>5" x 7"</option>
                        <option>6" x 9"</option>
                        <option value="Custom Size">Custom Size</option>
                    </select>
                </div>

                <!-- Custom Size Input (Hidden by default, matches Sticker behavior) -->
                <div id="customSizeInputGroup" class="form-group" style="display:none;">
                    <label>Custom Dimensions (e.g., 5.5 x 7.0 in)</label>
                    <input type="text" id="customSize" name="customSize" placeholder="Enter W x H">
                </div>

                <!-- Paper and Finish Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Paper Type</label>
                        <select id="paperType" name="paperType" required>
                            <option value="">Select</option>
                            <option>Glossy Cardstock (High Shine)</option>
                            <option>Matte Cardstock (Smooth Finish)</option>
                            <option>Recycled Paper (Eco-Friendly)</option>
                            <option>Textured Linen Paper</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Finish</label>
                        <select id="finish" name="finish" required>
                            <option value="">Select</option>
                            <option>Single-Sided Print</option>
                            <option>Double-Sided Print</option>
                            <option>UV Gloss Coated</option>
                            <option>Soft Touch Matte</option>
                        </select>
                    </div>
                </div>

                <!-- Orientation -->
                <div class="form-group">
                    <label>Orientation</label>
                    <div class="radio-group">
                        <label><input type="radio" name="orientation" checked value="Portrait"> Portrait</label>
                        <label><input type="radio" name="orientation" value="Landscape"> Landscape</label>
                    </div>
                </div>

                <!-- Upload and Quantity Row -->
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Upload Design</label>
                        <input type="file" id="fileInput" name="designFile[]" multiple accept=".png,.jpg,.jpeg,.pdf,.ai,.psd">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Quantity</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreaseQty()">−</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" step="1">
                            <button type="button" onclick="increaseQty()">+</button>
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div id="uploadBox" class="upload-note">
                    Accepted file types: .png, .jpg, .jpeg, .pdf, .ai, .psd
                </div>

                <!-- Buttons -->
                <div class="button-row">
                    <button type="button" onclick="submitOrder('standard')" name="orderSpeed" value="standard" class="btn normal">Standard (2 – 3 Days)</button>
                    <button type="button" onclick="submitOrder('rush')" name="orderSpeed" value="rush" class="btn rush">Rush</button>
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
            <h3>Custom Postcard Printing Services</h3>
            <p>
                Custom postcards are versatile communication tools perfect for personal greetings, business promotions, event 
                invitations, and travel keepsakes. Our premium postcard printing services combine high-quality cardstock with 
                vibrant printing technology to create memorable pieces that leave lasting impressions. Whether sharing vacation 
                memories, promoting your business, or sending special invitations, our postcards deliver professional quality 
                with personal touches that make your message stand out.
            </p>
            
            <div class="specifications">
                <div class="spec-item">
                    <strong>Type:</strong> Photo, Art/Illustration, Business/Promotional, Travel/Scenic, Vintage/Retro, Custom Message, Event Invitation, Holiday/Greeting
                </div>
                
                <div class="spec-item">
                    <strong>Size:</strong> 4"x6" (Standard), 5"x7", 6"x9", or Custom Size
                </div>
                
                <div class="spec-item">
                    <strong>Paper Type:</strong> Glossy Cardstock (Most Popular), Matte Cardstock, Recycled Paper, Textured Linen Paper
                </div>
                
                <div class="spec-item">
                    <strong>Finish:</strong> Single-Sided Print, Double-Sided Print, UV Gloss Coated, Soft Touch Matte
                </div>
                
                <div class="spec-item">
                    <strong>Orientation:</strong> Portrait or Landscape
                </div>
                
                <div class="spec-item">
                    <strong>Paper Weight:</strong> Premium 14-16pt cardstock for durability
                </div>
                
                <div class="spec-item">
                    <strong>Accepted File Formats:</strong> .jpg, .jpeg, .png, .pdf, .ai, .psd
                </div>
            </div>

            <!-- TABBED NAVIGATION -->
            <div class="description-tabs">
                <button class="tab-button active" onclick="openPostcardTab(event, 'postcardTypes')">Postcard Types</button>
                <button class="tab-button" onclick="openPostcardTab(event, 'paperOptions')">Paper Options</button>
                <button class="tab-button" onclick="openPostcardTab(event, 'finishOptions')">Finish Options</button>
                <button class="tab-button" onclick="openPostcardTab(event, 'deliveryTime')">Delivery Time</button>
            </div>

            <!-- TAB CONTENT: Postcard Types -->
            <div id="postcardTypes" class="tab-content active">
                <div class="additional-finishing">
                    <h4>Postcard Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Photo Postcard</strong>
                        <p>
                            Perfect for sharing personal memories, vacation photos, or family moments. High-quality photo printing 
                            preserves image clarity and color vibrancy, making your cherished moments tangible and shareable.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Business / Promotional Postcard</strong>
                        <p>
                            Effective marketing tool for announcing sales, events, new products, or services. Eye-catching designs 
                            and professional finish help your business message stand out in the mailbox and drive customer engagement.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Travel / Scenic Postcard</strong>
                        <p>
                            Traditional postcards featuring beautiful landscapes, landmarks, or destinations. Perfect for tourists 
                            and travelers to share their experiences and send greetings from their journeys.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Event Invitation Postcard</strong>
                        <p>
                            Stylish and convenient way to invite guests to weddings, parties, grand openings, or special events. 
                            Combines visual appeal with practical information delivery for memorable invitations.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Paper Options -->
            <div id="paperOptions" class="tab-content">
                <div class="additional-finishing">
                    <h4>Paper Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Glossy Cardstock (High Shine)</strong>
                        <p>
                            Premium high-gloss finish that makes colors appear vibrant and images sharp. The reflective surface 
                            enhances photos and colorful designs, providing a professional, polished look. Most popular choice 
                            for photo postcards and promotional materials. Water-resistant and durable.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Matte Cardstock (Smooth Finish)</strong>
                        <p>
                            Non-reflective smooth surface that reduces glare and provides an elegant, sophisticated appearance. 
                            Easy to write on with pen or marker, making it perfect for personal messages. Ideal for business 
                            communications and event invitations where a refined look is desired.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Recycled Paper (Eco-Friendly)</strong>
                        <p>
                            Environmentally conscious option made from recycled materials. Maintains excellent print quality 
                            while supporting sustainability efforts. Perfect for eco-friendly brands and individuals who want 
                            to reduce their environmental impact without compromising on quality.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Textured Linen Paper</strong>
                        <p>
                            Premium paper with a distinctive linen texture that adds a tactile, luxurious feel. Creates an 
                            upscale, sophisticated impression perfect for high-end invitations, boutique marketing, and special 
                            occasions. The textured surface adds visual and physical depth to your design.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Finish Options -->
            <div id="finishOptions" class="tab-content">
                <div class="additional-finishing">
                    <h4>Finish Options Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Single-Sided Print</strong>
                        <p>
                            Design printed on one side only, with blank back for writing personal messages or addresses. 
                            Traditional postcard format that allows customization of the message side. Cost-effective option 
                            for personal greetings and basic promotional materials.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Double-Sided Print</strong>
                        <p>
                            Full-color printing on both sides maximizes design space and impact. Front side features your 
                            main design while back can include additional information, coupons, or messaging. Perfect for 
                            marketing campaigns and detailed event invitations.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*UV Gloss Coated</strong>
                        <p>
                            Premium ultra-glossy coating applied with UV curing process for maximum shine and protection. 
                            Enhances color vibrancy, provides superior scratch resistance, and creates a high-end professional 
                            appearance. Ideal for making your postcards stand out with stunning visual impact.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Soft Touch Matte</strong>
                        <p>
                            Luxurious velvety coating that provides a premium tactile experience. Smooth, non-reflective surface 
                            with enhanced durability and resistance to fingerprints. Creates an upscale, sophisticated impression 
                            perfect for high-end branding and elegant designs.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Delivery Time -->
            <div id="deliveryTime" class="tab-content">
                <div class="delivery-info">
                    <h4>Delivery Time:</h4>
                    <ul>
                        <li><strong>Standard Delivery:</strong> 2-3 Business Days</li>
                        <li><strong>Rush Order:</strong> Same Day or Next Day (depending on order time and quantity)</li>
                    </ul>
                    <div class="delivery-note">
                        <p><strong>Rush Order Details:</strong></p>
                        <p>Orders placed before 12:00 PM may qualify for same-day delivery. Orders placed after 12:00 PM will be 
                        processed for next-day delivery. Large quantity orders (1000+ postcards) may require additional processing 
                        time. Double-sided prints and specialty finishes may extend production time by 1 business day.</p>
                    </div>
                </div>
            </div>
            
            <div class="note-section">
                <p><em>Note: For optimal print quality, please provide high-resolution images (300 DPI minimum). Colors may vary 
                slightly from screen to print due to color profile differences. USPS postcard regulations require a minimum size 
                of 3.5" x 5" and maximum of 4.25" x 6" for standard postcard mailing rates. Larger sizes will require additional 
                postage. Allow specialty coatings to cure for 24 hours before handling or mailing.</em></p>
            </div>
        </div>
    </div>
    <!-- DESCRIPTION SECTION END -->

</div>

<!-- Cancellation Confirmation Modal (Copied from sticker.php) -->
<div id="cancelOrderModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h3>Cancel Order Confirmation</h3>
        <p>You have started an order. Do you want to <strong>cancel</strong> and <strong>leave</strong> this page? All entered details will be lost.</p>
        <div class="modal-buttons">
            <button id="modalContinueBtn" class="modal-btn continue" onclick="continueNavigation()">YES, CANCEL ORDER</button>
            <button class="modal-btn stay" onclick="closeModal()">NO, CONTINUE ORDERING</button>
        </div>
    </div>
</div>

<script src="../js/postcard.js?v=4"></script>

<script>
function openPostcardTab(evt, tabName) {
    // Hide all tab contents
    var tabContents = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
    }
    
    // Remove active class from all buttons
    var tabButtons = document.getElementsByClassName("tab-button");
    for (var i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove("active");
    }
    
    // Show current tab and mark button as active
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>

</body>
</html>