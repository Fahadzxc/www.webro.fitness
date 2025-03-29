<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "membership_db";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in user info (if user is logged in)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = $conn->query("SELECT fName, lastName FROM admin_users WHERE id = '$user_id'");

    // Check if the query was successful and fetch the result
    if ($user_query) {
        $user = $user_query->fetch_assoc();
        // Use null coalescing operator to handle potential null values
        $username = ($user['fName'] ?? 'Guest') . ' ' . ($user['lastName'] ?? '');
    } else {
        $username = 'Guest';
    }
} else {
    $username = 'Guest';
}
?>
