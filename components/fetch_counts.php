<?php
session_start();

if(!isset($_SESSION['user'])) exit();

$paymentDB = new mysqli("localhost", "root", "", "payment");
$infoDB = new mysqli("localhost", "root", "", "printease");

$username = $_SESSION['user'];
$infoQuery = $infoDB->prepare("SELECT name FROM info WHERE username = ?");
$infoQuery->bind_param("s", $username);
$infoQuery->execute();
$infoResult = $infoQuery->get_result()->fetch_assoc();
$name = $infoResult['name'] ?? $username;
$like = "%$name%";

$countQuery = $paymentDB->prepare("
    SELECT 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) AS processing_count,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN status = 'Received' THEN 1 ELSE 0 END) AS received_count 
    FROM payments
    WHERE username = ? OR customer_name LIKE ?
");
$countQuery->bind_param("ss", $username, $like);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();

$pendingCount = $countResult['pending_count'] ?? 0;
$processingCount = $countResult['processing_count'] ?? 0;
$completedCount = $countResult['completed_count'] ?? 0;
$receivedCount = $countResult['received_count'] ?? 0; // ✅ New: Received Count
?>

<div class="d-flex justify-content-around my-4 text-center">
    <div class="status-btn" data-status="Pending">
        <div class="position-relative status-icon">
            <i class="fas fa-clock fa-2x text-secondary"></i>
            <?php if($pendingCount > 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                    <?= $pendingCount ?>
                </span>
            <?php endif; ?>
        </div>
        <small>Pending</small>
    </div>

    <div class="status-btn" data-status="Processing">
        <div class="position-relative status-icon">
            <i class="fas fa-cog fa-2x text-primary"></i>
            <?php if($processingCount > 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                    <?= $processingCount ?>
                </span>
            <?php endif; ?>
        </div>
        <small>Processing</small>
    </div>

    <div class="status-btn" data-status="Completed">
        <div class="position-relative status-icon">
            <i class="fas fa-check-circle fa-2x text-success"></i>
            <?php if($completedCount > 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                    <?= $completedCount ?>
                </span>
            <?php endif; ?>
        </div>
        <small>Completed</small>
    </div>
    
    <div class="status-btn" data-status="Received">
        <div class="position-relative status-icon">
            <!-- ⭐ CHANGED ICON for Order History/Received Status -->
            <i class="fas fa-history fa-2x text-info"></i>
            <?php if($receivedCount > 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                    <?= $receivedCount ?>
                </span>
            <?php endif; ?>
        </div>
        <small>Order History</small>
    </div>
</div>

<?php
$countQuery->close();
$infoQuery->close();
$paymentDB->close();
$infoDB->close();
?>