<?php
session_start();
include 'db.php';

// Check if the user is logged in and if user data is available in the session
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit();
}

// Fetch the logged-in user's data from the session or database
$user_result = $conn->query("SELECT firstName, lastName FROM users WHERE id = {$_SESSION['user_id']}");
$user = $user_result->fetch_assoc();

if (!$user) {
    echo "User data not found.";
    exit();
}

// Fetch all members
$result = $conn->query("SELECT * FROM members ORDER BY id DESC");

$successMessage = ''; // Variable to hold success message

// Handle Add Member form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $status = $_POST['status'];
    $joined_date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO members (name, email, phone, status, joined_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $status, $joined_date);
    $stmt->execute();
    $stmt->close();

    $successMessage = "Member added successfully!"; // Set success message
    header("Location: members.php?success=1"); // Redirect to the same page after adding
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <h2>Manage Members</h2>
                <a href="add_member.php" class="btn btn-success mb-3">Add Member</a>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo $row['joined_date']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td>
                                <a href="edit_member.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_member.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Show success pop-up notification after adding a member
    <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: 'Member added successfully!',
            showConfirmButton: false,
            timer: 5000
        });
    <?php endif; ?>
    
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
                window.location.href = "logout.php"; // Redirect to logout page
            }
        });
    }
</script>

</body>
</html>
