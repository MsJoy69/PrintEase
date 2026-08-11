<?php
include 'auth.php';

// ===== DB CONNECTIONS & Column Checks (Keep as is) =====
$conn = new mysqli("localhost", "root", "", "payment");
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

$notifConn = new mysqli("localhost", "root", "", "notification");
if($notifConn->connect_error){
    die("Notification DB connection failed: " . $notifConn->connect_error);
}

// Ensure columns exist (safe - won't break if already present)
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS order_id VARCHAR(50) NOT NULL AFTER id"); // Ensure order_id column exists
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Pending'");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS design_file VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS order_details TEXT NULL");


// ===== Handle Status Update (Keep as is) =====
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$logFile = __DIR__ . '/notif_debug.log';

function dbg($msg) {
    global $logFile;
    // file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND);
}

if (isset($_POST['update_status'])) {
    try {
        $pk_id = intval($_POST['order_id']); // This is the Primary Key (ID)
        $new_status = $_POST['status'];

        dbg("Admin triggered update: pk_id=$pk_id, new_status=$new_status");

        // Update payment status in payments DB using the Primary Key
        $update = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $pk_id);
        $update->execute();
        dbg("Updated payments.status for order $pk_id.");

        // Fetch user_id and string order_id for this order
        $get = $conn->prepare("SELECT user_id, customer_name, order_id FROM payments WHERE id = ?");
        $get->bind_param("i", $pk_id);
        $get->execute();
        $res = $get->get_result();
        $orderInfo = $res->fetch_assoc();
        $get->close();

        if (empty($orderInfo) || empty($orderInfo['user_id'])) {
            dbg("ERROR: No user_id found for order $pk_id. orderInfo: " . json_encode($orderInfo));
            header("Location: online_order.php?updated=0&msg=no_user");
            exit();
        }

        $user_id = (int)$orderInfo['user_id'];
        $customer_name = $orderInfo['customer_name'] ?? 'User';
        $display_order_id = $orderInfo['order_id'] ?? $pk_id; // Use the string ID for the message if available
        
        dbg("Order belongs to user_id=$user_id, customer_name={$customer_name}");

        // Build message
        switch ($new_status) {
            case 'Pending':
                $message = "Hello $customer_name, your online order #$display_order_id is now Pending. Please wait for processing.";
                break;
            case 'Processing':
                $message = "Your online order #$display_order_id is now Processing. We'll notify you when it's completed.";
                break;
            case 'Completed':
                $message = "Your online order #$display_order_id has been Completed. Thank you!";
                break;
            case 'Cancelled':
                $message = "Your online order #$display_order_id has been Cancelled. Contact support if needed.";
                break;
            default:
                $message = "Your online order #$display_order_id status changed to $new_status.";
        }

        // Insert into notifications DB
        $notifStmt = $notifConn->prepare("INSERT INTO notifications (user_id, order_id, message, status, created_at) VALUES (?, ?, ?, 'unread', NOW())");
        if (!$notifStmt) {
            $err = $notifConn->error;
            dbg("Prepare failed on notifConn: $err");
            throw new Exception("Notif prepare error: $err");
        }
        $notifStmt->bind_param("iis", $user_id, $pk_id, $message);
        $notifStmt->execute();
        dbg("Inserted notification for user_id=$user_id, order_id=$pk_id, message=" . substr($message,0,120));

        $notifStmt->close();

        header("Location: online_order.php?updated=1");
        exit();

    } catch (Exception $e) {
        dbg("EXCEPTION: " . $e->getMessage());
        echo "<div style='color:red;padding:20px;'>Notification error: " . htmlspecialchars($e->getMessage()) . "</div>";
        exit();
    }
}


// ===== Search and Filter Orders LOGIC (UPDATED) =====
$filter = $_GET['filter'] ?? 'All';
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['month'] ?? 'All'; 
$processFilter = $_GET['process'] ?? 'All'; 
$searchParam = "%" . trim($search) . "%";

// Base SQL query: Filter for orders that HAVE a payment proof (Online Orders)
$sql = "SELECT * FROM payments WHERE payment_proof IS NOT NULL AND payment_proof != ''";
$conditions = [];
$types = "";
$params = [];

// 1. Status Filter
if($filter !== 'All'){
    // MODIFIED: If filter is 'Completed', include both 'Completed' and 'Received'
    if ($filter === 'Completed') {
        $conditions[] = "status IN ('Completed', 'Received')";
    } else {
        $conditions[] = "status = ?";
        $types .= "s";
        $params[] = $filter;
    }
}

// 2. Date/Time Filter (Today, Week, Month, Year)
if ($monthFilter !== 'All' && !empty($monthFilter)) {
    if ($monthFilter === 'today') {
        $conditions[] = "DATE(created_at) = CURDATE()";
    } elseif ($monthFilter === 'week') {
        $conditions[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($monthFilter === 'year') {
        $conditions[] = "YEAR(created_at) = YEAR(CURDATE())";
    } elseif (is_numeric($monthFilter)) {
        $conditions[] = "MONTH(created_at) = ?";
        $types .= "i";
        $params[] = (int)$monthFilter;
    }
}

if ($processFilter !== 'All') {
    $conditions[] = "order_details LIKE ?";
    $types .= "s";
    $params[] = "%" . $processFilter . " Process%"; 
}

// 3. Search Filter (Only applies if search term is not empty)
if (!empty(trim($search))) {
    // Search Order ID (order_id or id), Customer Name, or Reference Number
    $conditions[] = "(id LIKE ? OR order_id LIKE ? OR customer_name LIKE ? OR reference_number LIKE ?)";
    $types .= "ssss";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Combine conditions
if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions); // Use AND because we already have a WHERE clause
}

$sql .= " ORDER BY id DESC";

// Prepare and Execute the statement
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    // Dynamically bind parameters
    if (!empty($params)) {
        $bind_params = array_merge([$types], $params);
        $references = [];
        foreach ($bind_params as $key => $value) {
            $references[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $references);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // If no filter or search, run simple query
    $result = $conn->query($sql);
}

// ===== COUNT ONLINE ORDERS =====
$baseCountSql = "SELECT COUNT(*) AS c FROM payments WHERE payment_proof IS NOT NULL AND payment_proof != ''";
$totalOnlineOrders = $conn->query($baseCountSql)->fetch_assoc()['c'];
$pendingCount = $conn->query($baseCountSql . " AND status='Pending'")->fetch_assoc()['c'];
$processingCount = $conn->query($baseCountSql . " AND status='Processing'")->fetch_assoc()['c'];
// MODIFIED: Count both Completed and Received
$completedCount = $conn->query($baseCountSql . " AND status IN ('Completed', 'Received')")->fetch_assoc()['c'];
$cancelledCount = $conn->query($baseCountSql . " AND status='Cancelled'")->fetch_assoc()['c'];

// Helper: try candidate relative paths and return first that exists
function resolve_image_path($storedPath) {
    if (empty($storedPath)) return null;
    $storedPath = str_replace('\\', '/', $storedPath);
    $candidates = [
        $storedPath,
        'components/' . $storedPath,
        '../' . $storedPath,
        '../components/' . $storedPath,
        'uploads/' . basename($storedPath),
        'components/uploads/' . basename($storedPath),
        '../uploads/payments/' . basename($storedPath),
        'uploads/payments/' . basename($storedPath)
    ];
    foreach ($candidates as $c) {
        $fsPath = __DIR__ . '/' . $c;
        if (file_exists($fsPath)) {
            return $c;
        }
    }
    if (strpos($storedPath, '/') === 0) {
        $docRootPath = $_SERVER['DOCUMENT_ROOT'] . $storedPath;
        if (file_exists($docRootPath)) return $storedPath;
    }
    return null;
}

// Array of Months for Dropdown
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Online Orders</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<link rel="stylesheet" href="online_order.css?v=2">

</head>
<style>
     @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
</style>
<body>

<button class="burger-icon" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <h4>PrintEase</h4>
    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="customer_chat.php"><i class="fa-solid fa-message"></i> Customer Chat</a>

    <a href="manage_order.php"><i class="fas fa-tasks"></i> Manage Orders</a>
    <a href="customer.php"><i class="fas fa-users"></i> Customer Management</a>
    <a href="online_order.php" class="active"><i class="fas fa-credit-card"></i> Online Order</a>
    <a href="cash_order.php"><i class="fas fa-money-bill"></i> Cash Order</a>
    <a href="received_order.php"><i class="fas fa-money-bill"></i> Received Order</a>
    
    <a href="admin_profile.php"><i class="fas fa-user-circle"></i> Admin Profile</a>

    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
</div>

<div class="overlay" id="overlay"></div>

<div class="content">

    <div class="header-top-row">
        <h2 class="mb-0">Online Orders</h2>
        <div class="current-datetime" id="currentDateTime"></div>
    </div>
    
    <div class="row mb-4 text-center">
        <div class="col-md-3 mb-3">
            <div class="card bg-c-blue" onclick="window.location='online_order.php?filter=All&search=<?= urlencode($search) ?>'">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6>Total Online Orders</h6>
                    <h2 class="fw-bold mb-0"><?= $totalOnlineOrders ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-c-green" onclick="window.location='online_order.php?filter=Pending&search=<?= urlencode($search) ?>'">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6>Pending Proof</h6>
                    <h2 class="fw-bold mb-0"><?= $pendingCount ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-c-yellow" onclick="window.location='online_order.php?filter=Processing&search=<?= urlencode($search) ?>'">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6>Processing</h6>
                    <h2 class="fw-bold mb-0"><?= $processingCount ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-c-pink" onclick="window.location='online_order.php?filter=Completed&search=<?= urlencode($search) ?>'">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6>Completed</h6>
                    <h2 class="fw-bold mb-0"><?= $completedCount ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success text-center">✅ Order status updated successfully!</div>
    <?php endif; ?>
    
    <div class="table-container mb-4">
            <form method="GET" action="online_order.php" class="filter-form-row">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                
                <div class="month-filter-group">
                    <label for="month-select" class="mb-0">Date:</label>
                    <select id="month-select" name="month" class="form-select" onchange="this.form.submit()">
                        <option value="All" <?= $monthFilter == 'All' ? 'selected' : '' ?>>All Time</option>
                        <option value="today" <?= $monthFilter == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $monthFilter == 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="year" <?= $monthFilter == 'year' ? 'selected' : '' ?>>This Year</option>
                        <option disabled>──────────</option>
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $monthFilter == $num ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="month-filter-group">
                    <label for="process-select" class="mb-0">Process:</label>
                    <select id="process-select" name="process" class="form-select" style="width: 140px;" onchange="this.form.submit()">
                        <option value="All" <?= $processFilter == 'All' ? 'selected' : '' ?>>All Types</option>
                        <option value="Standard" <?= $processFilter == 'Standard' ? 'selected' : '' ?>>Standard</option>
                        <option value="Rush" <?= $processFilter == 'Rush' ? 'selected' : '' ?>>⚡ Rush</option>
                    </select>
                </div>
                
                <div class="input-group search-input-group">
                    <input type="text" class="form-control" name="search" 
                            placeholder="Search by Order ID, Name, or Ref No." 
                            value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">🔍 Search</button>
                </div>
                
                <?php if (!empty($search) || $monthFilter !== 'All' || $processFilter !== 'All'): ?>
                    <small class="text-muted mt-2 d-block w-100">
                        Showing results for 
                        <?php if ($processFilter !== 'All'): ?>
                            <strong><?= htmlspecialchars($processFilter) ?> Process</strong> orders
                        <?php endif; ?>
                        <?php if ($monthFilter !== 'All'): ?>
                            in <strong><?= htmlspecialchars($months[(int)$monthFilter] ?? $monthFilter) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            matching "<strong><?= htmlspecialchars($search) ?></strong>" 
                        <?php endif; ?>
                        . 
                        <a href="online_order.php?filter=<?= urlencode($filter) ?>" class="text-danger">Clear All Filters</a>
                    </small>
                <?php endif; ?>
            </form>
    </div>

    <div class="table-container">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Reference No.</th>
                    <th>Customer</th>
                    <th>Order Type</th>
                    <th>Expected Date</th>
                    <th>Total Price</th>
                    <th>Payment Proof</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $imgUrl = resolve_image_path($row['payment_proof']);
                    
                    // Format the date for cleaner look in the table
                    $expectedDate = $row['expected_date'];
                    $displayDate = 'N/A';
                    if (!empty($expectedDate) && $expectedDate != '0000-00-00') {
                        try {
                            $dateTime = new DateTime($expectedDate);
                            $displayDate = $dateTime->format('M d, Y');
                        } catch (Exception $e) {
                            $displayDate = htmlspecialchars($expectedDate);
                        }
                    }

                    // Display the string order_id if available, otherwise fallback to PK id
                    $displayOrderId = !empty($row['order_id']) ? $row['order_id'] : $row['id'];
                    
                    // Status logic
                    $currentStatus = trim($row['status']);
                    $isFinalState = ($currentStatus == 'Completed' || $currentStatus == 'Cancelled' || $currentStatus == 'Received');
                ?>
                <tr>
                    <td><?= htmlspecialchars($displayOrderId) ?></td>
                    <td><?= htmlspecialchars($row['reference_number'] ?? 'N/A') ?></td>
                    <td>
                        <a href="#" class="customer-link" 
                            data-bs-toggle="modal" 
                            data-bs-target="#orderDetailsModal"
                            data-order-id="<?= htmlspecialchars($displayOrderId) ?>"
                            data-customer-name="<?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?>"
                            data-email="<?= htmlspecialchars($row['email'] ?? 'N/A') ?>"
                            data-contact="<?= htmlspecialchars($row['contact'] ?? 'N/A') ?>"
                            data-address="<?= htmlspecialchars($row['address'] ?? 'N/A') ?>"
                            data-order-type="<?= htmlspecialchars($row['order_type'] ?? '-') ?>"
                            data-quantity="<?= htmlspecialchars($row['quantity'] ?? '1') ?>"
                            data-total-price="₱<?= number_format($row['total_price'], 2) ?>"
                            data-expected-date="<?= htmlspecialchars($displayDate) ?>" 
                            data-design-file-b64="<?= base64_encode($row['design_file'] ?? '') ?>"
                            data-order-details='<?= htmlspecialchars($row['order_details'] ?? '{}', ENT_QUOTES, 'UTF-8') ?>'>
                            <?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['order_type'] ?? '-') ?></td>
                    <td><span class="fw-semibold text-info"><?= $displayDate ?></span></td>
                    <td>₱<?= number_format($row['total_price'], 2) ?></td>
                    <td>
                        <?php if(!empty($imgUrl)): ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                alt="Proof of Payment" 
                                class="payment-proof"
                                data-bs-toggle="modal"
                                data-bs-target="#proofModal"
                                data-img="<?= htmlspecialchars($imgUrl) ?>">
                        <?php else: ?>
                            <span class="text-danger fw-semibold">Error: No Proof</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- ===== STATUS DISPLAY ONLY ===== -->
                    <td>
                        <span class="fw-bold"><?= htmlspecialchars($currentStatus) ?></span>
                    </td>

                    <!-- ===== ACTION COLUMN: DROPDOWN & BUTTON ===== -->
                    <td>
                        <form method="POST" class="d-flex justify-content-center align-items-center">
                            <!-- Keep the Primary Key ID for the update logic -->
                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                            
                            <select name="status" class="form-select form-select-sm status-select me-2" <?= $isFinalState ? 'disabled' : '' ?>>
                                <?php if ($currentStatus == 'Pending'): ?>
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Completed">Completed</option>
                                <?php elseif ($currentStatus == 'Processing'): ?>
                                    <option value="Processing" selected>Processing</option>
                                    <option value="Completed">Completed</option>
                                    <!-- Cannot go back to Pending -->
                                <?php elseif ($currentStatus == 'Completed'): ?>
                                    <option value="Completed" selected>Completed</option>
                                    <!-- Final state -->
                                <?php elseif ($currentStatus == 'Cancelled'): ?>
                                    <option value="Cancelled" selected>Cancelled</option>
                                    <!-- Final state -->
                                <?php elseif ($currentStatus == 'Received'): ?> 
                                    <option value="Received" selected>Received</option>
                                <?php else: ?>
                                    <!-- Fallback for any unknown status -->
                                    <option value="<?= htmlspecialchars($currentStatus) ?>" selected><?= htmlspecialchars($currentStatus) ?></option>
                                    <option value="Pending">Pending</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Completed">Completed</option>
                                <?php endif; ?>
                            </select>
                            
                            <button type="submit" name="update_status" class="btn btn-sm btn-success" <?= $isFinalState ? 'disabled' : '' ?>>Update</button>
                        </form>
                    </td>

                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-muted">No online orders found under <?= htmlspecialchars($filter) ?> with the current search criteria.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img src="" id="proofImage" class="modal-img" alt="Payment Proof">
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailsModalLabel">Order Details for <span id="modalCustomerName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6><strong>Booking Details</strong></h6>
            <p><strong>Order ID:</strong> <span id="modalOrderId"></span></p>
            <p><strong>Customer Name:</strong> <span id="modalCustomerName2"></span></p>
            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
            <p><strong>Contact:</strong> <span id="modalContact"></span></p>
            <p><strong>Notes:</strong> <span id="modalAddress"></span></p>
          </div>
          <div class="col-md-6">
            <h6><strong>Service Details</strong></h6>
            <p><strong>Service Needed:</strong> <span id="modalOrderType"></span></p>
            <div id="modalServiceSpecifics" class="ps-3 mb-2"></div>
            <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
            <p><strong>Total Price:</strong> <span id="modalTotalPrice"></span></p>
            <p><strong>Expected Date:</strong> <span id="modalExpectedDate"></span></p>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><strong>Design File</strong></h6>
                <div id="downloadAllBtnContainer"></div>
            </div>
            <div id="modalDesignFileContainer">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Sidebar Functionality
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
});

// Close sidebar when clicking outside (on overlay)
document.getElementById('overlay').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
});

// Function to update the date and time
function updateDateTime() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
    const dateTimeString = now.toLocaleDateString('en-US', options).replace(',', '') + ' | ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    
    document.getElementById('currentDateTime').textContent = dateTimeString;
}

updateDateTime();
setInterval(updateDateTime, 1000);

// Modal for viewing payment proof
const proofModal = document.getElementById('proofModal');
if (proofModal) {
    proofModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const imgSrc = button.getAttribute('data-img');
        const modalImg = document.getElementById('proofImage');
        modalImg.src = imgSrc;
    });
}

// Modal for viewing order details
const orderDetailsModal = document.getElementById('orderDetailsModal');
if (orderDetailsModal) {
    orderDetailsModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget; // The <a> tag that was clicked

        // Extract info from data-* attributes
        const orderId = button.getAttribute('data-order-id');
        const customerName = button.getAttribute('data-customer-name');
        const email = button.getAttribute('data-email');
        const contact = button.getAttribute('data-contact');
        const address = button.getAttribute('data-address');
        const orderType = button.getAttribute('data-order-type');
        const quantity = button.getAttribute('data-quantity');
        const totalPrice = button.getAttribute('data-total-price');
        const expectedDate = button.getAttribute('data-expected-date');
        const orderDetailsJson = button.getAttribute('data-order-details');
        
        // Decode the Base64 string for safe retrieval
        const designFileB64 = button.getAttribute('data-design-file-b64');
        let designFile = '';

        if (designFileB64) {
            try {
                designFile = atob(designFileB64);
            } catch (e) {
                console.error("Base64 decoding failed:", e);
                designFile = '';
            }
        }
        
        // Update the modal's content.
        orderDetailsModal.querySelector('#modalCustomerName').textContent = customerName;
        orderDetailsModal.querySelector('#modalOrderId').textContent = orderId;
        orderDetailsModal.querySelector('#modalCustomerName2').textContent = customerName;
        orderDetailsModal.querySelector('#modalEmail').textContent = email;
        orderDetailsModal.querySelector('#modalContact').textContent = contact;
        orderDetailsModal.querySelector('#modalAddress').textContent = address;
        orderDetailsModal.querySelector('#modalOrderType').textContent = orderType;
        orderDetailsModal.querySelector('#modalQuantity').textContent = quantity;
        orderDetailsModal.querySelector('#modalTotalPrice').textContent = totalPrice;
        orderDetailsModal.querySelector('#modalExpectedDate').textContent = expectedDate;
        
        // Handle Service Specifics
        const specificsContainer = orderDetailsModal.querySelector('#modalServiceSpecifics');
        specificsContainer.innerHTML = ''; // Clear previous
        try {
            const details = JSON.parse(orderDetailsJson);
            for (const [key, value] of Object.entries(details)) {
                if (value) { // Don't show empty values
                    const p = document.createElement('p');
                    p.style.margin = '0';
                    p.style.fontSize = '0.9rem';
                    p.innerHTML = `<strong>${key}:</strong> ${value}`;
                    specificsContainer.appendChild(p);
                }
            }
        } catch (e) {
            console.error("Could not parse order details JSON:", e);
            specificsContainer.innerHTML = '<p class="text-muted">No specific details available.</p>';
        }


        const modalDesignFileContainer = orderDetailsModal.querySelector('#modalDesignFileContainer');
        modalDesignFileContainer.innerHTML = ''; // Clear previous content

        if (designFile) {
            const files = designFile.split(',').map(f => f.trim()).filter(f => f.length > 0);
            
            files.forEach((cleanedFile, index) => {
                if (cleanedFile) {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = '../uploads/designs/' + cleanedFile; 
                    downloadLink.textContent = `Download Design File ${index + 1}`;
                    downloadLink.className = 'btn btn-primary btn-sm me-2 mb-2';
                    downloadLink.setAttribute('download', cleanedFile);
                    modalDesignFileContainer.appendChild(downloadLink);
                }
            });
            
        } else {
            modalDesignFileContainer.innerHTML = '<p class="text-muted">No design file was uploaded for this order.</p>';
        }
    });
}
</script>

</body>
</html>

<?php $conn->close(); ?>