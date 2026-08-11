<?php
session_start(); // Always start session

// ✅ Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printease - Print Service</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <link rel="stylesheet" href="../css/printpict.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

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

    <a href="#" id="customBackBtn" class="back-btn-black">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <div class="print-wrapper">
        <h2 class="print-title"></h2>

        <div class="print-content">
            <div class="print-preview">
                <img id="mainPrintPreview" class="main-preview" src="../image/Printpic(1).jpg" alt="Main Preview">
                <div class="thumbnail-row">
                    <button type="button" class="arrow">&lt;</button>
                    <img class="thumb active" src="../image/printpict2.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/printpic3.jpg" onclick="changePreview(this)">
                    <img class="thumb" src="../image/printpic4.jpg" onclick="changePreview(this)">
                    <button type="button" class="arrow">&gt;</button>
                </div>
            </div>

            <form id="printForm" class="print-form" action="confirmation.php" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                <input type="hidden" name="orderType" value="printpict">
                <div class="form-group">
                    <label>Print Size</label>
                    <select id="printSize" name="printSize" onchange="toggleCustomPrintSize()">
                        <option value="Select Size">Select Size</option>
                        <option value="A4">A4</option>
                        <option value="A3">A3</option>
                        <option value="Legal">Legal</option>
                        <option value="A5">A5</option>
                    </select>
                </div>
                
                <div id="customPrintSizeInputGroup" class="form-group" style="display: none; margin-top: 10px;">
                    <label for="customPrintSize">Enter Custom Size (e.g., 5cm x 7cm)</label>
                    <input type="text" id="customPrintSize" name="customPrintSize" placeholder="Width x Height (e.g. 5cm x 7cm)">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Type of Paper</label>
                        <select id="paperType" name="paperType">
                            <option value="Select">Select</option>
                            <option value="Glossy Paper">Glossy Paper</option>
                            <option value="Matte Paper">Matte Paper</option>
                            <option value="Recycled Paper">Recycled Paper</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type of Color</label>
                        <select id="colorType" name="colorType">
                            <option value="Select">Select</option>
                            <option value="Black & White">Black & White</option>
                            <option value="Full Color">Full Color</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Print Option</label>
                    <div class="radio-group">
                        <label><input type="radio" name="printOption" checked value="Front Only"> Front Only</label>
                        <label><input type="radio" name="printOption" value="Back to Back"> Back to Back</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Upload Design</label>
                        <input type="file" id="printFileInput" name="designFile[]" multiple accept=".docx, .png,.jpg,.jpeg,.pdf,.ai,.svg" multiple>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Quantity</label>
                        <div class="qty-control">
                            <button type="button" onclick="decreaseQty()">−</button>
                            <input type="number" id="printQuantity" name="quantity" value="1" min="1">
                            <button type="button" onclick="increaseQty()">+</button>
                        </div>
                    </div>
                </div>

                <div id="uploadBox" class="upload-note">
                    Accepted file types: .docx, .png, .jpg, .pdf, .ai, .svg
                </div>

                <div class="button-row">
                   <button type="button" onclick="submitPrintOrder('standard')" class="btn normal">Standard (2 – 3 Days)</button>
                   <button type="button" onclick="submitPrintOrder('rush')" class="btn rush">Rush Order</button>
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
            <h3>Picture Printing Services</h3>
            <p>
                A picture print is a high-quality reproduction of your digital images on professional-grade paper. 
                Our printing services are designed to preserve your memories with vivid colors and sharp details. 
                Whether it's for personal collections, gifts, or professional displays, we ensure every print meets 
                the highest standards of quality and durability.
            </p>
            
            <div class="specifications">
                <div class="spec-item">
                    <strong>Size:</strong> A4, A3, Legal, A5, or Custom Size
                </div>
                
                <div class="spec-item">
                    <strong>Paper:</strong> Glossy Paper (Most Popular), Matte Paper, Recycled Paper
                </div>
                
                <div class="spec-item">
                    <strong>Paper Quality:</strong> Premium 200-300 GSM
                </div>
                
                <div class="spec-item">
                    <strong>Printing Side:</strong> Front Only or Back to Back (Front and Back)
                </div>
                
                <div class="spec-item">
                    <strong>Color:</strong> Black & White or Full Color (4 Color CMYK Available)
                </div>
                
                <div class="spec-item">
                    <strong>Accepted File Formats:</strong> .jpg, .jpeg, .png, .pdf, .ai, .svg, .docx
                </div>
            </div>
            
            <div class="finishing-section">
                <h4>Finishing Options:</h4>
                <p><strong>Standard Finish:</strong> Professional cut and trim</p>
            </div>
            
            <div class="additional-finishing">
                <h4>Additional Finishing Options:</h4>
                
                <div class="finishing-option">
                    <strong>*Glossy Finish</strong>
                    <p>
                        A soft glossy coating applied to the print surface to protect against damage and scratches, 
                        providing a shiny, vibrant appearance ideal for photos and colored images.
                    </p>
                </div>
                
                <div class="finishing-option">
                    <strong>*Matte Finish</strong>
                    <p>
                        A soft matte coating applied to the print surface to protect against damage and scratches, 
                        offering a non-reflective, elegant finish perfect for professional documents and art prints.
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
                <p><em>Note: Colors may vary slightly from screen display to printed output due to color profile differences. 
                For best results, please ensure your images are in high resolution (300 DPI recommended).</em></p>
            </div>
        </div>
    </div>
    <!-- DESCRIPTION SECTION END -->

</div>

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

<script src="../js/printpict.js"></script>

</body>
</html>