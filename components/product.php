<?php
session_start();
// Define base URL for internal links to prevent double components/ path issue.
// Assuming product.php is inside a 'components' directory one level up from the base.
$baseUrl = '../'; 
$componentPath = './'; 
// Note: Relative path is used for included files, but $baseUrl is for root-based links.

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) { 
	header("Location: login.php");
	exit();
}

$user_id = $_SESSION['user_id'];
$displayName = $_SESSION['user_name'] ?? $_SESSION['user'];

// --- Determine the profile image path for the top bar (Simplified) ---
$profileImage = "https://via.placeholder.com/30/2f6f56/ffffff?text=U";
if (!empty($_SESSION['profile_pic'])) {
	$profileImage = $_SESSION['profile_pic'];
}

// --- Get notification count ---
$notificationCount = 0;
$notificationDB = new mysqli("localhost", "root", "", "notification");
if (!$notificationDB->connect_error) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'";
    $stmt = $notificationDB->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $notificationCount = $row['count'];
    }
    $stmt->close();
    $notificationDB->close();
}

// --- Placeholder: Product Catalog Data ---
$productCatalog = [
    ['name' => 'PRINT', 'price_start' => 10.00, 'image' => 'Printpic(1).jpg', 'link' => 'printpict.php'],
    ['name' => 'LAMINATE', 'price_start' => 15.00, 'image' => 'Laminate.png', 'link' => 'laminate.php'],
    ['name' => 'STICKER', 'price_start' => 20.00, 'image' => 'Sticker.jpg', 'link' => 'sticker.php'],
    ['name' => 'POST CARD', 'price_start' => 30.00, 'image' => 'postcard1.jpg', 'link' => 'postcard.php'],
    ['name' => 'MAGAZINE', 'price_start' => 150.00, 'image' => 'magazine.jpg', 'link' => 'magazine.php'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Printease - Product Catalog</title>
<link rel="stylesheet" href="<?php echo $baseUrl; ?>style.css?v=2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
@import url('<?php echo $baseUrl; ?>css/product.css?v=2');

/* Notification Icon Styles */
.notification-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    background: #f1f5f9;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    margin-right: 15px;
}

.notification-icon:hover {
    background: #e2e8f0;
    transform: scale(1.05);
}

.notification-icon i {
    font-size: 20px;
    color: #2f6f56;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
}

.notification-badge:empty {
    display: none;
}

.top-buttons {
    display: flex;
    align-items: center;
}

/* Hamburger Menu Styles */
.hamburger {
    display: none;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    padding: 10px;
    z-index: 1001;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 20px;
    left: 20px;
}

.hamburger span {
    width: 25px;
    height: 3px;
    background: #2f6f56;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(8px, 8px);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -7px);
}

.sidebar {
    transition: transform 0.3s ease;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .hamburger {
        display: flex;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 1000;
        transform: translateX(-100%);
        overflow-y: auto;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0 !important;
        padding: 20px;
        padding-top: 80px;
    }

    .topbar {
        flex-wrap: wrap;
        padding: 15px;
    }

    .search-box {
        order: 2;
        max-width: 100%;
        margin-top: 15px;
        width: 100%;
    }

    .top-buttons {
        order: 1;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
    }

    .notification-icon i {
        font-size: 18px;
    }

    .notification-badge {
        font-size: 10px;
        min-width: 18px;
        height: 18px;
    }

    .product-grid {
        grid-template-columns: 1fr !important;
        gap: 20px;
    }

    .product-card {
        max-width: 100%;
    }

    .back-button {
        margin-top: 10px;
        margin-bottom: 20px;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 15px;
        padding-top: 70px;
    }

    .topbar {
        padding: 10px;
    }

    .product-section h2 {
        font-size: 1.5rem;
    }

    .product-card h3 {
        font-size: 1.2rem;
    }

    .product-card p {
        font-size: 0.9rem;
    }

    .buynow {
        padding: 10px 20px;
        font-size: 14px;
    }
}


</style>
</head>
<body>

<!-- Hamburger Menu -->
<div class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
</div>

<div class="sidebar" id="sidebar">
	<div class="logo">
		<img src="<?php echo $baseUrl; ?>image/logo.png" alt="Printease Logo">
		<h2>PRINTEASE</h2>
	</div>

	<ul class="menu">
		<li><a href="<?php echo $baseUrl; ?>index.php"><i class="fa-solid fa-table-cells icon"></i> <span class="label">Dashboard</span></a></li>
		<li><a href="<?php echo $componentPath; ?>product.php" class="active"><i class="fa-solid fa-box icon"></i> <span class="label">Product</span></a></li>
		<li><a href="<?php echo $componentPath; ?>order.php"><i class="fa-solid fa-credit-card icon"></i> <span class="label">Orders</span></a></li>
		<li><a href="<?php echo $componentPath; ?>notifications.php"><i class="fa-solid fa-bell icon"></i> <span class="label">Notifications</span></a></li>
		            <li><a href="../components/message_customer.php"><i class="fa-solid fa-message icon"></i> <span class="label">Message</span></a></li>

		<li><a href="<?php echo $componentPath; ?>profile.php"><i class="fa-solid fa-user icon"></i> <span class="label">Profile</span></a></li>
	</ul>

	<div class="logout">
		<a href="logout.php" id="sidebarLogoutTrigger">Logout</a>
	</div>
</div>

<div class="main-content">
	<div class="topbar">
		<div class="search-box">
			<i class="fa-solid fa-magnifying-glass"></i>
			<input type="text" id="searchInput" placeholder="Search orders or products..." autocomplete="off">
			
			<div class="search-dropdown" id="searchDropdown">
				
				<div id="searchHistoryList">
					</div>
				
				<div class="dropdown-section-title">Popular Suggestions</div>
				<a class="dropdown-item suggestion-item" data-query="Sticker Order" href="#">
					<i class="fa-solid fa-fire"></i> Sticker Order
				</a>
				<a class="dropdown-item suggestion-item" data-query="Recent Order Status" href="#">
					<i class="fa-solid fa-truck"></i> Recent Order Status
				</a>
				<a class="dropdown-item suggestion-item" data-query="Laminate Pricing" href="#">
					<i class="fa-solid fa-tags"></i> Laminate Pricing
				</a>
			</div>
		</div>

		<div class="top-buttons">
			<a href="<?php echo $componentPath; ?>notifications.php" class="notification-icon" id="notificationIcon" onclick="markNotificationsAsRead()">
				<i class="fa-solid fa-bell"></i>
				<span class="notification-badge" id="notificationBadge"><?php echo $notificationCount > 0 ? $notificationCount : ''; ?></span>
			</a>
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

	<section class="product-section">
        
        <a href="javascript:history.back()" class="back-button">
        <i class="fa-solid fa-arrow-left"></i> Back
        </a>

        <h2></h2>
        <div class="product-grid">

        <?php foreach ($productCatalog as $product): ?>
            <div class="product-card">
                <img src="<?php echo $baseUrl; ?>image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?> Service">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>Starting: ₱<?php echo number_format($product['price_start'], 2); ?></p>
                <a class="buynow" href="<?php echo $componentPath . htmlspecialchars($product['link']); ?>">
                    Print
                </a>
            </div>
        <?php endforeach; ?>

        </div>
    </section>
</div>

<div class="modal-overlay" id="logoutModal">
	<div class="logout-modal-content">
		<i class="fa-solid fa-door-open logout-icon"></i>
		<h2>Are you sure you want to log out?</h2>
		<div class="modal-actions">
			<a href="logout.php" class="btn-yes-logout">Yes, Log Out</a>
			<button class="btn-no-cancel" id="cancelLogout">No, Stay</button>
		</div>
	</div>
</div>

<script>
	// Inject PHP variable into JS scope for correct pathing
    const BASE_URL = '<?php echo $baseUrl; ?>'; 
    const COMPONENT_PATH = '<?php echo $componentPath; ?>'; 
	
	document.addEventListener('DOMContentLoaded', function() {
		// Hamburger Menu Toggle
		const hamburger = document.getElementById('hamburger');
		const sidebar = document.getElementById('sidebar');

		if (hamburger && sidebar) {
			hamburger.addEventListener('click', function() {
				hamburger.classList.toggle('active');
				sidebar.classList.toggle('active');
			});

			// Close sidebar when clicking outside
			document.addEventListener('click', function(e) {
				if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('active')) {
					hamburger.classList.remove('active');
					sidebar.classList.remove('active');
				}
			});

			// Close sidebar when clicking a menu item on mobile
			const menuItems = document.querySelectorAll('.sidebar .menu a');
			menuItems.forEach(item => {
				item.addEventListener('click', function() {
					if (window.innerWidth <= 768) {
						hamburger.classList.remove('active');
						sidebar.classList.remove('active');
					}
				});
			});
		}

		const modal = document.getElementById('logoutModal');
		// Get all elements with an href containing "logout=1"
		const logoutTriggers = document.querySelectorAll('a[href*="logout=1"]');
		const closeModalButton = document.getElementById('cancelLogout');
		
		// --- Logout Modal Logic (Copied from index.php) ---

		// 1. Prevent default logout action and open modal
		logoutTriggers.forEach(trigger => {
			if (!trigger.classList.contains('btn-yes-logout')) {
				trigger.addEventListener('click', function(e) {
					e.preventDefault();
					modal.style.display = 'flex';
				});
			}
		});

		// 2. Close modal handler (Cancel button)
		closeModalButton.addEventListener('click', function() {
			modal.style.display = 'none';
		});

		// 3. Close modal handler (Clicking outside the box)
		modal.addEventListener('click', function(e) {
			if (e.target === modal) {
				modal.style.display = 'none';
			}
		});

		// --- Search Bar Logic (Suggestions and History) (Copied from index.php) ---

		const searchInput = document.getElementById('searchInput');
		const searchDropdown = document.getElementById('searchDropdown');
		const searchHistoryList = document.getElementById('searchHistoryList');
		const MAX_HISTORY = 3;
		const HISTORY_KEY = 'printease_search_history';

		// Function to load and render history
		function loadHistory() {
			const history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
			searchHistoryList.innerHTML = ''; // Clear existing list

			if (history.length > 0) {
				const title = document.createElement('div');
				title.className = 'dropdown-section-title';
				title.textContent = 'Recent Searches (Max ' + MAX_HISTORY + ')';
				searchHistoryList.appendChild(title);

				history.forEach(query => {
					const item = document.createElement('a');
					item.className = 'dropdown-item history';
					item.href = '#';
					item.setAttribute('data-query', query);
					item.innerHTML = `<i class="fa-solid fa-history"></i> ${query}`;
					
					// Re-uses the search function when clicked
					item.addEventListener('click', function(e) {
						e.preventDefault();
						searchInput.value = query;
						handleSearch(query);
					});
					searchHistoryList.appendChild(item);
				});
			}
		}

		// Function to save a query to history
		function saveToHistory(query) {
			query = query.trim();
			if (!query) return; // Don't save empty queries

			let history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
			
			// 1. Remove query if it already exists (to move it to the top)
			history = history.filter(item => item.toLowerCase() !== query.toLowerCase());

			// 2. Add the new query to the front
			history.unshift(query);

			// 3. Keep only the latest MAX_HISTORY items
			if (history.length > MAX_HISTORY) {
				history = history.slice(0, MAX_HISTORY);
			}

			localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
			loadHistory(); // Reload dropdown content
		}

		// Function to handle the actual search (Redirect to order page with search query)
		function handleSearch(query) {
			query = query.trim();
			if (!query) return;

			saveToHistory(query);
			searchDropdown.style.display = 'none';
			
			// Redirects to order page for searching
			window.location.href = `${COMPONENT_PATH}order.php?search=${encodeURIComponent(query)}`;
		}

		// Show dropdown on input focus
		searchInput.addEventListener('focus', function() {
			loadHistory(); // Load history every time before showing
			searchDropdown.style.display = 'block';
		});

		// Hide dropdown when clicking outside
		document.addEventListener('click', function(e) {
			const searchBox = document.querySelector('.search-box');
			if (!searchBox.contains(e.target)) {
				searchDropdown.style.display = 'none';
			}
		});

		// Handle Enter keypress in search input
		searchInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				handleSearch(searchInput.value);
			}
		});

		// Handle clicks on suggestion items
		document.querySelectorAll('.suggestion-item').forEach(item => {
			item.addEventListener('click', function(e) {
				e.preventDefault();
				const query = item.getAttribute('data-query');
				searchInput.value = query;
				handleSearch(query);
			});
		});

		// Initial load of history
		loadHistory();

		// Auto-update notification count every 30 seconds
		function updateNotificationCount() {
			fetch(`${COMPONENT_PATH}get_notification_count.php`)
				.then(response => response.json())
				.then(data => {
					const badge = document.getElementById('notificationBadge');
					if (badge) {
						if (data.count > 0) {
							badge.textContent = data.count;
							badge.style.display = 'flex';
						} else {
							badge.textContent = '';
							badge.style.display = 'none';
						}
					}
				})
				.catch(error => console.error('Error fetching notification count:', error));
		}

		// Update every 30 seconds
		setInterval(updateNotificationCount, 30000);

		// Function to mark notifications as read when user clicks notification icon
		window.markNotificationsAsRead = function() {
			fetch(`${COMPONENT_PATH}get_notification_count.php?mark_read=1`)
				.then(response => response.json())
				.then(data => {
					const badge = document.getElementById('notificationBadge');
					if (badge) {
						badge.textContent = '';
						badge.style.display = 'none';
					}
				})
				.catch(error => console.error('Error marking notifications as read:', error));
		};
	});
</script>

</body>
</html>