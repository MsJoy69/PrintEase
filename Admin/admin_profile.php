<?php
include 'auth.php'; // Include authentication check

// Database connection
$user_conn = new mysqli("localhost", "root", "", "printeasee");

if ($user_conn->connect_error) {
    die("Connection failed: " . $user_conn->connect_error);
}

// =========================================================
// 1. SMART ADMIN ID FETCHING (THE FIX)
// =========================================================
$admin = [];
$admin_id = $_SESSION['admin_id'] ?? 0;

// Try to find the admin using the session ID first
if ($admin_id > 0) {
    $stmt = $user_conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
    }
}

// IF NO ADMIN FOUND (e.g. Session is wrong, or ID 1 doesn't exist), GET THE REAL ONE
if (empty($admin)) {
    // This grabs the first admin row from the database (e.g., your ID 3)
    $fallback_query = "SELECT * FROM admins LIMIT 1";
    $fallback_result = $user_conn->query($fallback_query);
    
    if ($fallback_result->num_rows > 0) {
        $admin = $fallback_result->fetch_assoc();
        $admin_id = $admin['id']; // OVERRIDE THE ID TO THE CORRECT ONE (e.g., 3)
        // Optional: fix the session for next time
        $_SESSION['admin_id'] = $admin_id; 
    } else {
        // Fallback only if database is completely empty
         $admin = [
            'id' => 1,
            'username' => 'Admin User',
            'email' => '',
            'phone' => '',
            'role' => 'Administrator',
            'department' => '',
            'reg_date' => date('Y-m-d'),
            'last_login' => date('Y-m-d H:i:s')
        ];
    }
}

// 2. Handle Form Submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $username_input = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $department = $_POST['department'];

        // Use the $admin_id we found above (e.g., 3)
        $update_query = "UPDATE admins SET username=?, email=?, phone=?, department=? WHERE id=?";
        $update_stmt = $user_conn->prepare($update_query);
        
        if ($update_stmt) {
            $update_stmt->bind_param("ssssi", $username_input, $email, $phone, $department, $admin_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                
                // Refresh data immediately
                $stmt = $user_conn->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin = $result->fetch_assoc();
            } else {
                $error_message = "Update failed: " . $user_conn->error;
            }
        } else {
            $error_message = "Database Error: " . $user_conn->error;
        }
    }
    
        // CHANGE PASSWORD
        if (isset($_POST['change_password'])) {
            // Remove $current_password line
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Remove the database fetch for the old password
            // Remove the password_verify() check

            if ($new_password === $confirm_password) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $password_query = "UPDATE admins SET password=? WHERE id=?";
                $password_stmt = $user_conn->prepare($password_query);
                if ($password_stmt) {
                    $password_stmt->bind_param("si", $hashed_new_password, $admin_id);
                    if ($password_stmt->execute()) {
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Failed to update password in database.";
                    }
                }
            } else {
                $error_message = "Passwords do not match!";
            }
        }
}

// 3. Stats Logic
$activity_stats = ['total_orders' => 0, 'pending_orders' => 0, 'completed_orders' => 0, 'total_customers' => 0];

$payment_conn = new mysqli("localhost", "root", "", "payment");
if (!$payment_conn->connect_error) {
    $res = $payment_conn->query("SELECT COUNT(*) as total FROM payments");
    if($res) $activity_stats['total_orders'] = $res->fetch_assoc()['total'];
    
    $res = $payment_conn->query("SELECT COUNT(*) as total FROM payments WHERE status='Pending'");
    if($res) $activity_stats['pending_orders'] = $res->fetch_assoc()['total'];
    
    $res = $payment_conn->query("SELECT COUNT(*) as total FROM payments WHERE status='Received'");
    if($res) $activity_stats['completed_orders'] = $res->fetch_assoc()['total'];
    $payment_conn->close();
}

if (!$user_conn->connect_error) {
    $res = $user_conn->query("SELECT COUNT(*) as total FROM users");
    if($res) $activity_stats['total_customers'] = $res->fetch_assoc()['total'];
}

// Helpers
$display_name = $admin['username'] ?? 'Admin';
$join_date = $admin['reg_date'] ?? date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - PrintEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_profile.css?v=2">
</head>
<style>
     @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
</style>
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
    <a href="admin_profile.php" class="active"><i class="fas fa-user-circle"></i> Admin Profile</a>
    <a href="archived_order.php" class="active"><i class="fas fa-user-circle"></i> archived_order</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
</div>

<div class="content">
    <h2 class="text-center">Admin Profile</h2>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header-card">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($display_name) ?>&size=200&background=0d6efd&color=fff" 
                    alt="Admin Avatar" class="profile-avatar">
            </div>
            <div class="col-md-7">
                <h3><?= htmlspecialchars($display_name) ?></h3>
                <p><i class="fas fa-user-shield"></i> <?= htmlspecialchars($admin['role'] ?? 'Admin') ?></p>
                <p><i class="fas fa-building"></i> <?= htmlspecialchars($admin['department'] ?? 'N/A') ?></p>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($admin['email'] ?? 'N/A') ?></p>
            </div>
            <div class="col-md-3 text-center">
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-receipt text-primary"></i>
                <h4><?= $activity_stats['total_orders'] ?></h4>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-clock text-warning"></i>
                <h4><?= $activity_stats['pending_orders'] ?></h4>
                <p>Pending Orders</p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-check-circle text-success"></i>
                <h4><?= $activity_stats['completed_orders'] ?></h4>
                <p>Completed Orders</p>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-users text-info"></i>
                <h4><?= $activity_stats['total_customers'] ?></h4>
                <p>Total Customers</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="info-card">
                <h4><i class="fas fa-user-circle"></i> Profile Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($display_name) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($admin['email'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($admin['phone'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Role</div>
                        <div class="info-value">
                            <span class="badge bg-primary"><?= htmlspecialchars($admin['role'] ?? 'Admin') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?= htmlspecialchars($admin['department'] ?? 'N/A') ?></div>
                    </div>
                    <!-- <div class="col-md-6">
                        <div class="info-label">Registered Date</div>
                        <div class="info-value"><?= date('F j, Y', strtotime($join_date)) ?></div>
                    </div> -->
                </div>
            </div>

            <!-- Security Settings -->
            <div class="info-card">
                <h4><i class="fas fa-shield-alt"></i> Security Settings</h4>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="info-card">
                <h4><i class="fas fa-history"></i> Recent Activity</h4>
                <div class="activity-item">
                    <strong>Profile Viewed</strong><br>
                    <small class="text-muted">Just now</small>
                </div>
                <div class="activity-item">
                    <strong>System Login</strong><br>
                    <small class="text-muted"><?= date('M j, Y g:i A') ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="name" 
                                       value="<?= htmlspecialchars($display_name) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" 
                                       value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" 
                                       value="<?= htmlspecialchars($admin['department'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                    </div>
                    <div id="passwordError" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Client-side password validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const errorDiv = document.getElementById('passwordError');
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            errorDiv.textContent = 'Passwords do not match!';
            errorDiv.classList.remove('d-none');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            errorDiv.textContent = 'Password must be at least 8 characters long!';
            errorDiv.classList.remove('d-none');
            return false;
        }
        
        errorDiv.classList.add('d-none');
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.closest('.alert')) {
                const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
</script>
</body>
</html>