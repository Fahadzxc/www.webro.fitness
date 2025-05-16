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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card-box {
      border-radius: 1rem;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    
    .card-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 25px rgba(0,0,0,0.15);
    }
    
    .card-header {
      font-weight: 600;
      letter-spacing: 0.5px;
      padding: 1rem 1.5rem;
      border-bottom: none;
    }
    
    .dashboard-title {
      font-size: 1.5rem;
      font-weight: bold;
      color: #2c3e50;
    }
    
    .sidebar {
      min-height: 100vh;
      background: linear-gradient(180deg, #1a237e 0%, #283593 50%, #303f9f 100%);
      color: white;
      padding: 1.5rem 1rem;
      box-shadow: 5px 0 15px rgba(0,0,0,0.1);
      z-index: 1000;
    }
    
    .sidebar-header {
      padding-bottom: 1.5rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-header h4 {
      font-weight: 700;
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      color: #fff;
    }
    
    .sidebar a {
      color: rgba(255,255,255,0.85);
      display: block;
      padding: 0.8rem 1rem;
      text-decoration: none;
      border-radius: 0.5rem;
      margin-bottom: 0.5rem;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .sidebar a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    .sidebar a:hover, .sidebar a.active {
      background-color: rgba(255,255,255,0.15);
      color: #fff;
      transform: translateX(5px);
    }
    
    .sidebar a.active {
      background-color: rgba(255,255,255,0.2);
      color: #fff;
      box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    
    .logout-btn {
      margin-top: 2rem;
      background-color: rgba(220, 53, 69, 0.6);
      color: white;
      transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
      background-color: rgba(220, 53, 69, 0.9);
    }
    
    .content-area {
      padding: 2rem;
    }
    
    .btn {
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      border: none;
    }
    
    .btn-warning {
      background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);
      border: none;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
      border: none;
    }
    
    .form-control {
      border-radius: 0.5rem;
      padding: 0.75rem 1rem;
      border: 1px solid #ced4da;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .welcome-text {
      color: rgba(255,255,255,0.9);
      font-weight: 500;
      margin-bottom: 1.5rem;
    }
    
    .user-name {
      font-weight: 700;
    }
    
    .settings-section {
      background-color: white;
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .settings-section h3 {
      margin-bottom: 1.5rem;
      color: #2c3e50;
      font-weight: 600;
    }
    
    .settings-divider {
      margin: 2rem 0;
      border-top: 1px solid #eee;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <div class="sidebar-header">
        <h4>Admin Panel</h4>
        <p class="welcome-text">Welcome, <span class="user-name"><?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?></span>!</p>
      </div>
      <a href="admin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
      <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="logoutConfirmation()" class="sidebar-link logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    <div class="col-md-10 content-area">
      <div class="dashboard-title mb-4">
        <i class="fas fa-cog me-2"></i> Settings
      </div>
      
      <!-- Profile Settings -->
      <div class="settings-section">
        <h3><i class="fas fa-user-circle me-2"></i> Profile Settings</h3>
        
        <form method="post">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?>" required>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" name="update_profile" class="btn btn-primary">
              <i class="fas fa-save me-2"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
      
      <!-- Password Settings -->
      <div class="settings-section">
        <h3><i class="fas fa-lock me-2"></i> Security Settings</h3>
        
        <form method="post">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Current Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" name="current_password" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="new_password" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" name="change_password" class="btn btn-warning">
              <i class="fas fa-key me-2"></i> Change Password
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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