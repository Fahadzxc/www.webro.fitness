<?php
session_start();
include "db_connection.php"; // Siguraduhin may connection sa database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['firstName'];
            $_SESSION['last_name'] = $user['lastName'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['created_at'] = $user['created_at'];

            header("Location: admin_profile.php");
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found!";
    }
}
?>
