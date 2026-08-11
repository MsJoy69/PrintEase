<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "printeasee"; // nilagay ko dito kasi andito din nmn yung user table

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
