<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Kunin ang admin info mula sa session
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : "Admin";
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : "No Email";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .profile-icon {
            font-size: 70px;
            color: #007bff;
        }
        .btn-logout {
            background-color: #dc3545;
            color: white;
            border-radius: 5px;
            padding: 10px 20px;
            text-decoration: none;
        }
        .btn-logout:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

    <div class="profile-container">
        <i class="fas fa-user-circle profile-icon"></i>
        <h2 class="mt-3"><?php echo $admin_name; ?></h2>
        <p><i class="fas fa-envelope"></i> <?php echo $admin_email; ?></p>
        <a href="logout.php" class="btn btn-logout mt-3">Logout</a>
    </div>

</body>
</html>