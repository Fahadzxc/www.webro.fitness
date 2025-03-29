<?php
session_start();
require 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ;
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc() ?? ['name' => '', 'email' => '']; // Avoid null errors

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $update_query = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $update_query->bind_param("ssi", $name, $email, $user_id);
    $update_query->execute();
    echo "<script>alert('Profile updated successfully!');</script>";
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    // Verify current password
    $password_query = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $password_query->bind_param("i", $user_id);
    $password_query->execute();
    $password_result = $password_query->get_result();
    $user_data = $password_result->fetch_assoc();

    if ($user_data && password_verify($current_password, $user_data['password'])) {
        $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_password->bind_param("si", $new_password, $user_id);
        $update_password->execute();
        echo "<script>alert('Password changed successfully!');</script>";
    } else {
        echo "<script>alert('Current password is incorrect!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert -->
</head>
<body>
<div class="container mt-4">
    <h2>Settings</h2>

    <!-- Profile Update Form -->
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email:</label>
            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
    </form>

    <hr>

    <!-- Password Change Form -->
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Current Password:</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password:</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
    </form>

    <hr>

    <!-- Logout Button -->
    <a href="#" class="btn btn-danger mt-3" onclick="logoutConfirmation();">Logout</a>
</div>

<!-- JavaScript for Logout Confirmation -->
<script>
function logoutConfirmation() {
    Swal.fire({
        title: "Are you sure?",
        text: "You will be logged out of your account.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, log out!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "logout.php"; // Redirect to logout page
        }
    });
}
</script>

</body>
</html>
