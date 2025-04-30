<?php
$host = "localhost";  // Change if using a different server
$user = "root";       // Default XAMPP user (change if necessary)
$pass = "";           // Default XAMPP password (empty by default)
$dbname = "membership_db"; // Your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
