<?php
session_start();
include 'connect.php'; // For database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Debugging: Print the received token
    echo "Received Token: $token<br>";

    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $email = $user_data['email'];
        $expires_at = $user_data['expires_at'];

        // Debugging: Print the expiration time
        echo "Token Expires At: $expires_at<br>";

        if (strtotime($expires_at) > time()) {
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateStmt->bind_param("ss", $newPassword, $email);
            $updateStmt->execute();

            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $deleteStmt->bind_param("s", $email);
            $deleteStmt->execute();

            echo "<script>alert('Password successfully reset.'); window.location.href = 'index.php';</script>";
        } else {
            echo "<script>alert('Token has expired.');</script>";
        }
    } else {
        echo "<script>alert('Invalid or expired token.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f7f7f7;
            font-family: Arial, sans-serif;
        }
        .reset-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .reset-container h1 {
            margin-bottom: 1rem;
            color: #333;
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .input-group i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .btn {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 4px;
            background-color: #007BFF;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1>Reset Password</h1>
        <form method="post" action="">
            <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="New Password" required>
            </div>
            <input type="submit" class="btn" value="Reset Password">
        </form>
    </div>
</body>
</html>
