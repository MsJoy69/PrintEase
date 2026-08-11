<?php
session_start();
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Magazine Service</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="../css/magazine.css?v=2"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Inline Modal CSS -->
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

    <!-- Back Button -->
    <a href="#" onclick="checkCancellation('product.php')" class="back-btn-black">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <!-- Magazine Wrapper -->
    <div class="magazine-wrapper">
        <div class="magazine-content">
            <!-- Left Side: Image Preview -->
            <div class="magazine-preview">
                <img id="mainMagazinePreview" class="main-preview" src="../image/Magazine 2.jpg" alt="Main Preview">
                <div class="thumbnail-row">
                    <button class="arrow">&lt;</button>
                    <img class="thumb active" src="../image/Magazine1.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/Magazine 2.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/Magazine3.jpg" onclick="changePreview(this)">
                    <button class="arrow">&gt;</button>
                </div>
            </div>

            <!-- Right Side: Form -->
            <form id="magazineForm" class="magazine-form" action="confirmation.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="orderType" value="magazine">

                <!-- Size -->
                <div class="form-group">
                    <label>Magazine Size (Base Price)</label>
                    <select id="size" name="size" required>
                        <option value="">Select Size</option>
                        <option>8.5" x 11" (Standard)</option>
                        <option>5.5" x 8.5" (Digest)</option>
                        <option>9" x 12" (Large Format)</option>
                    </select>
                </div>

                <!-- Paper Options Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Cover Paper</label>
                        <select id="coverPaper" name="coverPaper" required>
                            <option value="">Select</option>
                            <option>Self-Cover (Same as Inside)</option>
                            <option>100lb Glossy Cardstock</option>
                            <option>100lb Matte Cardstock</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Inside Paper</label>
                        <select id="insidePaper" name="insidePaper" required>
                            <option value="">Select</option>
                            <option>70lb Uncoated Paper</option>
                            <option>80lb Glossy Paper</option>
                            <option>80lb Matte Paper</option>
                        </select>
                    </div>
                </div>
                
                <!-- Binding -->
                <div class="form-group">
                    <label>Binding Type</label>
                    <select id="binding" name="binding" required>
                        <option value="">Select Binding</option>
                        <option>Saddle Stitch (Stapled)</option>
                        <option>Perfect Bound (Glued)</option>
                    </select>
                </div>

                <!-- Page Count and Quantity Row -->
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Total Page Count (Min: 8)</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreasePageCount()">−</button>
                            <input type="number" id="pageCount" name="pageCount" value="8" min="8" step="4">
                            <button type="button" onclick="increasePageCount()">+</button>
                        </div>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Quantity (Min: 1)</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreaseQty()">−</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" step="1">
                            <button type="button" onclick="increaseQty()">+</button>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Design -->
                <div class="form-group">
                    <label>Upload Cover & Inside Pages (PDF preferred)</label>
                    <input type="file" id="fileInput" name="designFile[]" multiple accept=".png,.jpg,.jpeg,.pdf,.ai,.psd">
                </div>

                <!-- Note -->
                <div id="uploadBox" class="upload-note">
                    Accepted file types: .pdf, .ai, .psd, .png, .jpg
                </div>

                <!-- Buttons -->
                <div class="button-row">
                    <button type="button" onclick="submitOrder('standard')" name="orderSpeed" value="standard" class="btn normal">Standard (5 – 7 Days)</button>
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
            <h3>Magazine Printing Services</h3>
            <p>
                A magazine is a periodical publication printed on gloss-coated and matte paper, designed to inform, entertain, 
                and engage readers with a variety of content. Magazines are generally published on a regular schedule and financed 
                by advertising, purchase price, prepaid subscriptions, or a combination of the three. Our professional magazine 
                printing services deliver high-quality publications with vibrant colors, sharp imagery, and durable binding options 
                perfect for corporate publications, community newsletters, artistic portfolios, and special interest publications.
            </p>
            
            <div class="specifications">
                <div class="spec-item">
                    <strong>Size:</strong> A3 Vertical (11.7" x 16.5"), A4 Vertical (8.3" x 11.7"), 8.5" x 11" (Standard), 5.5" x 8.5" (Digest), 9" x 12" (Large Format)
                </div>
                
                <div class="spec-item">
                    <strong>Paper:</strong> Wood-free (Most Popular), Recycled, Coated
                </div>
                
                <div class="spec-item">
                    <strong>Paper Thickness:</strong> 120 GSM (Standard), 150 GSM (Premium)
                </div>
                
                <div class="spec-item">
                    <strong>Number of Inside Pages:</strong> 50-100 pages (adjustable in multiples of 4)
                </div>
                
                <div class="spec-item">
                    <strong>Printing Side:</strong> Tow side (Front and Back)
                </div>
                
                <div class="spec-item">
                    <strong>Color:</strong> 4 Available Now (Full CMYK)
                </div>
                
                <div class="spec-item">
                    <strong>Cover Type:</strong> Art Matt (Matte) or Art Glossy (Glossy)
                </div>
                
                <div class="spec-item">
                    <strong>Accepted File Formats:</strong> .pdf, .ai, .psd, .png, .jpg
                </div>
            </div>

            <!-- TABBED NAVIGATION -->
            <div class="description-tabs">
                <button class="tab-button active" onclick="openMagazineTab(event, 'paperOptions')">Paper Options</button>
                <button class="tab-button" onclick="openMagazineTab(event, 'bindingTypes')">Binding Types</button>
                <button class="tab-button" onclick="openMagazineTab(event, 'coverOptions')">Cover Options</button>
                <button class="tab-button" onclick="openMagazineTab(event, 'deliveryTime')">Delivery Time</button>
            </div>

            <!-- TAB CONTENT: Paper Options -->
            <div id="paperOptions" class="tab-content active">
                <div class="additional-finishing">
                    <h4>Paper Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Cover Paper Options</strong>
                        <p>
                            <strong>Self-Cover:</strong> Same paper stock as inside pages for a cohesive, unified look. Cost-effective 
                            option ideal for newsletters and internal publications.<br><br>
                            <strong>100lb Glossy Cardstock:</strong> Premium heavyweight cover with high-shine finish. Provides 
                            vibrant color reproduction and professional appearance with enhanced durability.<br><br>
                            <strong>100lb Matte Cardstock:</strong> Heavyweight cover with elegant non-reflective finish. Offers 
                            sophisticated appearance with superior durability and fingerprint resistance.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Inside Paper Options</strong>
                        <p>
                            <strong>70lb Uncoated Paper:</strong> Natural, non-glossy paper perfect for text-heavy publications. 
                            Easy to write on, reduces eye strain, and provides excellent readability. Ideal for literary magazines 
                            and educational publications.<br><br>
                            <strong>80lb Glossy Paper:</strong> Smooth, shiny finish that enhances image quality and color vibrancy. 
                            Perfect for photo-heavy magazines, fashion publications, and lifestyle content.<br><br>
                            <strong>80lb Matte Paper:</strong> Smooth, non-reflective finish that balances image quality with 
                            readability. Reduces glare while maintaining excellent color reproduction. Versatile choice for mixed 
                            content magazines.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Binding Types -->
            <div id="bindingTypes" class="tab-content">
                <div class="additional-finishing">
                    <h4>Binding Type Details:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Saddle Stitch Binding (Stapled)</strong>
                        <p>
                            Traditional magazine binding method using staples through the fold. Cost-effective and ideal for 
                            magazines with 8-64 pages. Pages lay flat when opened, making it easy to read and photograph content. 
                            Quick production time and economical for smaller publications, newsletters, catalogs, and brochures. 
                            Professional appearance suitable for most magazine applications.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Perfect Bound (Glued)</strong>
                        <p>
                            Premium binding method where pages are glued together at the spine with a wrap-around cover. Creates 
                            a professional, book-like appearance with a flat, printable spine perfect for displaying titles and 
                            branding. Ideal for magazines with 28+ pages. More durable than saddle stitch, suitable for higher 
                            page counts (up to 300+ pages). Preferred for annual reports, product catalogs, and publications 
                            intended for long-term use or display.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Cover Options -->
            <div id="coverOptions" class="tab-content">
                <div class="additional-finishing">
                    <h4>Cover Finishing Options:</h4>
                    
                    <div class="finishing-option">
                        <strong>*Art Matt (Matte Lamination)</strong>
                        <p>
                            A soft matte layer coated over the cover to protect against damage and scratches, providing a 
                            practical and elegant finish. The non-reflective surface reduces glare and fingerprints while 
                            offering a sophisticated, upscale appearance. Perfect for artistic publications, literary magazines, 
                            and corporate reports where a refined, professional look is desired. Durable and resistant to scuffing.
                        </p>
                    </div>
                    
                    <div class="finishing-option">
                        <strong>*Art Glossy (Glossy Lamination)</strong>
                        <p>
                            A soft gloss layer coated over the cover to protect against damage and scratches while providing a 
                            shiny, vibrant appearance. Enhances color saturation and makes images pop with brilliant intensity. 
                            Ideal for fashion magazines, lifestyle publications, promotional materials, and any content where 
                            visual impact is paramount. Water-resistant and easy to clean, providing both protection and 
                            eye-catching appeal.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT: Delivery Time -->
            <div id="deliveryTime" class="tab-content">
                <div class="delivery-info">
                    <h4>Delivery Time:</h4>
                    <ul>
                        <li><strong>Standard Delivery:</strong> 5-7 Business Days</li>
                        <li><strong>Rush Order:</strong> 3-4 Business Days (depending on order time and quantity)</li>
                    </ul>
                    <div class="delivery-note">
                        <p><strong>Production Time Details:</strong></p>
                        <p>Magazine printing requires additional time due to the complexity of multi-page layout, binding, and 
                        quality control processes. Rush orders placed before 12:00 PM may be expedited. Large quantity orders 
                        (500+ magazines) or publications exceeding 100 pages may require additional production time. Perfect 
                        bound magazines require 1-2 extra days compared to saddle stitch due to binding and drying time. 
                        Specialty finishes and custom sizes may extend production timeline.</p>
                    </div>
                </div>
            </div>
            
            <div class="note-section">
                <p><em>Note: Magazine page counts must be in multiples of 4 for proper printing and binding. For best results, 
                provide print-ready PDF files with proper bleed (0.125" on all sides) and CMYK color mode. Text should be at 
                least 0.25" from trim edges. Saddle stitch binding requires minimum 8 pages, while perfect binding requires 
                minimum 28 pages. Color accuracy may vary slightly between screen display and printed output. Allow 24 hours 
                for lamination coatings to fully cure before heavy handling or shipping.</em></p>
            </div>
        </div>
    </div>
    <!-- DESCRIPTION SECTION END -->

</div>

<!-- Cancellation Confirmation Modal -->
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

<script src="../js/magazine.js?v=1"></script>

<script>
function openMagazineTab(evt, tabName) {
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