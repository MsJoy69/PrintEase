<?php
include 'auth.php';

// ===== DB CONNECTIONS & Column Checks (Keep as is) =====
// NOTE: Ang code na ito ay gumagamit ng 'payment' database, hindi 'order_cash'. 
// Siguraduhin na ito ang tamang database at table na nais mong gamitin.
$conn = new mysqli("localhost", "root", "", "payment");
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

$notifConn = new mysqli("localhost", "root", "", "notification");
if($notifConn->connect_error){
    die("Notification DB connection failed: " . $notifConn->connect_error);
}

// Ensure columns exist (safe - won't break if already present)
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Pending'");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS design_file VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS order_details TEXT NULL");

// 🔑 NEW: Add status_time for expiration logic
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS status_time DATETIME DEFAULT NULL"); 
// =========================================================================

// --- ACTION BUTTON/FORM REMOVED: NO STATUS UPDATE IS ALLOWED ON THIS PAGE ---
// --- Keep the dbg function just in case ---
function dbg($msg) {
    global $logFile;
    // file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND);
}

// ===== Filter Orders (MODIFIED FOR ARCHIVED CASH ORDERS ONLY) =====
// BASE QUERY: Only cash orders (NO payment_proof) AND status = 'Archived' 
$base_query_archived = "FROM payments WHERE (payment_proof IS NULL OR payment_proof = '') AND status = 'Archived'"; 

// This page only shows Archived orders, so filtering is simplified.
$result = $conn->query("SELECT * $base_query_archived ORDER BY id DESC");


// ===== COUNT ORDERS (MODIFIED FOR ARCHIVED CASH ORDERS ONLY) =====
$count_base = "FROM payments WHERE payment_proof IS NULL OR payment_proof = ''";

// Only count ARCHIVED orders for the main card on this page
$totalArchivedOrders = $conn->query("SELECT COUNT(*) AS c $count_base AND status = 'Archived'")->fetch_assoc()['c'];
$archivedCount = $totalArchivedOrders; 

// Helper: try candidate relative paths and return first that exists (Keep as is)
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archived Cash Orders</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
/* ... your existing CSS style block ... */
body {
    background: #f4f6f9;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #343a40;
    color: white;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: start;
    padding: 20px 0;
    position: fixed;
    height: 100%;
}
.sidebar h4 {
    text-align: center;
    width: 100%;
    margin-bottom: 25px;
    font-weight: 600;
}
.sidebar a {
    color: #ddd;
    padding: 12px 20px;
    text-decoration: none;
    display: block;
    width: 100%;
    transition: 0.3s;
    font-size: 15px;
}
.sidebar a:hover,
.sidebar a.active {
    background-color: #198754;
    color: white;
}

/* Content */
.content {
    margin-left: 250px;
    padding: 40px 50px;
    width: calc(100% - 250px);
}

h2 {
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: 0.3s ease;
    height: 140px;
    cursor: pointer;
}
.card:hover {
    transform: scale(1.05);
    opacity: 0.95;
}
.bg-c-archived { background: #6c757d; color: #fff; } /* Color for Archived */

/* Table */
.table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    padding: 25px;
}
.table thead {
    background: #0d6efd;
    color: #fff;
}
.table tbody tr:hover {
    background-color: #f1f3f5;
    transition: 0.3s;
}
.payment-proof {
    max-width: 70px;
    border-radius: 8px;
    transition: 0.3s;
    cursor: pointer;
    border: 2px solid transparent;
}
.payment-proof:hover {
    transform: scale(1.1);
    border-color: #0d6efd;
}

/* Clickable customer name */
/* MODIFIED: Changed color to indicate it leads to a notification, not details */
.customer-link {
    color: #dc3545; /* Red color to signify 'Expired/Archived' status when clicked */
    text-decoration: underline;
    cursor: pointer;
    font-weight: 500;
}
.customer-link:hover {
    color: #b02a37;
}


/* Modal (Keeping the modal structure for Proof, but removing Order Details Modal HTML) */
.modal-img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
}
.order-card {
    height: 140px;
    cursor: default; /* Changed cursor to default since the card is no longer clickable */
    transition: all 0.3s ease;
}
.order-card:hover {
    transform: none; /* Removed hover effect */
    box-shadow: 0 3px 10px rgba(0,0,0,0.1); /* Default shadow */
    opacity: 1;
}

</style>
</head>
<body>

<div class="sidebar">
    <h4>PrintEase</h4>
    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="customer_chat.php"><i class="fa-solid fa-message"></i> Customer Chat</a>

    <a href="manage_order.php"><i class="fas fa-tasks"></i> Manage Orders</a>
    <a href="customer.php"><i class="fas fa-users"></i> Customer Management</a>
    <a href="online_order.php"><i class="fas fa-credit-card"></i> Online Order</a>
    <a href="cash_order.php"><i class="fas fa-money-bill"></i> Cash Order</a>
    <a href="received_order.php"><i class="fas fa-money-bill"></i> Received Order</a>
    <a href="archived_order.php" class="active"><i class="fa-solid fa-box-archive"></i> Archived Order</a>
    <a href="admin_profile.php"><i class="fas fa-user-circle"></i> Admin Profile</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
</div>

<div class="content">
    <h2 class="text-center">📦 Archived Cash Orders</h2>

    <div class="row mb-4 text-center justify-content-center">
        <div class="col-md-3 mb-3">
            <div class="card bg-c-archived order-card">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6>Total Archived Orders</h6>
                    <h2 class="fw-bold mb-0"><?= $archivedCount ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger text-center">
        🔒 These orders are permanently archived (expired/not paid on time) and **cannot be edited or reactivated** from this page.
    </div>


    <div class="table-container">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Reference No.</th>
                    <th>Customer</th>
                    <th>Order Type</th>
                    <th>Total Price</th>
                    <th>Payment Proof</th>
                    <th>Status</th>
                    </tr>
            </thead>
            <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $imgUrl = resolve_image_path($row['payment_proof']);
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['reference_number'] ?? 'N/A') ?></td>
                    <td>
                        <a href="#" class="customer-link" 
                            data-bs-toggle="modal" 
                            data-bs-target="#expiredAlertModal"
                            data-order-id="<?= $row['id'] ?>"
                            data-customer-name="<?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?>">
                                         <?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['order_type'] ?? '-') ?></td>
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
                            <span class="text-success fw-semibold">Cash</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-muted">No archived cash orders found.</td></tr>
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

<div class="modal fade" id="expiredAlertModal" tabindex="-1" aria-labelledby="expiredAlertModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="expiredAlertModalLabel">Order Status: Expired/Archived</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p class="lead text-danger fw-bold">This Order (<span id="alertOrderId"></span>) for <span id="alertCustomerName"></span> is **EXPIRED**.</p>
        <p>It was automatically archived because it was completed but not claimed/paid within the required timeframe.</p>
        <p class="text-muted">No further action or detail viewing is available here.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Modal for viewing payment proof (kept as is)
const proofModal = document.getElementById('proofModal');
if (proofModal) {
    proofModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const imgSrc = button.getAttribute('data-img');
        const modalImg = document.getElementById('proofImage');
        modalImg.src = imgSrc;
    });
}

// NEW Script for Expired Alert Modal
const expiredAlertModal = document.getElementById('expiredAlertModal');
if (expiredAlertModal) {
    expiredAlertModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget; // The <a> tag that was clicked
        
        // Extract info from data-* attributes
        const orderId = button.getAttribute('data-order-id');
        const customerName = button.getAttribute('data-customer-name');

        // Update the modal's content.
        expiredAlertModal.querySelector('#alertOrderId').textContent = `#${orderId}`;
        expiredAlertModal.querySelector('#alertCustomerName').textContent = customerName;
    });
}

// REMOVED orderDetailsModal JS handler as it is no longer used.
</script>

</body>
</html>

<?php $conn->close(); ?>