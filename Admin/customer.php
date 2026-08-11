<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "printease"; // CONFIRMED Database name: printease
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Fetch customer data
// CRITICAL: Using the correct table name 'info' and confirmed column names.
// Note: Using 'contact_number' instead of 'phone_number' for consistency with modal labels.
// If the error persists, the column name 'address' is definitely the problem.
$stmt = $conn->prepare("SELECT id, name, email, address, contact_number FROM info"); // <-- Using 'contact_number'
$stmt->execute();
$result = $stmt->get_result();

// Start buffering the rows and modals
$table_rows = "";
$modal_html = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_id = htmlspecialchars($row['id']);
        $user_name = htmlspecialchars($row['name']);
        $user_email = htmlspecialchars($row['email']);
        // Use confirmed column names from the 'info' table screenshot
        $user_contact = htmlspecialchars($row['contact_number'] ?? ''); // Changed variable to reflect contact_number
        $user_address = htmlspecialchars($row['address'] ?? '');
        
        // Build Table Row: The "View" button now triggers the modal for viewing only
        $table_rows .= "
        <tr>
            <td>{$user_name}</td>
            <td>{$user_email}</td>
            <td>
                <button type='button' class='btn btn-info btn-sm view-customer-btn' data-bs-toggle='modal' data-bs-target='#viewEditModal{$user_id}' data-mode='view'>
                    View
                </button>
                <a href='delete.php?id={$user_id}' class='btn btn-danger btn-sm'>Delete</a>
            </td>
        </tr>";

        // Build View/Edit Modal HTML for this user
        $modal_html .= "
        <div class='modal fade' id='viewEditModal{$user_id}' tabindex='-1' aria-labelledby='viewEditModalLabel{$user_id}' aria-hidden='true'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <form action='update_customer.php' method='POST' id='customerForm{$user_id}'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='viewEditModalLabel{$user_id}'>Customer Details: {$user_name}</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                        <div class='modal-body text-start'>
                            <input type='hidden' name='user_id' value='{$user_id}'>

                            <div class='mb-3'>
                                <label for='name{$user_id}' class='form-label'>Name</label>
                                <input type='text' class='form-control customer-field' id='name{$user_id}' name='name' value='{$user_name}' required>
                            </div>
                            
                            <div class='mb-3'>
                                <label for='email{$user_id}' class='form-label'>Email</label>
                                <input type='email' class='form-control customer-field' id='email{$user_id}' name='email' value='{$user_email}' required>
                            </div>
                            
                            <div class='mb-3'>
                                <label for='contact{$user_id}' class='form-label'>Contact Number</label>
                                <input type='text' class='form-control customer-field' id='contact{$user_id}' name='contact' value='{$user_contact}'>
                            </div>

                            <div class='mb-3'>
                                <label for='address{$user_id}' class='form-label'>Address</label>
                                <input type='text' class='form-control customer-field' id='address{$user_id}' name='address' value='{$user_address}'>
                            </div>

                            <hr class='edit-only-section'>
                            <div class='mb-3 edit-only-section'>
                                <label for='password{$user_id}' class='form-label text-danger'>New Password (Leave blank to keep current)</label>
                                <input type='password' class='form-control customer-field' id='password{$user_id}' name='new_password'>
                                <small class='text-muted'>Enter a new password ONLY if you want to change it.</small>
                            </div>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                            <button type='submit' class='btn btn-warning save-changes-btn'>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>";
    }
}

// Close the statement and connection after all data is processed
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
            /* The critical line: pushes the element to the bottom */
            margin-top: auto; 
            /* Set specific styling for the button, adjust width/margin as needed */
            width: 90%; /* Adjust width to fit the sidebar */
            margin: 200px auto 20px auto; /* Center it with more space at the bottom */
            text-align: center;
            border-radius: 5px;
            padding: 10px;
            box-sizing: border-box; /* Include padding in width calculation */
        }

        /* Content area setup for relative positioning */
        .content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
            position: relative; /* Crucial for positioning the datetime display */
        }
        
        /* New Style for Date and Time Display */
        .datetime-display {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 0.9rem;
            color: #555;
            font-weight: 500;
            background: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            z-index: 10; /* Ensure it stays above other elements */
        }

        .table th, .table td {
            text-align: center;
        }

        .btn-sm {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
    <h4>PrintEase</h4>
    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="customer_chat.php"><i class="fa-solid fa-message"></i> Customer Chat</a>

    <a href="manage_order.php"><i class="fas fa-tasks"></i> Manage Orders</a>
    <a href="customer.php" class="active"><i class="fas fa-users"></i> Customer Management</a>
    <a href="online_order.php"><i class="fas fa-credit-card"></i> Online Order</a>
    <a href="cash_order.php"><i class="fas fa-money-bill"></i> Cash Order</a>
    <a href="received_order.php"><i class="fas fa-money-bill"></i> Received Order</a>
    
    <a href="admin_profile.php"><i class="fas fa-user-circle"></i> Admin Profile</a>

    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
</div>>

    <div class="content">
        <div class="datetime-display">
           
        </div>
        
        <div class="container mt-5">
            <h2>Customer Management</h2>
            <div class="row">
                <?php if (!empty($table_rows)): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $table_rows; // Output the dynamically generated rows ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No customers found!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php echo $modal_html; ?> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Get all buttons that open a customer modal
            const customerModalButtons = document.querySelectorAll('.view-customer-btn');

            customerModalButtons.forEach(button => {
                const modalId = button.getAttribute('data-bs-target'); // e.g., #viewEditModal1
                const modalElement = document.querySelector(modalId);
                const isViewMode = button.getAttribute('data-mode') === 'view';

                // We listen to the Bootstrap event right before the modal is shown
                modalElement.addEventListener('show.bs.modal', function (event) {
                    
                    // Find elements within the specific modal
                    const inputFields = modalElement.querySelectorAll('.customer-field');
                    const saveButton = modalElement.querySelector('.save-changes-btn');
                    const editOnlySections = modalElement.querySelectorAll('.edit-only-section');
                    const modalTitle = modalElement.querySelector('.modal-title');

                    // If the button clicked has data-mode='view' (which is the case for the new 'View' button)
                    if (isViewMode) {
                        // Set all fields to read-only
                        inputFields.forEach(input => {
                            input.setAttribute('readonly', 'readonly');
                        });
                        // Hide the Save Changes button
                        if (saveButton) {
                            saveButton.style.display = 'none';
                        }
                        // Hide the password change section
                        editOnlySections.forEach(section => {
                            section.style.display = 'none';
                        });
                        // Update the modal title to indicate View mode
                        if (modalTitle) {
                             // Get the original name from the heading content (it contains the name)
                             const originalTitle = modalTitle.textContent;
                             // Replace 'Edit' or 'Details' with 'View'
                             modalTitle.textContent = originalTitle.replace('Edit Customer:', 'View Customer:').replace('Customer Details:', 'View Customer:');
                        }
                    } 
                    // To ensure it works if you later introduce an "Edit" button
                    else {
                        // Remove read-only attributes and show the save button for "Edit" mode
                        inputFields.forEach(input => {
                            input.removeAttribute('readonly');
                        });
                        if (saveButton) {
                            saveButton.style.display = 'block';
                        }
                        editOnlySections.forEach(section => {
                            section.style.display = 'block';
                        });
                    }
                });

                // Crucial: Clear the read-only attributes and show buttons when the modal is closed
                // This resets the modal for the *next* time it is opened, which is important
                // if the next action is an "Edit" or another "View" button.
                modalElement.addEventListener('hidden.bs.modal', function (event) {
                    const inputFields = modalElement.querySelectorAll('.customer-field');
                    const saveButton = modalElement.querySelector('.save-changes-btn');
                    const editOnlySections = modalElement.querySelectorAll('.edit-only-section');

                    inputFields.forEach(input => {
                        input.removeAttribute('readonly');
                    });
                    if (saveButton) {
                        saveButton.style.display = 'block'; // Or whatever default is
                    }
                    editOnlySections.forEach(section => {
                        section.style.display = 'block';
                    });
                });
            });
        });
    </script>
</body>
</html>