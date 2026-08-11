<?php
include 'auth.php'; // Include authentication check

// --- FIX: Set Timezone for accurate time display ---
date_default_timezone_set('Asia/Manila');

// Database connection (payment db for orders, user db for customer count)
$conn = new mysqli("localhost", "root", "", "payment");
if ($conn->connect_error) {
    die("Payment Database Connection Failed: " . $conn->connect_error);
}

$user_conn = new mysqli("localhost", "root", "", "printeasee");
if ($user_conn->connect_error) {
    die("User Database Connection Failed: " . $user_conn->connect_error);
}

// Ensure necessary columns exist in the payments table
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Pending'");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS design_file VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS order_details TEXT NULL");
$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS status_time DATETIME DEFAULT NULL");

// --- Fetch Dashboard Data ---

// Total Orders (All)
$totalOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments");
$totalOrders = $totalOrdersResult->fetch_assoc()['total'];

// Online Orders (has payment_proof)
$onlineOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE payment_proof IS NOT NULL AND payment_proof != ''");
$onlineOrders = $onlineOrdersResult->fetch_assoc()['total'];

// Cash Orders (no payment_proof)
$cashOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE payment_proof IS NULL OR payment_proof = ''");
$cashOrders = $cashOrdersResult->fetch_assoc()['total'];

// Customer Count
$customerCountResult = $user_conn->query("SELECT COUNT(*) AS total FROM users");
$customerCount = $customerCountResult->fetch_assoc()['total'];

// Pending Orders Count
$pendingOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status = 'Pending'");
$pendingOrders = $pendingOrdersResult->fetch_assoc()['total'];

// Processing Orders Count
$processingOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status = 'Processing'");
$processingOrders = $processingOrdersResult->fetch_assoc()['total'];

// Completed Orders Count
$completedOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status = 'Completed'");
$completedOrders = $completedOrdersResult->fetch_assoc()['total'];

// Cancelled Orders Count
$cancelledOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status = 'Cancelled'");
$cancelledOrders = $cancelledOrdersResult->fetch_assoc()['total']; // Still calculated, but removed from display

// >>> Received Orders Count <<<
$receivedOrdersResult = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status = 'Received'");
$receivedOrders = $receivedOrdersResult->fetch_assoc()['total'];

// Calculate total revenue (Includes both Completed and Received)
$totalRevenueResult = $conn->query("SELECT SUM(total_price) AS revenue FROM payments WHERE status IN ('Completed', 'Received')");
$totalRevenue = $totalRevenueResult->fetch_assoc()['revenue'] ?? 0;

// --- Fetch Recent Order Activity ---
$recentActivityQuery = "SELECT id, customer_name, order_type, total_price, status, created_at 
                        FROM payments 
                        ORDER BY created_at DESC 
                        LIMIT 5";
$recentActivityResult = $conn->query($recentActivityQuery);
$recentActivities = [];
if ($recentActivityResult->num_rows > 0) {
    while ($row = $recentActivityResult->fetch_assoc()) {
        $recentActivities[] = $row;
    }
}

// Fetch 5 most recent customers for the new card
// FIXED: Changed 'full_name' to 'name' based on users.sql structure.
$recentCustomersQuery = "SELECT id, name, email, created_at 
                         FROM users 
                         ORDER BY created_at DESC 
                         LIMIT 5";
$recentCustomersResult = $user_conn->query($recentCustomersQuery);
$recentCustomers = [];
if ($recentCustomersResult->num_rows > 0) {
    while ($row = $recentCustomersResult->fetch_assoc()) {
        $recentCustomers[] = $row;
    }
}


$conn->close();
$user_conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PrintEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
         @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
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
            align-items: center;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar h4 {
            text-align: center;
            width: 100%;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .sidebar-menu {
            width: 100%;
            flex-grow: 1;
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
        .logout-btn {
            width: 90%;
            margin: 20px auto;
            text-align: center;
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
            margin-bottom: 30px;
        }

        /* --- Main Dashboard Cards Row 1 (Restored Custom Style) --- */
        .metric-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
            height: 140px;
            display: flex;
            align-items: center;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-card-content {
            position: relative;
            z-index: 2;
        }

        .metric-card .title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #777;
            margin-bottom: 5px;
        }

        .metric-card .value {
            font-size: 2.5rem;
            font-weight: 800;
        }

        /* Border Color for Custom Cards */
        .metric-card.total-orders { border-left: 5px solid #28a745; }
        .metric-card.total-orders .value { color: #28a745; }

        .metric-card.online-orders { border-left: 5px solid #007bff; }
        .metric-card.online-orders .value { color: #007bff; }

        .metric-card.cash-orders { border-left: 5px solid #ffc107; }
        .metric-card.cash-orders .value { color: #ffc107; }

        .metric-card.customer-count { border-left: 5px solid #dc3545; }
        .metric-card.customer-count .value { color: #dc3545; }

        /* Background Icons (Simulated from the image) */
        .bg-icon {
            position: absolute;
            right: 0px;
            bottom: -5px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(-10deg);
            z-index: 1;
        }
        .metric-card.total-orders .bg-icon { color: #28a745; }
        .metric-card.online-orders .bg-icon { color: #007bff; }
        .metric-card.cash-orders .bg-icon { color: #ffc107; }
        .metric-card.customer-count .bg-icon { color: #dc3545; }
        /* ----------------------------------------------------------- */


        /* Revenue Card */
        .revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .revenue-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .revenue-card .amount {
            font-size: 2.5rem;
            font-weight: 700;
        }

        /* Recent Activity Table */
        .activity-table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-top: 0px; 
        }
        .activity-table-container h3 {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .activity-table thead {
            background: #0d6efd;
            color: #fff;
        }
        .activity-table tbody tr:hover {
            background-color: #f1f3f5;
            transition: 0.3s;
        }
        .activity-table .badge {
            font-size: 0.85em;
            padding: 0.5em 0.8em;
        }

        /* Quick Stats Section */
        .quick-stats {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .quick-stats h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }
        .stat-item {
            padding: 15px;
            border-left: 3px solid #0d6efd;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-item:hover {
            background-color: #e9ecef;
        }
        .stat-item .label {
            font-weight: 600;
            color: #555;
        }
        .stat-item .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0d6efd;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .chart-container h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #198754 0%, #0a58ca 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .welcome-banner h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .welcome-banner p {
            margin: 0;
            opacity: 0.9;
        }

        /* New Customer Management Card Style */
        .customer-management-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }
        .customer-management-card h3 {
            font-weight: 600;
            color: #dc3545; /* Consistent color for customer section */
            margin-bottom: 20px;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 10px;
        }
        .customer-management-card table th {
            background: #dc3545;
            color: white;
        }
        .customer-management-card table tbody tr:hover {
            background-color: #fcebeb;
        }

    </style>
</head>
<body>

<div class="sidebar">
    <h4>PrintEase</h4>
    <div class="sidebar-menu">
        <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="customer_chat.php"><i class="fa-solid fa-message"></i> Customer Chat</a>
        <a href="manage_order.php"><i class="fas fa-tasks"></i> Manage Orders</a>
        <a href="customer.php"><i class="fas fa-users"></i> Customer Management</a>
        <a href="online_order.php"><i class="fas fa-credit-card"></i> Online Order</a>
        <a href="cash_order.php"><i class="fas fa-money-bill"></i> Cash Order</a>
        <a href="received_order.php"><i class="fas fa-money-bill"></i> Received Order</a>
        <a href="admin_profile.php"><i class="fas fa-user-circle"></i> Admin Profile</a>
    </div>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
</div>

<div class="content">
    <div class="welcome-banner">
        <h1><i class="fas fa-tachometer-alt"></i> Welcome to PrintEase Dashboard</h1>
        <p><i class="fas fa-calendar-alt"></i> <?= date('l, F j, Y') ?> | <i class="fas fa-clock"></i> <?= date('g:i A') ?></p>
    </div>


    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card total-orders" onclick="window.location='manage_order.php?filter=All'">
                <div class="metric-card-content">
                    <div class="title">TOTAL ORDERS</div>
                    <div class="value"><?= $totalOrders ?></div>
                </div>
                <i class="fas fa-receipt bg-icon"></i> 
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card online-orders" onclick="window.location='online_order.php'">
                 <div class="metric-card-content">
                    <div class="title">ONLINE ORDERS</div>
                    <div class="value"><?= $onlineOrders ?></div>
                </div>
                <i class="fas fa-credit-card bg-icon"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card cash-orders" onclick="window.location='cash_order.php'">
                <div class="metric-card-content">
                    <div class="title">CASH ORDERS</div>
                    <div class="value"><?= $cashOrders ?></div>
                </div>
                <i class="fas fa-coins bg-icon"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card customer-count" onclick="window.location='customer.php'">
                <div class="metric-card-content">
                    <div class="title">TOTAL CUSTOMERS</div>
                    <div class="value"><?= $customerCount ?></div>
                </div>
                <i class="fas fa-users bg-icon"></i>
            </div>
        </div>
    </div>

    <div class="row"> 
        <div class="col-lg-4">
            <div class="revenue-card">
                <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
                <div class="amount">₱<?= number_format($totalRevenue, 2) ?></div>
                <p class="mt-2 mb-0"><small>From completed and received orders</small></p>
            </div>

            <div class="quick-stats">
                <h4><i class="fas fa-chart-bar"></i> Quick Stats</h4>
                <div class="stat-item">
                    <span class="label">Pending Orders</span>
                    <span class="stat-value"><?= $pendingOrders ?></span>
                </div>
                <div class="stat-item">
                    <span class="label">Processing Orders</span>
                    <span class="stat-value"><?= $processingOrders ?></span>
                </div>
                <div class="stat-item">
                    <span class="label">Completed Orders</span>
                    <span class="stat-value"><?= $completedOrders ?></span>
                </div>
                <div class="stat-item">
                    <span class="label">Received Orders</span>
                    <span class="stat-value"><?= $receivedOrders ?></span>
                </div>
                </div>
        </div>

        <div class="col-lg-8">
            <div class="activity-table-container">
                <h3><i class="fas fa-history"></i> Recent Order Activity</h3>
                <table class="table table-bordered table-hover text-center align-middle activity-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Order Type</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentActivities)): ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><?= htmlspecialchars($activity['id']) ?></td>
                                    <td><?= htmlspecialchars($activity['customer_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($activity['order_type'] ?? 'N/A') ?></td>
                                    <td>₱<?= number_format($activity['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= ($activity['status']=='Pending'?'bg-warning text-dark':
                                               ($activity['status']=='Processing'?'bg-info':
                                               ($activity['status']=='Completed'?'bg-success':
                                               ($activity['status']=='Received'?'bg-primary': // Added style for Received
                                               ($activity['status']=='Cancelled'?'bg-danger':'bg-secondary'))))) ?>">
                                            <?= htmlspecialchars($activity['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-muted">No recent order activity found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="text-center mt-3">
                    <a href="manage_order.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="chart-container">
                <h4><i class="fas fa-chart-pie"></i> Order Status Distribution</h4>
                <canvas id="orderStatusChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-container">
                <h4><i class="fas fa-chart-line"></i> Payment Methods</h4>
                <canvas id="paymentMethodChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="customer-management-card">
                <h3><i class="fas fa-users"></i> Recent Customer Registrations</h3>
                <table class="table table-bordered table-hover activity-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentCustomers)): ?>
                            <?php foreach ($recentCustomers as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($customer['id']) ?></td>
                                    <td><?= htmlspecialchars($customer['name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($customer['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted text-center">No recent customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="text-center mt-3">
                    <a href="customer.php" class="btn btn-danger">
                        <i class="fas fa-user-circle"></i> Manage All Customers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusChart = new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        // MODIFICATION: Removed 'Cancelled' from labels
        labels: ['Pending', 'Processing', 'Completed', 'Received'],
        datasets: [{
            // MODIFICATION: Removed $cancelledOrders data point
            data: [<?= $pendingOrders ?>, <?= $processingOrders ?>, <?= $completedOrders ?>, <?= $receivedOrders ?>],
            // MODIFICATION: Removed the corresponding background color (#dc3545) for Cancelled
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#0d6efd'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});

// Payment Method Chart
const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
const paymentMethodChart = new Chart(paymentMethodCtx, {
    type: 'bar',
    data: {
        labels: ['Online Payment', 'Cash Payment'],
        datasets: [{
            label: 'Number of Orders',
            data: [<?= $onlineOrders ?>, <?= $cashOrders ?>],
            backgroundColor: ['#198754', '#ffc107'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Add animation on load
document.addEventListener('DOMContentLoaded', function() {
    // Target all cards that should animate
    const cards = document.querySelectorAll('.metric-card, .revenue-card, .quick-stats, .activity-table-container, .chart-container, .customer-management-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 50);
    });
});
</script>

</body>
</html>