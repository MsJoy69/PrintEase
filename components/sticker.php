<?php
session_start(); // Start session to check if user is logged in
// NO LOGIN REDIRECT HERE - Let users browse freely
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Sticker Service</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="../css/sticker.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- MODIFIED: Inline CSS for Modal to match the desired design -->
    <style>
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7); /* Darker overlay */
            display: flex; 
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal.is-active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background-color: #ffffff;
            /* Added close button styling */
            position: relative; 
            margin: auto;
            padding: 40px 30px 30px 30px; /* Adjusted padding for better internal spacing */
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal.is-active .modal-content {
            transform: translateY(0);
        }

        /* Close button (X) top right */
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            font-weight: 300;
        }
        .modal-close:hover {
            color: #333;
        }


        .modal-content h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.6rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .modal-content p {
            margin-bottom: 30px;
            color: #666;
            line-height: 1.6;
            font-size: 1.05rem;
        }

        .modal-buttons {
            display: flex;
            flex-direction: column; /* Stack buttons vertically for better mobile view */
            gap: 15px;
        }

        .modal-btn {
            padding: 18px 20px; /* Increased vertical padding */
            border: none;
            border-radius: 8px; /* Slightly less rounded */
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            flex-grow: 1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 1.0rem;
        }

        /* NO, CONTINUE ORDERING (Teal/Dark Green) */
        .modal-btn.stay {
            background-color: #00897b; /* Teal color from image */
            color: white;
            order: 2; /* Move this to the bottom for the desired layout */
        }
        .modal-btn.stay:hover {
            background-color: #00796b;
            box-shadow: 0 4px 10px rgba(0, 137, 123, 0.4);
        }

        /* YES, CANCEL ORDER (Orange) */
        .modal-btn.continue {
            background-color: #ffffff; /* Orange color from image */
            color: #333;
            order: 1; /* Keep this on top for the desired layout */
            border: 2px solid #00796b;
        }
        .modal-btn.continue:hover {
            background-color: #00796b;
            box-shadow: #00796b 0 4px 10px rgba(0, 121, 107, 0.4);
            color: #ffffff;
        }

        /* Make buttons horizontal on desktop */
        @media (min-width: 600px) {
            .modal-buttons {
                flex-direction: row;
                gap: 15px;
            }
            .modal-btn {
                order: initial !important; /* Reset order for horizontal layout */
            }
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
        <li><a href="#" onclick="checkCancellation('notification.php')"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
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
                <span><?php echo htmlspecialchars(string: $displayName); ?></span>
                <div class="dropdown-menu">
                    <a href="components/login.php?logout=1">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <a href="#" onclick="checkCancellation('product.php')" class="back-btn-black">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <!-- Sticker Form -->
    <div class="sticker-wrapper">
        <div class="sticker-content">
            <!-- Left Side: Image Preview -->
            <div class="sticker-preview">
                <img id="mainStickerPreview" class="main-preview" src="../image/Sticker.jpg" alt="Main Preview">
                <div class="thumbnail-row">
                    <button class="arrow">&lt;</button>
                    <img class="thumb active" src="../image/Sticker1.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/Sticker2.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/Sticker3.jpg" onclick="changePreview(this)">
                    <button class="arrow">&gt;</button>
                </div>
            </div>

            <!-- Right Side: Form -->
            <form id="stickerForm" class="sticker-form" action="confirmation.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="orderType" value="sticker">
                <div class="form-group">
                    <label>Sticker Size</label>
                    <select id="size" name="size" onchange="toggleCustomSize()" required>
                        <option value="">Select Size</option>
                        <option>Small (2x2 inch)</option>
                        <option>Medium (3x3 inch)</option>
                        <option>Large (4x4 inch)</option>
                        <option>A5 (5.8x8.3 inch)</option>
                        <option value="Custom Size">Custom Size</option>
                    </select>
                </div>
                
                <!-- NEW: Custom Size Input Group -->
                <div id="customSizeInputGroup" class="form-group" style="display:none; margin-top: 10px;">
                    <label>Custom Dimensions (e.g., 5.5 x 7.0 in)</label>
                    <input type="text" id="customSize" name="customSize" placeholder="Enter W x H">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Material</label>
                        <select id="material" name="material" required>
                            <option value="">Select</option>
                            <option>Glossy Vinyl</option>
                            <option>Matte Vinyl</option>
                            <option>Transparent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cut Type</label>
                        <select id="cutType" name="cutType" required>
                            <option value="">Select</option>
                            <option>Die-Cut</option>
                            <option>Kiss-Cut</option>
                            <option>Sheet</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sticker Type</label>
                    <div class="radio-group">
                        <label><input type="radio" name="stickerType" checked value="Indoor"> Indoor</label>
                        <label><input type="radio" name="stickerType" value="Outdoor"> Outdoor</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Upload Design</label>
                        <input type="file" id="fileInput" name="designFile[]" multiple accept=".docx, .png,.jpg,.jpeg,.pdf,.ai,.svg">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Quantity</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreaseQty()">−</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1">
                            <button type="button" onclick="increaseQty()">+</button>
                        </div>
                    </div>
                </div>

                <!-- File types info -->
                <div id="uploadBox" class="upload-note">
                    Accepted file types: .docx, .png, .jpg, .pdf, .ai, .svg
                </div>

                <!-- Buttons for order type -->
                <div class="button-row">
                    <button type="button" onclick="submitOrder('standard')" name="orderSpeed" value="standard" class="btn normal">Standard (2 – 3 Days)</button>
                    <button type="button" onclick="submitOrder('rush')" name="orderSpeed" value="rush" class="btn rush">Rush Order</button>
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
            <h3>Custom Sticker Printing Services</h3>
            <p>
                Custom stickers are versatile adhesive labels perfect for branding, decoration, packaging, and promotional purposes. 
                Our high-quality sticker printing services use premium vinyl materials and advanced printing technology to deliver 
                vibrant colors and long-lasting durability. Whether you need stickers for business branding, product labels, 
                event promotions, or personal projects, we provide professional results that make your designs stand out.
            </p>
            
            <div class="specifications">
                <div class="spec-item">
                    <strong>Size:</strong> Small (2x2"), Medium (3x3"), Large (4x4"), A5 (5.8x8.3"), or Custom Size
                </div>
                
                <div class="spec-item">
                    <strong>Material:</strong> Glossy Vinyl (Most Popular), Matte Vinyl, Transparent
                </div>
                
                <div class="spec-item">
                    <strong>Cut Type:</strong> Die-Cut, Kiss-Cut, Sheet
                </div>
                
                <div class="spec-item">
                    <strong>Sticker Type:</strong> Indoor or Outdoor (Weather-resistant)
                </div>
                
                <div class="spec-item">
                    <strong>Material Quality:</strong> Premium vinyl with strong adhesive backing
                </div>
                
                <div class="spec-item">
                    <strong>Accepted File Formats:</strong> .jpg, .jpeg, .png, .pdf, .ai, .svg, .docx
                </div>
            </div>

            <!-- TABBED NAVIGATION -->
            <div class="description-tabs">
                <button class="tab-button active" onclick="openStickerTab(event, 'materialDetails')">Material Details</button>
                <button class="tab-button" onclick="openStickerTab(event, 'cutTypes')">Cut Types</button>
                <button class="tab-button" onclick="openStickerTab(event, 'stickerTypes')">Sticker Types</button>
                <button class="tab-button" onclick="openStickerTab(event, 'deliveryTime')">Delivery Time</button>
            </div>

            <!-- TAB CONTENT: Material Details -->
            <div id="materialDetails" class="tab-content active">
                <div class="additional-finishing">
                    <h4>Material Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Glossy Vinyl</strong>
                        <p>
                            High-shine finish that makes colors pop with vibrant intensity. The glossy surface enhances color 
                            saturation and provides a professional, eye-catching appearance. Perfect for logos, product labels, 
                            and promotional stickers. Durable and water-resistant for long-lasting use.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Matte Vinyl</strong>
                        <p>
                            Non-reflective smooth finish that offers an elegant, sophisticated look. Reduces glare and 
                            fingerprints while providing excellent writability. Ideal for informational labels, packaging, 
                            and designs that require a subtle, professional appearance. Offers the same durability as glossy vinyl.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Transparent/Clear Vinyl</strong>
                        <p>
                            See-through material that creates a "no background" effect, making your design appear printed 
                            directly on the surface. Perfect for window decals, product labels, and creative applications 
                            where you want the background to show through. UV-resistant and weatherproof.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Cut Types -->
            <div id="cutTypes" class="tab-content">
                <div class="additional-finishing">
                    <h4>Cut Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Die-Cut Stickers</strong>
                        <p>
                            Custom-shaped stickers cut precisely around your design outline. No visible border or background, 
                            creating a clean, professional look. Perfect for logos, characters, and unique shapes. Each sticker 
                            is individually cut to match your design's exact contours.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Kiss-Cut Stickers</strong>
                        <p>
                            Stickers cut through the top vinyl layer only, leaving the backing paper intact for easy peeling. 
                            Can be supplied on sheets with multiple stickers or as individual pieces. Ideal for handing out 
                            multiple stickers, creating sticker sheets, or maintaining organization during application.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Sheet Stickers</strong>
                        <p>
                            Multiple stickers printed and cut on a single sheet with backing paper. Convenient for distributing 
                            several designs at once or for retail packaging. Great for sticker packs, promotional giveaways, 
                            or creating organized collections of related designs.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Sticker Types -->
            <div id="stickerTypes" class="tab-content">
                <div class="additional-finishing">
                    <h4>Sticker Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Indoor Stickers</strong>
                        <p>
                            Designed for interior applications on smooth, clean surfaces. Perfect for laptops, notebooks, 
                            water bottles, phone cases, and indoor signage. Features strong adhesive for secure placement 
                            and easy removal without residue. Maintains vibrant colors in indoor environments.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Outdoor Stickers</strong>
                        <p>
                            Weather-resistant stickers built to withstand outdoor conditions. Features UV-resistant ink that 
                            prevents fading in sunlight, waterproof coating, and extra-strong adhesive for secure placement 
                            on vehicles, windows, outdoor equipment, and signage. Durable in rain, snow, and extreme temperatures. 
                            Typically lasts 3-5 years outdoors.
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
                        <p>Orders placed before 12:00 PM may qualify for same-day delivery. Orders placed after 12:00 PM will be processed for next-day delivery. Large quantity orders (500+ stickers) may require additional processing time. Complex die-cut shapes may extend production time by 1 business day.</p>
                    </div>
                </div>
            </div>
            
            <div class="note-section">
                <p><em>Note: For best results, provide high-resolution images (300 DPI minimum) in vector format when possible. 
                Colors may vary slightly from screen to print due to color profile differences. Custom die-cut shapes with 
                intricate details may be adjusted to ensure clean cutting. Outdoor stickers require 24-hour cure time before 
                exposure to water or extreme conditions.</em></p>
            </div>
        </div>
    </div>
    <!-- DESCRIPTION SECTION END -->

</div>

<!-- MODIFIED: Cancellation Confirmation Modal content -->
<div id="cancelOrderModal" class="modal">
    <div class="modal-content">
        <!-- Close button (X) -->
        <span class="modal-close" onclick="closeModal()">&times;</span>

        <h3>Cancel Order Confirmation</h3>
        <p>You have started an order. Do you want to <strong>cancel</strong> and <strong>leave</strong> this page?</p>
        <div class="modal-buttons">
            <button id="modalContinueBtn" class="modal-btn continue" onclick="continueNavigation()">YES</button>
            <button class="modal-btn stay" onclick="closeModal()">NO</button>
        </div>
    </div>
</div>

<script src="../js/sticker.js"></script>

<script>
function openStickerTab(evt, tabName) {
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