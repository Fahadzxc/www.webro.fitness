<?php
session_start();
include 'connect.php'; // For login database

// Check if the user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit();
}

// Fetch logged-in user info from the login database
$user_id = $_SESSION['user_id'];
$stmt = $conn_login->prepare("SELECT firstName, lastName FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    // Construct the username
    $username = ($user['firstName'] ?? 'Unknown') . ' ' . ($user['lastName'] ?? '');
} else {
    $username = 'Unknown User';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-box { border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .dashboard-title { font-size: 1.5rem; font-weight: bold; }
    .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding: 1rem; }
    .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; }
    .sidebar a:hover { background-color: #495057; border-radius: 0.5rem; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <h5 class="mb-4">User Panel</h5>
      <p>Welcome, <?php echo htmlspecialchars($username); ?>!</p>
      <a href="user_profile.php">👤 User Profile</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="settings.php">Settings</a>
      <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a>
    </div>
    <div class="col-md-10 py-4">
      <div class="row mb-4">
        <div class="col-md-8">
          <div class="card card-box">
            <div class="card-header bg-primary text-white">User Information</div>
            <div class="card-body">
              <ul class="list-group">
                <li class="list-group-item">Name: <strong><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></strong></li>
                <li class="list-group-item">Email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></li>
                <li class="list-group-item">Role: <strong><?php echo htmlspecialchars($user['role']); ?></strong></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">Notifications</div>
            <div class="card-body">
              <p>No new notifications.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function logoutConfirmation() {
    Swal.fire({
      title: "Are you sure?",
      text: "You will be logged out of your account.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, log out!",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = "logout.php";
      }
    });
  }
</script>

</body>
</html>
