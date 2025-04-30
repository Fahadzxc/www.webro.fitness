<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT firstName, lastName FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc() ?? ['firstName' => '', 'lastName' => '']; // Avoid null errors

// Success message (empty by default)
$successMessage = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("INSERT INTO members (name, email, phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $phone);
    $stmt->execute();
    $stmt->close();

    // Set success message with only email
    $successMessage = "Welcome! Your email (" . htmlspecialchars($email) . ") has been successfully added.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-box { border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dashboard-title { font-size: 1.5rem; font-weight: bold; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding: 1rem; }
        .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; border-radius: 0.5rem; transition: background-color 0.3s ease, transform 0.3s ease; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; transform: scale(1.05); }
        .sidebar a.active { background-color: #495057; color: #fff; }
        /* Centered success message at the bottom */
        .success-message {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            background-color: #4a4a4a; /* Dark gray background */
            color: white;
            border-radius: 10px;
            font-size: 16px;
            display: none;
        }
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
                <h2>Add Member</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Message Popup -->
<div id="successMessage" class="success-message">
    <?php echo $successMessage; ?>
</div>

<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Show success message popup if the success message is set
    <?php if (!empty($successMessage)): ?>
        document.getElementById("successMessage").style.display = "block";
        setTimeout(function() {
            document.getElementById("successMessage").style.display = "none";
        }, 5000);  // Hide after 5 seconds
    <?php endif; ?>

    // Logout confirmation
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
