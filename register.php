<?php
include 'connect.php';
session_start();

if (isset($_POST['signUp'])) {
    $username = $_POST['username'];
    $firstName = $_POST['fName'];
    $lastName = $_POST['lName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Secure password hashing
    $role = 'user'; // Default role for new users

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email Address Already Exists!";
    } else {
        // Insert new user into the database with the role
        $insertQuery = "INSERT INTO users (username, firstName, lastName, role, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssss", $username, $firstName, $lastName, $role, $email, $hashedPassword);

        if ($stmt->execute()) {
            // Store user ID and role in session
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['role'] = $role;
            header("Location: user_dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}

if (isset($_POST['signIn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from DB
    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Store user ID and role in session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on role
            if ($row['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            echo "Incorrect Password!";
        }
    } else {
        echo "Email Not Found!";
    }
}
?>
