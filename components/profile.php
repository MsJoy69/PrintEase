<?php
session_start();
if (!isset($_SESSION['user_id'])) { // Check for user_id for consistency
    header("Location: login.php");
    exit();
}

// ====== DATABASE CONNECTIONS ======
// This connects to 'printeasee' (main users table)
include("../db.php"); 
// This connects to 'printease' (profile info table)
$profileDB = new mysqli("localhost", "root", "", "printease");
if ($profileDB->connect_error) {
    die("Profile DB connection failed: " . $profileDB->connect_error);
}

$email = $_SESSION['user']; 
$user_id = $_SESSION['user_id'];
$name_from_session = $_SESSION['user_name'];

// --- FIX FOR DUPLICATE ENTRY ERROR ---
if (empty($name_from_session)) {
    $name_from_session = 'User-' . $user_id; 
}
// ------------------------------------------------

// --- Fetch user data from 'info' table ---
$stmt = $profileDB->prepare("SELECT * FROM info WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// --- Handle case where profile info doesn't exist ---
if (!$userData) {
    $insertStmt = $profileDB->prepare("INSERT INTO info (email, name, username) VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", $email, $name_from_session, $email);
    $insertStmt->execute(); 
    
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
}
$stmt->close();


// Handle image upload
if (isset($_POST['upload'])) {
    $targetDir = "uploads/";
    $uploadPath = __DIR__ . "/../" . $targetDir; 

    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    $fileName = basename($_FILES['profile_pic']['name']);
    $dbPath = $targetDir . time() . "_" . $fileName; 
    $targetFile = $uploadPath . time() . "_" . $fileName; 

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
        $updatePicStmt = $profileDB->prepare("UPDATE info SET profile_pic=? WHERE email=?");
        $updatePicStmt->bind_param("ss", $dbPath, $email);
        $updatePicStmt->execute();
        
        $_SESSION['profile_pic'] = $dbPath;
        $userData['profile_pic'] = $dbPath; 
        
    } else {
        $error = "Failed to upload image. Check permissions.";
    }
}

// Handle profile info update
if (isset($_POST['save_profile'])) {
    // Sanitize user inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $age = htmlspecialchars(trim($_POST['age']));
    $contact = htmlspecialchars(trim($_POST['contact_number']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $emailInput = htmlspecialchars(trim($_POST['email']));
    $address = htmlspecialchars(trim($_POST['address']));
    $city = htmlspecialchars(trim($_POST['city']));
    $phone = htmlspecialchars(trim($_POST['phone_number']));

    // Start a transaction
    $conn->begin_transaction();
    $profileDB->begin_transaction();

    try {
        // 1. Update the 'info' table
        $infoStmt = $profileDB->prepare(
            "UPDATE info SET name=?, age=?, contact_number=?, gender=?, email=?, address=?, city=?, phone_number=? WHERE email=?"
        );
        $infoStmt->bind_param("sssssssss", $name, $age, $contact, $gender, $emailInput, $address, $city, $phone, $email);
        $infoStmt->execute();

        // 2. Update the 'users' table
        $userStmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $userStmt->bind_param("ssi", $name, $emailInput, $user_id);
        $userStmt->execute();

        // Commit transactions
        $conn->commit();
        $profileDB->commit();

        $success = "Profile updated successfully!";
        
        // Update session variables
        $_SESSION['user'] = $emailInput;
        $_SESSION['user_name'] = $name;

        // Re-fetch data
        $email = $emailInput; 
        $userData = $profileDB->query("SELECT * FROM info WHERE email='$email'")->fetch_assoc();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $profileDB->rollback();
        if (strpos($exception->getMessage(), 'Duplicate entry') !== false) {
             $error = "Failed to update profile. The email or name you entered may already be in use.";
        } else {
            $error = "Failed to update profile. Error: " . $exception->getMessage();
        }
    }
}

$rawImagePath = $userData['profile_pic'] ?? null;

$defaultImage = "../image/defaultPic.png"; 

$profileImage = $defaultImage;

if (!empty($rawImagePath)) {
    $serverPath = __DIR__ . "/../" . $rawImagePath;
    
    if (file_exists($serverPath)) {
        $profileImage = "../" . $rawImagePath;
    }
}

$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Printease - Profile</title>
<!-- Removed old separate CSS link and added inline styles for design improvement -->
<link rel="stylesheet" href="../style.css?v=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* --- New CSS for Profile Page Design --- */

/* Ensures the body/main content uses the full height */
.main-content {
    padding: 20px;
    background-color: #f0f2f5; /* Light background for contrast */
    min-height: 100vh;
}

.profile-container {
    display: flex;
    gap: 30px;
    padding-top: 20px;
    max-width: 1200px;
    margin: 0 auto; /* Center the profile content */
}

/* Profile Card (Left Side) */
.profile-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 30px 20px;
    text-align: center;
    width: 300px; /* Fixed width for the profile summary card */
    flex-shrink: 0;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.profile-image-section {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-image-section img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #2f6f56; /* Accent border color */
    transition: transform 0.3s ease;
}

.profile-image-section img:hover {
    transform: scale(1.05);
}

.profile-image-section button {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background-color: #2f6f56;
    color: white;
    border: 2px solid #fff;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.profile-image-section button:hover {
    background-color: #3b8e6a;
}

.profile-card h2 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 5px;
}

.profile-card p {
    color: #777;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

/* Profile Form (Right Side) */
.profile-form-section {
    flex-grow: 1;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 30px;
}

.profile-form-section h3 {
    font-size: 1.25rem;
    color: #2f6f56;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
    margin-bottom: 25px;
    font-weight: 600;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-row input,
.form-row select {
    flex-grow: 1;
    padding: 12px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-row input:focus,
.form-row select:focus {
    border-color: #2f6f56;
    outline: none;
    box-shadow: 0 0 0 3px rgba(47, 111, 86, 0.2);
}

.save-btn {
    background-color: #2f6f56;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 20px;
    transition: background-color 0.3s, transform 0.1s;
    float: right;
}

.save-btn:hover {
    background-color: #3b8e6a;
}

.save-btn:active {
    transform: scale(0.99);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .profile-container {
        flex-direction: column;
        gap: 20px;
    }
    .profile-card {
        width: 100%;
        position: static;
        order: 1; /* Move card to the top on mobile */
    }
    .profile-form-section {
        order: 2;
    }
}

@media (max-width: 600px) {
    .form-row {
        flex-direction: column;
        gap: 10px;
    }
}
</style>
</head>
<body>

<!-- Sidebar (unchanged) -->
<div class="sidebar">
    <div class="logo">
        <img src="../image/logo.png" alt="Printease Logo">
        <h2>PRINTEASE</h2>
    </div>

    <ul class="menu">
        <li><a href="../index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
        <li><a href="../components/product.php"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
        <li><a href="../components/order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
        <li><a href="../components/notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
                    <li><a href="../components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>

        <li><a href="../components/profile.php" class="active"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
    </ul>

    <div class="logout">
        <a href="logout.php">Logout</a>
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
                    <div class="account-trigger">
                        <span><?php echo htmlspecialchars($displayName); ?></span>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="<?php echo $componentPath; ?>profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                        <a href="<?php echo $componentPath; ?>product.php"><i class="fa-solid fa-box"></i> Product Catalog</a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="logout.php" class="logout-link">
                            <i class="fa-solid fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
			    </div>
        </div>
    </div>

    <!-- START OF REDESIGNED PROFILE SECTION -->
    <div class="profile-container">
        
        <!-- Left Side: Profile Summary Card -->
        <div class="profile-card">
            <div class="profile-image-section">
                <!-- Display using the calculated $profileImage path -->
                <img id="profileDisplay" src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture">

                <!-- Upload form for editing image -->
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" style="display:none;">
                    <button type="button" onclick="document.getElementById('profile_pic').click();"><i class="fa-solid fa-pencil"></i></button>
                    <input type="submit" name="upload" style="display:none;" id="submitUpload">
                </form>
            </div>

            <h2><?php echo htmlspecialchars($userData['name'] ?? $displayName); ?></h2>
            <p><?php echo htmlspecialchars($userData['email'] ?? $email); ?></p>
            
            <div style="text-align: left; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee;">
                <p style="margin-bottom: 10px; color: #555;">
                    <i class="fa-solid fa-id-card-clip" style="color: #2f6f56; width: 20px;"></i> 
                    Username: <?php echo htmlspecialchars($userData['name'] ?? ''); ?>
                </p>
                <p style="margin-bottom: 10px; color: #555;">
                    <i class="fa-solid fa-mobile-alt" style="color: #2f6f56; width: 20px;"></i> 
                    Contact: <?php echo htmlspecialchars($userData['contact_number'] ?? 'Not set'); ?>
                </p>
                <p style="margin-bottom: 10px; color: #555;">
                    <i class="fa-solid fa-city" style="color: #2f6f56; width: 20px;"></i> 
                    Location: <?php echo htmlspecialchars($userData['city'] ?? 'Not set'); ?>
                </p>
            </div>
        </div>

        <!-- Right Side: Edit Form -->
        <div class="profile-form-section">
            <form method="POST">
                
                <h3><i class="fa-solid fa-user-circle"></i> Personal Details</h3>
                <div class="form-row">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name'] ?? ''); ?>" placeholder="Full Name" required>
                    <input type="number" name="age" value="<?php echo htmlspecialchars($userData['age'] ?? ''); ?>" placeholder="Age">
                </div>

                <div class="form-row">
                    <input type="text" name="contact_number" value="<?php echo htmlspecialchars($userData['contact_number'] ?? ''); ?>" placeholder="Contact Number">
                    <select name="gender">
                        <option value="">-- Select Gender --</option>
                        <option value="male" <?php if (($userData['gender'] ?? '') == 'male') echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if (($userData['gender'] ?? '') == 'female') echo 'selected'; ?>>Female</option>
                        <option value="other" <?php if (($userData['gender'] ?? '') == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                
                <h3 style="margin-top: 40px;"><i class="fa-solid fa-envelope"></i> Contact & Location</h3>
                <div class="form-row">
                    <!-- Email is displayed but remains editable for change -->
                    <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" placeholder="Email" required>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number'] ?? ''); ?>" placeholder="Phone Number">
                </div>

                <div class="form-row">
                    <input type="text" name="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>" placeholder="Street Address">
                    <input type="text" name="city" value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>" placeholder="City / Province">
                </div>

                <button type="submit" name="save_profile" class="save-btn">Save Changes</button>
            </form>

            <?php if (!empty($success)) echo "<p style='color:green; text-align:right; margin-top: 60px;'>$success</p>"; ?>
            <?php if (!empty($error)) echo "<p style='color:red; text-align:right; margin-top: 60px;'>$error</p>"; ?>
        </div>
    </div>
</div>

<script>
const profileInput = document.getElementById('profile_pic');
const profileDisplay = document.getElementById('profileDisplay');
const submitUpload = document.getElementById('submitUpload');

profileInput.addEventListener('change', function(){
    if(profileInput.files && profileInput.files[0]){
        // Client-side preview
        profileDisplay.src = URL.createObjectURL(profileInput.files[0]);
        // Submit the form to handle server-side upload and DB update.
        submitUpload.click();
    }
});
</script>

</body>
</html>