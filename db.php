<?php
$servername = "localhost";
$username = "root";   // default XAMPP/WAMP username
$password = "";       // default no password
$dbname = "printeasee";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
