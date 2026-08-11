<?php
session_start(); // Always start session

// ====== Redirect to login if not logged in ======
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ==========================================
//  0. HANDLE CANCELLATION (DELETE TEMP FILES)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    // Check if there are files in the session to delete
    if (isset($_SESSION['design_file']) && !empty($_SESSION['design_file'])) {
        $filesToDelete = explode(',', $_SESSION['design_file']);
        foreach ($filesToDelete as $file) {
            $filePath = "../uploads/temp/" . trim($file);
            // Check if file exists before attempting to delete
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        // Clear the session variable
        unset($_SESSION['design_file']);
    }

    // Redirect back to product page (or dashboard)
    header("Location: product.php");
    exit();
}

$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

// ====== DB CONNECTION ======
$conn = new mysqli("localhost", "root", "", "printease");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// ==========================================
//  1. HANDLE FILE UPLOAD (TO TEMP FOLDER)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedFileNames = [];
    
    // Check if files exist in the POST request
    if (isset($_FILES['designFile'])) {
        
        $targetDir = "../uploads/temp/";
        
        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileArray = $_FILES['designFile'];
        
        // Check for upload errors
        if (is_array($fileArray['name'])) {
            foreach ($fileArray['name'] as $key => $name) {
                $err = $fileArray['error'][$key];
                if ($err === UPLOAD_ERR_OK) {
                    $tmpName = $fileArray['tmp_name'][$key];
                    $safeName = time() . '_' . uniqid() . '_' . basename($name);
                    $targetPath = $targetDir . $safeName;
                    
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $uploadedFileNames[] = $safeName;
                    }
                }
            }
        } elseif ($fileArray['error'] === UPLOAD_ERR_OK) {
             // Single file fallback
        }
    }

    // Save to Session
    if (!empty($uploadedFileNames)) {
        $_SESSION['design_file'] = implode(',', $uploadedFileNames);
    }

}

// ==========================================
//  2. DETERMINE ORDER DETAILS
// ==========================================
$orderType = $_POST['orderType'] ?? $_GET['orderType'] ?? 'sticker';
$quantity = $_POST['quantity'] ?? $_GET['quantity'] ?? 1;
$orderSpeed = $_POST['orderSpeed'] ?? $_GET['orderSpeed'] ?? 'standard';

// Retrieve file count from session if available
$designFileStr = $_SESSION['design_file'] ?? '';
$numFiles = $designFileStr ? count(explode(',', $designFileStr)) : 0;

// Defaults
$size = 'N/A'; $material = 'N/A'; $cutType = 'N/A'; $stickerType = 'N/A';
$imagePath = '../image/hello.png';
$pageCount = 8; // Default for magazine

// Labels for the Breakdown
$labels = [
    'size' => 'Size',
    'material' => 'Material',
    'cut' => 'Cut Type',
    'option' => 'Option',
    'pageCount' => 'Page Count'
];

// Map variables based on Order Type
switch ($orderType) {
    case 'laminate':
        $size = $_POST['laminateSize'] ?? $_GET['laminateSize'] ?? 'Medium (3x3 inch)';
        $material = $_POST['laminateType'] ?? $_GET['laminateType'] ?? 'Matte';
        $cutType = $_POST['thickness'] ?? $_GET['thickness'] ?? '3 mil';
        $stickerType = $_POST['laminateFinish'] ?? $_GET['laminateFinish'] ?? 'Glossy';
        $imagePath = $_POST['image'] ?? $_GET['image'] ?? '../image/Laminate.png';
        $labels = ['size' => 'Base Price', 'material' => 'Laminate Type', 'cut' => 'Thickness', 'option' => 'Finish'];
        break;

   case 'printpict':
        $size = $_POST['printSize'] ?? $_GET['printSize'] ?? 'A4';
        $material = $_POST['paperType'] ?? $_GET['paperType'] ?? 'Glossy Paper';
        $cutType = $_POST['colorType'] ?? $_GET['colorType'] ?? 'Full Color';
        $stickerType = $_POST['printOption'] ?? $_GET['printOption'] ?? 'Front Only';
        $imagePath = $_POST['image'] ?? $_GET['image'] ?? '../image/Printpic(1).jpg';
        $labels = ['size' => 'Base Price', 'material' => 'Paper Type', 'cut' => 'Color Type', 'option' => 'Print Option'];
        break;
        
    case 'postcard':
        $size = $_POST['size'] ?? $_GET['size'] ?? '4" x 6" (Standard)';
        $material = $_POST['paperType'] ?? $_GET['paperType'] ?? 'Matte Cardstock (Smooth Finish)';
        $cutType = $_POST['finish'] ?? $_GET['finish'] ?? 'Single-Sided Print';
        $stickerType = $_POST['orientation'] ?? $_GET['orientation'] ?? 'Portrait';
        $imagePath = $_POST['image'] ?? $_GET['image'] ?? '../image/Postcard.jpg';
        $labels = ['size' => 'Base Price', 'material' => 'Paper Type', 'cut' => 'Finish', 'option' => 'Orientation'];
        break;

    // NEW: Magazine Case
    case 'magazine':
        $size = $_POST['size'] ?? $_GET['size'] ?? '8.5" x 11" (Standard)';
        $material = $_POST['coverPaper'] ?? $_GET['coverPaper'] ?? 'Self-Cover (Same as Inside)';
        $cutType = $_POST['binding'] ?? $_GET['binding'] ?? 'Saddle Stitch (Stapled)';
        $stickerType = $_POST['insidePaper'] ?? $_GET['insidePaper'] ?? '70lb Uncoated Paper'; // Re-using 'stickerType' for inside paper
        $pageCount = $_POST['pageCount'] ?? $_GET['pageCount'] ?? 8;
        $imagePath = $_POST['image'] ?? $_GET['image'] ?? '../image/Magazine.jpg';
        $labels = ['size' => 'Base Price (Size)', 'material' => 'Cover Paper', 'cut' => 'Binding', 'option' => 'Inside Paper', 'pageCount' => 'Page Count'];
        break;

    default: // Sticker
        $orderType = 'sticker'; // Ensure default is set
        $size = $_POST['size'] ?? $_GET['size'] ?? 'Medium (3x3 inch)';
        if ($size === "Custom Size" && isset($_POST['customSize']) && !empty($_POST['customSize'])) {
            $size = htmlspecialchars($_POST['customSize']);
        }
        $material = $_POST['material'] ?? $_GET['material'] ?? 'Transparent';
        $cutType = $_POST['cutType'] ?? $_GET['cutType'] ?? 'Die-Cut';
        $stickerType = $_POST['stickerType'] ?? $_GET['stickerType'] ?? 'Outdoor';
        $imagePath = $_POST['image'] ?? $_GET['image'] ?? '../image/Sticker.jpg';
        $labels = ['size' => 'Base Price', 'material' => 'Material', 'cut' => 'Cut Type', 'option' => 'Sticker Type'];
        break;
}

// ==========================================
//  3. PRICING LOGIC (DETAILED BREAKDOWN)
// ==========================================

// Price Tables
$sizePrices = [
    // Sticker/Laminate
    "Small (2x2 inch)" => 2, "Medium (3x3 inch)" => 5, "Large (4x4 inch)" => 8, "A5 (5.8x8.3 inch)" => 15,
    // Print
    "A4" => 10, "A3" => 15, "Legal" => 12, "A5" => 8, "Custom Size" => 5, // Custom size base
    // Laminate sizes
    "Small" => 3, "Medium" => 5, "Large" => 8,
    // Postcard Sizes
    "4\" x 6\" (Standard)" => 30, // Was 8
    "5\" x 7\"" => 34, // Was 12
    "6\" x 9\"" => 38, // Was 16
    // NEW: Magazine Sizes (Base Price)
    "8.5\" x 11\" (Standard)" => 100, "5.5\" x 8.5\" (Digest)" => 80, "9\" x 12\" (Large Format)" => 120
];


$materialPrices = [
    // Sticker
    "Glossy Vinyl" => 5, "Matte Vinyl" => 4, "Transparent" => 6,
    // Print
    "Glossy Paper" => 5, "Matte Paper" => 4, "Recycled Paper" => 3,
    // Laminate
    "Glossy" => 5, "Matte" => 4, "Soft Touch" => 0, 
    // Postcard Paper
    "Glossy Cardstock (High Shine)" => 5, "Matte Cardstock (Smooth Finish)" => 4, "Recycled Paper (Eco-Friendly)" => 3, "Textured Linen Paper" => 6,
    // NEW: Magazine Cover Paper
    "Self-Cover (Same as Inside)" => 0, "100lb Glossy Cardstock" => 20, "100lb Matte Cardstock" => 15
];

$cutTypePrices = [
    // Sticker
    "Die-Cut" => 3, "Kiss-Cut" => 2, "Sheet" => 1,
    // Laminate
    "3 mil" => 2, "5 mil" => 3, "10 mil" => 5,
    // Print
    "Black & White" => 0, "Full Color" => 5,
    // Postcard Finish
    "Single-Sided Print" => 0, "Double-Sided Print" => 5, "UV Gloss Coated" => 3, "Soft Touch Matte" => 3,
    // NEW: Magazine Binding
    "Saddle Stitch (Stapled)" => 5, "Perfect Bound (Glued)" => 25
];

// 'optionPrices' is used for Sticker Type, Print Option, Laminate Finish, Postcard Orientation, and Magazine INSIDE PAPER
$optionPrices = [
    // Print
    "Front Only" => 0, "Back to Back" => 5, 
    // Sticker
    "Outdoor" => 2, "Indoor" => 0,
    // Laminate
    "Glossy" => 0, 
    // Postcard
    "Portrait" => 0, "Landscape" => 0,
    // NEW: Magazine Inside Paper (Price PER PAGE)
    "70lb Uncoated Paper" => 1.5, "80lb Glossy Paper" => 2.5, "80lb Matte Paper" => 2
];

// Calculate Individual Component Prices
$priceSize = $sizePrices[$size] ?? ($sizePrices["Custom Size"] ?? 5); // Fallback for custom text
$priceMaterial = $materialPrices[$material] ?? 0;
$priceCut = $cutTypePrices[$cutType] ?? 0;
$priceOption = $optionPrices[$stickerType] ?? 0; // This is the 'Inside Paper' price for magazines
$priceFile = $numFiles * 5; // 5 pesos per file

// Calculate Base Unit Price (Including File Cost per Unit now)
$baseUnitPrice = 0;
if ($orderType === 'magazine') {
    // Magazine logic: (BaseSize + Cover + Binding + (InsidePaperPrice * PageCount)) + FileCost
    $baseUnitPrice = $priceSize + $priceMaterial + $priceCut + ($priceOption * $pageCount) + $priceFile;
} else {
    // Standard logic: Items + FileCost
    $baseUnitPrice = $priceSize + $priceMaterial + $priceCut + $priceOption + $priceFile;
}

// Calculate Totals
// The file cost is now part of baseUnitPrice, so it gets multiplied by quantity
$totalPrice = $baseUnitPrice * $quantity;

// Date Calculation
$startDate = new DateTime();
$endDate = new DateTime();

// --- START: UPDATED DATE LOGIC ---
if ($orderType === 'magazine') {
    // Magazine Services
    if ($orderSpeed === 'standard') {
        // Standard Magazine: 5-7 days
        $startDate->modify('+5 days');
        $endDate->modify('+7 days');
    } else {
        // Rush Magazine: 3-5 days
        $startDate->modify('+3 days');
        $endDate->modify('+5 days');
    }
} else {
    // All Other Products
    if ($orderSpeed === 'standard') {
        // Standard (Non-Magazine): 2-3 days
        $startDate->modify('+2 days');
        $endDate->modify('+3 days');
    } else {
        // Rush (Non-Magazine): 1-2 days
        $startDate->modify('+1 days');
        $endDate->modify('+2 days');
    }
}
// --- END: UPDATED DATE LOGIC ---
$expectedDays = $startDate->format('F j') . ' - ' . $endDate->format('F j, Y');

// --- FIX: DISPLAY NAME LOGIC (Replace printpict with Print) ---
$displayOrderType = ($orderType === 'printpict') ? 'Print' : ucfirst($orderType);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="../style.css?v=5">
    <link rel="stylesheet" href="../css/confirmation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>


    <div class="sidebar">
        <div class="logo">
            <img src="../image/logo.png" alt="Printease Logo">
            <h2>PRINTEASE</h2>
        </div>
        <ul class="menu">
            <li><a href="/systemcutie/index.php"><i class="fa-solid fa-table-cells icon"></i> Dashboard</a></li>
            <li><a href="/systemcutie/components/product.php"><i class="fa-solid fa-box icon"></i> Product</a></li>
            <li><a href="/systemcutie/components/order.php"><i class="fa-solid fa-credit-card icon"></i> Orders</a></li>
            <li><a href="/systemcutie/components/notifications.php"><i class="fa-solid fa-bell icon"></i> Notifications</a></li>
            <li><a href="/systemcutie/components/message_customer.php"><i class="fa-solid fa-message icon"></i> Message</a></li>
            <li><a href="../systemcutie/components/profile.php"><i class="fa-solid fa-user icon"></i> Profile</a></li>
        </ul>
        <div class="logout"><a href="#" id="logout-btn">Logout</a></div>
    </div>

    <div class="main-content">
        <div class="topbar">
          <!--  <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search...">
            </div> -->
            <div class="account-dropdown">
               <!-- <i class="fa-solid fa-circle-user"></i> -->
              <!--  <span><?= htmlspecialchars($displayName) ?></span> -->
                <div class="dropdown-menu">
                    <a href="components/login.php?logout=1">Log Out</a>
                </div>
            </div>
        </div>

        <div class="confirmation-page-wrapper">
            <div class="main-container">
                <div class="header-banner">
                    <div class="checkmark-circle"><div class="checkmark"></div></div>
                    <h1>ORDER CONFIRMATION</h1>
                    <!-- USED displayOrderType HERE -->
                    <p>Your custom <?= htmlspecialchars($displayOrderType) ?> is being prepared</p>
                </div>

                <div class="content-wrapper">
                    <div class="image-section">
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product Preview" class="preview-image">
                    </div>

                    <div class="details-section">
                        <div>
                            <h2 class="section-title">Order Summary:</h2>
                            <!-- USED displayOrderType HERE -->
                            <div class="info-row"><span>Order Type:</span><span><?= htmlspecialchars($displayOrderType) ?></span></div>
                            <div class="info-row"><span><?= $labels['size'] ?>:</span><span><?= htmlspecialchars($size) ?></span></div>
                            <div class="info-row"><span><?= $labels['material'] ?>:</span><span><?= htmlspecialchars($material) ?></span></div>
                            <div class="info-row"><span><?= $labels['cut'] ?>:</span><span><?= htmlspecialchars($cutType) ?></span></div>
                            <div class="info-row"><span><?= $labels['option'] ?>:</span><span><?= htmlspecialchars($stickerType) ?></span></div>
                            <?php if ($orderType === 'magazine'): ?>
                                <div class="info-row"><span><?= $labels['pageCount'] ?>:</span><span><?= htmlspecialchars($pageCount) ?></span></div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h2 class="section-title">Price Breakdown:</h2>
                            <div class="price-breakdown">
                                <div class="price-row">
                                    <span><?= $labels['size'] ?> (<?= htmlspecialchars($size) ?>)</span>
                                    <span>₱<?= number_format($priceSize, 2) ?></span>
                                </div>
                                
                                <?php if($priceMaterial > 0): ?>
                                <div class="price-row">
                                    <span><?= $labels['material'] ?> (<?= htmlspecialchars($material) ?>)</span>
                                    <span>₱<?= number_format($priceMaterial, 2) ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if($priceCut > 0): ?>
                                <div class="price-row">
                                    <span><?= $labels['cut'] ?> (<?= htmlspecialchars($cutType) ?>)</span>
                                    <span>₱<?= number_format($priceCut, 2) ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if($priceOption > 0): ?>
                                <div class="price-row">
                                    <!-- Special logic for magazine inside paper -->
                                    <?php if ($orderType === 'magazine'): ?>
                                        <span><?= $labels['option'] ?> (<?= htmlspecialchars($stickerType) ?>)</span>
                                        <span>₱<?= number_format($priceOption, 2) ?> x <?= $pageCount ?> pages</span>
                                    <?php else: ?>
                                        <span><?= $labels['option'] ?> (<?= htmlspecialchars($stickerType) ?>)</span>
                                        <span>₱<?= number_format($priceOption, 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <!-- MOVED UP: File Upload is now part of unit price -->
                                <div class="price-row">
                                    <span>File Upload (<?= $numFiles ?> file/s)</span>
                                    <span>₱<?= number_format($priceFile, 2) ?></span>
                                </div>

                                <div class="price-row">
                                    <span>× Quantity</span>
                                    <span>× <?= $quantity ?></span>
                                </div>

                                <div class="price-row total">
                                    <span>Total Price:</span>
                                    <span>₱<?= number_format($totalPrice, 2) ?></span>
                                </div>
                            </div>
                            <div class="completion-info"><strong>Estimated Completion:</strong><span><?= $expectedDays ?></span></div>
                        </div>

                        <div class="button-group">
                            <form action="payment.php" method="POST" style="display: contents;">
                                <input type="hidden" name="orderType" value="<?= htmlspecialchars($orderType) ?>">
                                <input type="hidden" name="quantity" value="<?= htmlspecialchars($quantity) ?>">
                                <input type="hidden" name="orderSpeed" value="<?= htmlspecialchars($orderSpeed) ?>">
                                <input type="hidden" name="from_confirmation" value="1">

                                <!-- PHP logic to pass correct fields -->
                                <?php if ($orderType === 'sticker'): ?>
                                    <input type="hidden" name="size" value="<?= htmlspecialchars($size) ?>">
                                    <input type="hidden" name="material" value="<?= htmlspecialchars($material) ?>">
                                    <input type="hidden" name="cutType" value="<?= htmlspecialchars($cutType) ?>">
                                    <input type="hidden" name="stickerType" value="<?= htmlspecialchars($stickerType) ?>">
                                <?php elseif ($orderType === 'printpict'): ?>
                                    <input type="hidden" name="printSize" value="<?= htmlspecialchars($size) ?>">
                                    <input type="hidden" name="paperType" value="<?= htmlspecialchars($material) ?>">
                                    <input type="hidden" name="colorType" value="<?= htmlspecialchars($cutType) ?>">
                                    <input type="hidden" name="printOption" value="<?= htmlspecialchars($stickerType) ?>">
                                <?php elseif ($orderType === 'laminate'): ?>
                                    <input type="hidden" name="laminateSize" value="<?= htmlspecialchars($size) ?>">
                                    <input type="hidden" name="laminateType" value="<?= htmlspecialchars($material) ?>">
                                    <input type="hidden" name="thickness" value="<?= htmlspecialchars($cutType) ?>">
                                    <input type="hidden" name="laminateFinish" value="<?= htmlspecialchars($stickerType) ?>">
                                <?php elseif ($orderType === 'postcard'): ?>
                                    <input type="hidden" name="size" value="<?= htmlspecialchars($size) ?>">
                                    <input type="hidden" name="paperType" value="<?= htmlspecialchars($material) ?>">
                                    <input type="hidden" name="finish" value="<?= htmlspecialchars($cutType) ?>">
                                    <input type="hidden" name="orientation" value="<?= htmlspecialchars($stickerType) ?>">
                                    <input type="hidden" name="type" value="<?= htmlspecialchars($_POST['type'] ?? 'N/A') ?>">
                                <?php elseif ($orderType === 'magazine'): ?>
                                    <!-- NEW: Magazine Hidden Fields -->
                                    <input type="hidden" name="size" value="<?= htmlspecialchars($size) ?>">
                                    <input type="hidden" name="coverPaper" value="<?= htmlspecialchars($material) ?>">
                                    <input type="hidden" name="binding" value="<?= htmlspecialchars($cutType) ?>">
                                    <input type="hidden" name="insidePaper" value="<?= htmlspecialchars($stickerType) ?>">
                                    <input type="hidden" name="pageCount" value="<?= htmlspecialchars($pageCount) ?>">
                                <?php endif; ?>
                                <!-- End of PHP logic -->

                                <button type="submit" class="btn btn-confirm">Confirm Order</button>
                            </form>
                            <!-- Changed Cancel button to trigger file deletion -->
                            <a href="?action=cancel" class="btn btn-cancel">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="logout-modal" id="logoutModal">
        <div class="logout-box">
            <i class="fa-solid fa-print"></i>
           <!-- <h2>ARE YOU SURE YOU WANT TO<br>LOG OUT?</h2> -->
            <div class="logout-actions">
                <a href="components/login.php?logout=1" class="btn yes">yes</a>
                <button class="btn no" id="cancelLogout">no</button>
            </div>
        </div>
    </div>

    <script>
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');

        logoutBtn?.addEventListener('click', e => {
            e.preventDefault();
            logoutModal.style.display = 'flex';
        });
        cancelLogout?.addEventListener('click', () => logoutModal.style.display = 'none');
        window.addEventListener('click', e => {
            if (e.target === logoutModal) logoutModal.style.display = 'none';
        });
    </script>
</body>
</html>