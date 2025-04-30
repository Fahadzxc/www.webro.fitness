<?php
session_start();
require 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect or handle user not logged in
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT firstName, lastName, email FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc() ?? ['firstName' => '', 'lastName' => '', 'email' => '']; // Avoid null errors

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];

    // Split name into first and last names
    $name_parts = explode(" ", $name, 2);
    $firstName = $name_parts[0];
    $lastName = isset($name_parts[1]) ? $name_parts[1] : '';

    $update_query = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ? WHERE id = ?");
    $update_query->bind_param("sssi", $firstName, $lastName, $email, $user_id);
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
    <style>
        .card-box { border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dashboard-title { font-size: 1.5rem; font-weight: bold; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding: 1rem; }
        .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; border-radius: 0.5rem; transition: background-color 0.3s ease, transform 0.3s ease; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; transform: scale(1.05); }
        .sidebar a.active { background-color: #495057; color: #fff; }
    </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar">
      <h5 class="mb-4">Admin Panel</h5>
      <p>Welcome, <?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?>!</p>
      <a href="admin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
      <a href="members.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">Members</a>
      <a href="billing.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'active' : ''; ?>">Billing</a>
      <a href="sales.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">Sales</a>
      <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">Settings</a>
      <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 mt-5">
        <div class="container">
            <h2>Settings</h2>

            <!-- Profile Update Form -->
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Name:</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
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
        </div>
    </div>
  </div>
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
