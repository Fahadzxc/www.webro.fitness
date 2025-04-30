<?php
session_start();
require __DIR__ . "/vendor/autoload.php";
include 'connect.php'; // For database connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$client = new Google\Client();
$client->setClientId("550534749723-4q982oe8bdn83f0mb4aviqvfhc08tdib.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/www.webro.fitness/user_dashboard.php");
$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetPassword'])) {
    $email = $_POST['email'];
    $token = generateResetToken($conn, $email);
    sendResetEmail($email, $token);
    echo "<script>alert('Password reset link has been sent to your email.');</script>";
}

function generateResetToken($conn, $email) {
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();

    return $token;
}

function sendResetEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fahadalalawi1815@gmail.com'; // SMTP username
        $mail->Password   = 'qgwp tibe fzud qvaw'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@webro.fitness', 'Webro Fitness');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Click the following link to reset your password: <a href='http://localhost/www.webro.fitness/reset_password.php?token=$token'>Reset Password</a>";
        $mail->AltBody = "Click the following link to reset your password: http://localhost/www.webro.fitness/reset_password.php?token=$token";

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="signup" style="display:none;">
        <h1 class="form-title">Register</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" id="fName" placeholder="First Name" required>
                <label for="fName">First Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" id="lName" placeholder="Last Name" required>
                <label for="lName">Last Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <input type="hidden" name="roles" value="user">
            <input type="submit" class="btn" value="Sign Up" name="signUp">
        </form>
        <p class="or">
            ----------or--------
        </p>
        <div class="icons">
            <i class="fab fa-google"></i>
            <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
            <p>Already Have Account?</p>
            <button id="signInButton">Sign In</button>
        </div>
    </div>

    <div class="container" id="signIn">
        <h1 class="form-title">Sign In</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <p class="recover">
                <a href="#" id="recoverPassword">Recover Password</a>
            </p>
            <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
        <p class="or">
            ----------or--------
        </p>
        <div class="icons">
            <a href="<?= $url ?>"> <i class="fab fa-google"></i></a>
            <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
            <p>Don't have an account yet?</p>
            <button id="signUpButton">Sign Up</button>
        </div>
    </div>

    <div class="container" id="recoverPasswordForm" style="display:none;">
        <h1 class="form-title">Recover Password</h1>
        <form method="post" action="">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="recoverEmail" placeholder="Email" required>
                <label for="recoverEmail">Email</label>
            </div>
            <input type="submit" class="btn" value="Send Reset Link" name="resetPassword">
        </form>
        <div class="links">
            <p>Remember your password?</p>
            <button id="backToSignIn">Sign In</button>
        </div>
    </div>

    <script>
        document.getElementById('signInButton').addEventListener('click', function() {
            document.getElementById('signup').style.display = 'none';
            document.getElementById('signIn').style.display = 'block';
            document.getElementById('recoverPasswordForm').style.display = 'none';
        });

        document.getElementById('signUpButton').addEventListener('click', function() {
            document.getElementById('signIn').style.display = 'none';
            document.getElementById('signup').style.display = 'block';
        });

        document.getElementById('recoverPassword').addEventListener('click', function() {
            document.getElementById('signIn').style.display = 'none';
            document.getElementById('recoverPasswordForm').style.display = 'block';
        });

        document.getElementById('backToSignIn').addEventListener('click', function() {
            document.getElementById('recoverPasswordForm').style.display = 'none';
            document.getElementById('signIn').style.display = 'block';
        });
    </script>
</body>
</html>
