<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to the main index page. Since the index.php checks for 
// session, it will automatically redirect the user to login.php.
// This is a cleaner approach than directly redirecting to login.php.
header("Location: ../index.php");
exit();
?>