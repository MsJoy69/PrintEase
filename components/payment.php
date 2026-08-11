<?php
session_start();

// NEW: Handle incoming order from CONFIRMATION.PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderType']) && !isset($_POST['pay'])) {

    // --- 1. VALIDATE SESSION ---
    if (!isset($_SESSION['design_file'])) {
        $_SESSION['design_file'] = ''; 
    }
    
    // --- 2. Calculate Price & Gather Details (Server-Side) ---
    
    $totalPrice = 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $orderType = $_POST['orderType'];
    // Capture Order Speed
    $orderSpeed = $_POST['orderSpeed'] ?? 'standard'; 

    $orderDetails = [];
    
    // Defaults for calculation variables
    $size = 'N/A'; 
    $material = 'N/A'; 
    $cutType = 'N/A'; 
    $stickerType = 'N/A'; // acts as "Option" (Finish, Print Option, etc.)
    $pageCount = 8; // Default for magazine

    // Map POST variables based on Order Type
    switch ($orderType) {
        case 'laminate':
            $size = $_POST['laminateSize'] ?? 'Medium (3x3 inch)';
            $material = $_POST['laminateType'] ?? 'Matte';
            $cutType = $_POST['thickness'] ?? '3 mil';
            $stickerType = $_POST['laminateFinish'] ?? 'Glossy';
            
            $orderDetails = [
                'Laminate Size' => $size,
                'Laminate Type' => $material,
                'Thickness' => $cutType,
                'Finish' => $stickerType
            ];
            break;
    
       case 'printpict':
            $size = $_POST['printSize'] ?? 'A4';
            $material = $_POST['paperType'] ?? 'Glossy Paper';
            $cutType = $_POST['colorType'] ?? 'Full Color';
            $stickerType = $_POST['printOption'] ?? 'Front Only';
            
            $orderDetails = [
                'Print Size' => $size,
                'Paper Type' => $material,
                'Color Type' => $cutType,
                'Print Option' => $stickerType
            ];
            break;
            
        case 'postcard':
            $size = $_POST['size'] ?? '4" x 6" (Standard)';
            if ($size === "Custom Size" && isset($_POST['customSize']) && !empty($_POST['customSize'])) {
                $size = htmlspecialchars($_POST['customSize']);
            }
            $material = $_POST['paperType'] ?? 'Matte Cardstock (Smooth Finish)';
            $cutType = $_POST['finish'] ?? 'Single-Sided Print';
            $stickerType = $_POST['orientation'] ?? 'Portrait';
            
            $orderDetails = [
                'Postcard Type' => $_POST['type'] ?? 'N/A',
                'Size' => $size,
                'Paper Type' => $material,
                'Finish' => $cutType,
                'Orientation' => $stickerType
            ];
            break;
            
        case 'magazine':
            $size = $_POST['size'] ?? '8.5" x 11" (Standard)';
            $material = $_POST['coverPaper'] ?? 'Self-Cover (Same as Inside)';
            $cutType = $_POST['binding'] ?? 'Saddle Stitch (Stapled)';
            $stickerType = $_POST['insidePaper'] ?? '70lb Uncoated Paper';
            $pageCount = $_POST['pageCount'] ?? 8;
            
            $orderDetails = [
                'Size' => $size,
                'Cover Paper' => $material,
                'Binding' => $cutType,
                'Inside Paper' => $stickerType,
                'Page Count' => $pageCount
            ];
            break;
    
        default: // Sticker
            $orderType = 'sticker';
            $size = $_POST['size'] ?? 'Medium (3x3 inch)';
            if ($size === "Custom Size" && isset($_POST['customSize']) && !empty($_POST['customSize'])) {
                $size = htmlspecialchars($_POST['customSize']);
            }
            $material = $_POST['material'] ?? 'Transparent';
            $cutType = $_POST['cutType'] ?? 'Die-Cut';
            $stickerType = $_POST['stickerType'] ?? 'Outdoor';
            
            $orderDetails = [
                'Sticker Size' => $size,
                'Material' => $material,
                'Cut Type' => $cutType,
                'Sticker Type' => $stickerType
            ];
            break;
    }

    // --- PRICING ARRAYS ---
    $sizePrices = [
        "Small (2x2 inch)" => 2, "Medium (3x3 inch)" => 5, "Large (4x4 inch)" => 8, "A5 (5.8x8.3 inch)" => 15,
        "A4" => 10, "A3" => 15, "Legal" => 12, "A5" => 8, "Custom Size" => 5,
        "Small" => 3, "Medium" => 5, "Large" => 8,
        "4\" x 6\" (Standard)" => 30,
        "5\" x 7\"" => 34,
        "6\" x 9\"" => 38,
        "8.5\" x 11\" (Standard)" => 100, "5.5\" x 8.5\" (Digest)" => 80, "9\" x 12\" (Large Format)" => 120
    ];

    $materialPrices = [
        "Glossy Vinyl" => 5, "Matte Vinyl" => 4, "Transparent" => 6,
        "Glossy Paper" => 5, "Matte Paper" => 4, "Recycled Paper" => 3,
        "Glossy" => 5, "Matte" => 4, "Soft Touch" => 0, 
        "Glossy Cardstock (High Shine)" => 5, "Matte Cardstock (Smooth Finish)" => 4, "Recycled Paper (Eco-Friendly)" => 3, "Textured Linen Paper" => 6,
        "Self-Cover (Same as Inside)" => 0, "100lb Glossy Cardstock" => 20, "100lb Matte Cardstock" => 15
    ];

    $cutTypePrices = [
        "Die-Cut" => 3, "Kiss-Cut" => 2, "Sheet" => 1,
        "3 mil" => 2, "5 mil" => 3, "10 mil" => 5,
        "Black & White" => 0, "Full Color" => 5,
        "Single-Sided Print" => 0, "Double-Sided Print" => 5, "UV Gloss Coated" => 3, "Soft Touch Matte" => 3,
        "Saddle Stitch (Stapled)" => 5, "Perfect Bound (Glued)" => 25
    ];

    $optionPrices = [
        "Front Only" => 0, "Back to Back" => 5, 
        "Outdoor" => 2, "Indoor" => 0,
        "Glossy" => 0, 
        "Portrait" => 0, "Landscape" => 0,
        "70lb Uncoated Paper" => 1.5, "80lb Glossy Paper" => 2.5, "80lb Matte Paper" => 2
    ];

    // Calculate Component Prices
    $priceSize = $sizePrices[$size] ?? ($sizePrices["Custom Size"] ?? 5);
    $priceMaterial = $materialPrices[$material] ?? 0;
    $priceCut = $cutTypePrices[$cutType] ?? 0;
    $priceOption = $optionPrices[$stickerType] ?? 0;

    // Calculate File Cost
    $designFileNames = $_SESSION['design_file'];
    $priceFile = 0;
    $fileCount = 0;
    if (!empty($designFileNames)) {
        $fileCount = count(explode(',', $designFileNames));
        $priceFile = $fileCount * 5;
    }

    // Calculate Base Unit Price
    $baseUnitPrice = 0;
    if ($orderType === 'magazine') {
        $baseUnitPrice = $priceSize + $priceMaterial + $priceCut + ($priceOption * $pageCount) + $priceFile;
    } else {
        $baseUnitPrice = $priceSize + $priceMaterial + $priceCut + $priceOption + $priceFile;
    }
    
    $totalPrice = $baseUnitPrice * $quantity;

    // --- NEW: ADD PRICING BREAKDOWN & ORDER TYPE TO ORDER DETAILS ---
    // This ensures it's saved in the DB and visible on the receipt
    $orderDetails['Order Type'] = ucfirst($orderSpeed) . ' Process';
    $orderDetails['---'] = '---'; // Separator
    $orderDetails['Base Price'] = '₱' . number_format($priceSize, 2);
    
    if ($priceMaterial > 0) $orderDetails['Material Fee'] = '₱' . number_format($priceMaterial, 2);
    if ($priceCut > 0) $orderDetails['Cut/Binding Fee'] = '₱' . number_format($priceCut, 2);
    
    if ($priceOption > 0) {
        if ($orderType === 'magazine') {
             $orderDetails['Inside Paper Fee'] = '₱' . number_format($priceOption, 2) . ' x ' . $pageCount . ' pgs';
        } else {
             $orderDetails['Option Fee'] = '₱' . number_format($priceOption, 2);
        }
    }
    
    if ($priceFile > 0) {
        $orderDetails['File Upload Fee'] = '₱' . number_format($priceFile, 2) . ' (' . $fileCount . ' files)';
    }
    // ---------------------------------------------------------------

    // --- 3. Set Session Variables ---
    
    // FIX: Ensure 'printpict' is displayed as 'Print'
    $displayType = ($orderType === 'printpict') ? 'Print' : ucfirst($orderType);
    $_SESSION['orderType'] = $displayType . " Order";

    $_SESSION['quantity'] = $quantity;
    $_SESSION['totalPrice'] = $totalPrice;
    $_SESSION['order_details'] = json_encode($orderDetails);

    // Calculate expected completion days
    $startDate = new DateTime();
    $endDate = new DateTime();
    
    if ($orderType === 'magazine') {
        if ($orderSpeed === 'standard') {
            $startDate->modify('+5 days');
            $endDate->modify('+7 days');
        } else {
            $startDate->modify('+3 days');
            $endDate->modify('+5 days');
        }
    } else {
        if ($orderSpeed === 'standard') {
            $startDate->modify('+2 days');
            $endDate->modify('+3 days');
        } else {
            $startDate->modify('+1 days');
            $endDate->modify('+2 days');
        }
    }
    $_SESSION['expectedDays'] = $startDate->format('F j') . ' - ' . $endDate->format('F j, Y');

    // --- 4. Redirect to self (GET) to show payment page ---
    header("Location: payment.php");
    exit();
}


// Redirect to login if not logged in
if(!isset($_SESSION['user_id']) && !isset($_SESSION['user'])){ 
    if (isset($_SESSION['user'])) {
        // Session logic handle
    } else {
        header("Location: components/login.php");
        exit();
    }
}

// ====== DATABASE CONNECTIONS ======
$paymentDB = new mysqli("localhost", "root", "", "payment");
if($paymentDB->connect_error){
    die("Payment DB Connection failed: " . $paymentDB->connect_error);
}

$printeaseDB = new mysqli("localhost", "root", "", "printease");
if($printeaseDB->connect_error){
    die("Users DB Connection failed: " . $printeaseDB->connect_error);
}

// ===== Robustly ensure columns exist =====
$paymentDB->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS user_id INT(11) NULL AFTER username");
$paymentDB->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS order_details TEXT NULL AFTER design_file");
$paymentDB->query("ALTER TABLE payments MODIFY COLUMN design_file TEXT NULL"); 
$paymentDB->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS downpayment_amount DECIMAL(10,2) NULL AFTER total_price");
$paymentDB->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS downpayment_proof VARCHAR(255) NULL AFTER payment_proof");
$paymentDB->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS downpayment_reference VARCHAR(100) NULL AFTER reference_number");


// ====== FETCH USER INFO ======
$user_email = $_SESSION['user'] ?? 'Guest';
$userData = [];

if ($user_email !== 'Guest') {
    $stmt = $printeaseDB->prepare("SELECT * FROM info WHERE email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        if (!isset($_SESSION['user_id']) && isset($userData['id'])) {
            $_SESSION['user_id'] = $userData['id'];
        }
    }
    $stmt->close();
}

$customerName = $userData['name'] ?? '';
$customerEmail = $userData['email'] ?? '';
$customerContact = $userData['contact_number'] ?? ''; 
$customerAddress = $userData['address'] ?? ''; 

$city = $userData['city'] ?? '';
if (!empty($city)) {
    $customerAddress = !empty($customerAddress) ? $customerAddress . ', ' . $city : $city;
}

$user = $_SESSION['user'] ?? 'Guest';

$orderType = $_SESSION['orderType'] ?? 'N/A';
$quantity = $_SESSION['quantity'] ?? 1;
$totalPrice = $_SESSION['totalPrice'] ?? 0;
$expectedDays = $_SESSION['expectedDays'] ?? '';
$deliveryOption = "Sila ang kukuha ng mismong order sa shop nila"; 

$downpayment75 = $totalPrice * 0.75;

// ====== HANDLE FINAL PAYMENT SUBMISSION ======
if(isset($_POST['pay'])){
    $user_id = $_SESSION['user_id'] ?? 0;

    $orderType = $_POST['orderType'];
    $quantity = (int)$_POST['quantity'];
    $totalPrice = (float)$_POST['totalPrice'];
    $deliveryOption = $_POST['deliveryOption'];
    $expectedDate = $_POST['expectedDate'];
    
    $designFile = $_SESSION['design_file'] ?? ''; 
    $orderDetails = $_SESSION['order_details'] ?? ''; 

    $customerName = $_POST['customerName'];
    $customerEmail = $_POST['customerEmail'];
    $customerContact = $_POST['customerContact'];
    
    $address = $_POST['notes']; 

    $paymentMethod = $_POST['paymentMethod'];
    $referenceNumber = $paymentMethod === 'GCash' ? $_POST['referenceNumber'] : '';
    $paymentProof = '';

    // Move files from TEMP to FINAL folder
    $finalDesignDir = "../uploads/designs/";
    if (!is_dir($finalDesignDir)) mkdir($finalDesignDir, 0777, true);

    if (!empty($designFile)) {
        $filesToMove = explode(',', $designFile);
        foreach ($filesToMove as $fileName) {
            $tempPath = "../uploads/temp/" . $fileName;
            $finalPath = $finalDesignDir . $fileName;
            
            if (file_exists($tempPath)) {
                rename($tempPath, $finalPath);
            }
        }
    }

    // Handle GCash payment proof
    if($paymentMethod === 'GCash' && isset($_FILES['paymentProof']) && $_FILES['paymentProof']['error'] == 0){
        $targetDir = "../uploads/payments/";
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time().'_'.basename($_FILES['paymentProof']['name']);
        $targetFile = $targetDir.$fileName;
        if(move_uploaded_file($_FILES['paymentProof']['tmp_name'], $targetFile)){
            $paymentProof = 'uploads/payments/' . $fileName;
        }
    }

    $downpaymentAmount = 0;
    $downpaymentProof = '';
    $downpaymentReference = '';
    
    if($paymentMethod === 'Cash' && isset($_POST['hasDownpayment']) && $_POST['hasDownpayment'] === 'yes'){
        $downpaymentAmount = (float)$_POST['downpaymentAmount'];
        $downpaymentReference = $_POST['downpaymentReference'];
        
        if(isset($_FILES['downpaymentProof']) && $_FILES['downpaymentProof']['error'] == 0){
            $targetDir = "../uploads/payments/";
            if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time().'_downpayment_'.basename($_FILES['downpaymentProof']['name']);
            $targetFile = $targetDir.$fileName;
            if(move_uploaded_file($_FILES['downpaymentProof']['tmp_name'], $targetFile)){
                $downpaymentProof = 'uploads/payments/' . $fileName;
            }
        }
    }

    $datePart = date('Ymd');
    $randomPart = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $orderID = $datePart . '-' . $randomPart;

    $stmt = $paymentDB->prepare("INSERT INTO payments 
        (order_id, username, user_id, customer_name, email, contact, address, order_type, quantity, total_price, downpayment_amount, delivery_option, expected_date, payment_method, reference_number, downpayment_reference, payment_proof, downpayment_proof, design_file, order_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssssiddsssssssss", $orderID, $user, $user_id, $customerName, $customerEmail, $customerContact, $address, $orderType, $quantity, $totalPrice, $downpaymentAmount, $deliveryOption, $expectedDate, $paymentMethod, $referenceNumber, $downpaymentReference, $paymentProof, $downpaymentProof, $designFile, $orderDetails);
    
    if ($stmt->execute()) {
        unset($_SESSION['orderType'], $_SESSION['quantity'], $_SESSION['totalPrice'], $_SESSION['expectedDays'], $_SESSION['design_file'], $_SESSION['order_details']);

        $_SESSION['orderID'] = $orderID;
        header("Location: payment_success.php");
        exit();
    } else {
        echo "Error inserting order: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - Printease</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../style.css?v=3">
<link rel="stylesheet" href="../css/payment.css?v=2">
<style>
.gcash-fields, .downpayment-fields { display: none; padding-top: 10px; }
.gcash-fields.active, .downpayment-fields.active { display: block; }
.payment-option.selected { border: 2px solid #007bff; }
/* REMOVED .downpayment-checkbox styles as they are no longer needed */
.downpayment-info { background: #f0f8ff; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #007bff; }
.downpayment-info p { margin: 0; color: #333; font-size: 14px; }
.downpayment-info strong { color: #007bff; font-size: 18px; }

.payment-breakdown { margin-top: 20px; padding-top: 15px; border-top: 2px solid #ddd; }
.payment-breakdown table { width: 100%; border-collapse: collapse; }
.payment-breakdown td { padding: 8px 0; font-size: 14px; }
.payment-breakdown td:first-child { font-weight: 600; color: #555; }
.payment-breakdown td:last-child { text-align: right; color: #007bff; font-weight: 600; }
.payment-breakdown tr.total-row td { padding-top: 12px; border-top: 2px solid #007bff; font-size: 16px; font-weight: 700; color: #007bff; }
</style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../image/logo.png" alt="Printease Logo">
            <h2>PRINTEASE</h2>
        </div>

        <ul class="menu">
            <li><a href="/systemcutie/index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
            <li><a href="/systemcutie/components/product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
            <li><a href="/systemcutie/components/order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
            <li><a href="/systemcutie/components/notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
            <li><a href="/systemcutie/components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>
            <li><a href="../systemcutie/components/profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
        </ul>

        <div class="logout">
            <a href="#" id="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <form method="GET" action="">
              <!--  <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search...">
                </div> -->
            </form>

            <div class="top-buttons">
            

                <div class="account-dropdown">
                   

                    <div class="dropdown-menu">
                        <a href="#">My Account</a>
                        <a href="#">Orders</a>
                        <a href="components/login.php?logout=1">Log Out</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="payment-page-wrapper">
            <div class="payment-container">
                <div class="payment-header">
                    <div class="payment-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <h1>ORDER PAYMENT</h1>
                    <p>Your custom order is being prepared</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="payment-body">
                        <div class="left-column">
                            <div class="info-box">
                                <div class="box-header">
                                    <i class="fa-solid fa-shopping-bag"></i>
                                    <h2>Order Summary</h2>
                                </div>
                                
                                <div class="summary-item">
                                    <div class="summary-item-left">
                                        <div class="summary-item-title"><?php echo htmlspecialchars($orderType); ?></div>
                                        <div class="summary-item-subtitle">Quantity: <?php echo htmlspecialchars($quantity); ?></div>
                                    </div>
                                    <div class="summary-item-price">₱<?php echo number_format($totalPrice, 2); ?></div>
                                </div>

                            <div class="summary-total">
                                <span>Total Amount</span>
                                <span id="totalAmount">₱<?php echo number_format((float)$totalPrice, 2, '.', ','); ?></span>
                            </div>
                            
                            <!-- Downpayment Summary Table -->
                            <div id="downpaymentSummary" class="payment-breakdown" style="display: none;">
                                <table>
                                    <tr>
                                        <td>Total Amount:</td>
                                        <td id="breakdownTotal">₱<?php echo number_format($totalPrice, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Downpayment (75%):</td>
                                        <td id="breakdownDownpayment">₱<?php echo number_format($downpayment75, 2); ?></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td>Remaining Amount:</td>
                                        <td id="breakdownRemaining">₱<?php echo number_format($totalPrice - $downpayment75, 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                            </div>

                            <div class="info-box">
                                <div class="box-header">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <h2>Order Details</h2>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Order ID</span>
                                    <span class="detail-value">Pending</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Estimated Completion</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($expectedDays); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Pickup Option</span>
                                    <span class="detail-value">Store Pickup</span>
                                </div>
                            </div>
                        </div>

                        <div class="right-column">
                            <div class="info-box">
                                <div class="box-header">
                                    <i class="fa-solid fa-user"></i>
                                    <h2>Customer Information</h2>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Full Name</label>
                                    <input type="text" name="customerName" value="<?php echo htmlspecialchars($customerName); ?>" placeholder="Juan Dela Cruz" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="customerEmail" value="<?php echo htmlspecialchars($customerEmail); ?>" placeholder="juan@example.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="customerContact" value="<?php echo htmlspecialchars($customerContact); ?>" placeholder="09123456789" required>
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label>Notes</label>
                                    <textarea name="notes" placeholder="Request what type of printing you want"><?php echo htmlspecialchars($customerAddress); ?></textarea>
                                </div>
                            </div>

                            <div class="info-box">
                                <div class="box-header">
                                    <i class="fa-solid fa-credit-card"></i>
                                    <h2>Payment Method</h2>
                                </div>
                                
                                <div class="payment-methods">
                                    <label class="payment-option" id="gcashOption">
                                        <input type="radio" name="paymentMethod" value="GCash" required>
                                        <div class="payment-option-icon">
                                            <i class="fa-solid fa-mobile-screen"></i>
                                        </div>
                                        <span>GCash</span>
                                    </label>
                                    <label class="payment-option" id="cashOption">
                                        <input type="radio" name="paymentMethod" value="Cash" required>
                                        <div class="payment-option-icon">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </div>
                                        <span>Cash on Pickup</span>
                                    </label>
                                </div>

                                <!-- GCASH SECTION -->
                                <div id="gcashFields" class="gcash-fields">
                                    <div class="form-group">
                                        <label>Attach Payment Proof:</label>
                                        <input type="file" name="paymentProof" accept="image/*">
                                        <a href="../gcash.webp" target="__blank" style="display:flex; justify-content: center; margin-top: 5px;"><img src="../gcash.webp" style="width: 50%;"></a>
                                    </div>
                                    <div class="form-group">
                                        <label>Reference Number:</label>
                                        <input type="text" name="referenceNumber" placeholder="Enter GCash reference number" oninput="this.value = this.value.replace(/[^0-9]/g, '')" inputmode="numeric">                                    
                                    </div>
                                </div>

                                <!-- CASH / DOWNPAYMENT SECTION -->
                                <div id="cashFields" class="cash-fields" style="display: none;">
                                    <!-- Automatically trigger downpayment processing in PHP -->
                                    <input type="hidden" name="hasDownpayment" value="yes">

                                    <div id="downpaymentFields" class="downpayment-fields active" style="display: block;">
                                        <div class="downpayment-info">
                                            <p>75% Downpayment Amount: <strong>₱<?php echo number_format($downpayment75, 2); ?></strong></p>
                                            <p style="margin-top: 5px; font-size: 12px; color: #666;">Remaining balance: ₱<?php echo number_format($totalPrice - $downpayment75, 2); ?> (to be paid on pickup)</p>
                                        </div>
                                        
                                        <!-- QR CODE SECTION (Added based on screenshot) -->
                                        <div style="text-align: center; margin: 15px 0;">
                                            <img src="../gcash.webp" alt="QR Code" style="width: 250px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                        </div>

                                        <div class="form-group">
                                            <label>Downpayment Amount:</label>
                                            <input type="number" name="downpaymentAmount" step="0.01" min="0" value="<?php echo number_format($downpayment75, 2, '.', ''); ?>" placeholder="Enter amount (₱)" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Attach Downpayment Proof:</label>
                                            <input type="file" name="downpaymentProof" accept="image/*">
                                        </div>
                                        <div class="form-group">
                                            <label>Reference Number:</label>
                                            <input type="text" name="downpaymentReference" placeholder="Enter reference number" oninput="this.value = this.value.replace(/[^0-9]/g, '')" inputmode="numeric">                                        
                                        </div>
                                    </div>
                                </div>

                                <div class="info-note">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <p>Please prepare exact amount for pickup to speed up your transaction.</p>
                                </div>

                                <button type="submit" name="pay" class="pay-button">pay now</button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="orderType" value="<?php echo htmlspecialchars($orderType); ?>">
                    <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
                    <input type="hidden" name="totalPrice" value="<?php echo htmlspecialchars($totalPrice); ?>">
                    <input type="hidden" name="deliveryOption" value="<?php echo htmlspecialchars($deliveryOption); ?>">
                    <input type="hidden" name="expectedDate" value="<?php echo htmlspecialchars($expectedDays); ?>">
                </form>
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
        const gcashOption = document.getElementById('gcashOption');
        const cashOption = document.getElementById('cashOption');
        const gcashFields = document.getElementById('gcashFields');
        const cashFields = document.getElementById('cashFields');
        // downpaymentFields is now just a container inside cashFields
        const paymentOptions = document.querySelectorAll('.payment-option');
        const downpaymentSummary = document.getElementById('downpaymentSummary');

        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                
                const selectedValue = this.querySelector('input[type="radio"]').value;
                if (selectedValue === 'GCash') {
                    // Show GCash inputs
                    gcashFields.classList.add('active');
                    cashFields.style.display = 'none';
                    
                    // Hide Summary table for Cash
                    if(downpaymentSummary) downpaymentSummary.style.display = 'none';

                    // Set GCash required
                    document.querySelector('input[name="paymentProof"]').required = true;
                    document.querySelector('input[name="referenceNumber"]').required = true;
                    // Unset Cash required
                    document.querySelector('input[name="downpaymentProof"]').required = false;
                    document.querySelector('input[name="downpaymentReference"]').required = false;

                } else if (selectedValue === 'Cash') {
                    // Show Cash (Downpayment) inputs immediately
                    gcashFields.classList.remove('active');
                    cashFields.style.display = 'block';

                    // Show Summary table immediately
                    if(downpaymentSummary) downpaymentSummary.style.display = 'block';

                    // Unset GCash required
                    document.querySelector('input[name="paymentProof"]').required = false;
                    document.querySelector('input[name="referenceNumber"]').required = false;
                    
                    // Set Cash required
                    document.querySelector('input[name="downpaymentProof"]').required = true;
                    document.querySelector('input[name="downpaymentReference"]').required = true;
                }
            });
        });

        // Logout Modal Logic
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');

        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
            });
        }

        if (cancelLogout) {
            cancelLogout.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>