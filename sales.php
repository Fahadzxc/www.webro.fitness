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

// Get the current date, week, and month
$current_date = date("Y-m-d");
$current_week = date("Y-W");  // ISO-8601 week number
$current_month = date("Y-m");

// Handle Add Sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sale'])) {
    $item = $_POST['item'];
    $amount = $_POST['amount'];

    // Insert the sale into the database
    $stmt = $conn->prepare("INSERT INTO sales (item, amount, sale_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $item, $amount, $current_date);
    $stmt->execute();
    $stmt->close();

    header("Location: sales.php");
    exit();
}

// Handle Delete Sale
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: sales.php");
    exit();
}

// Get sales for today, week, and month

// Sales Today - Reset happens at midnight, so just check today's sales
$daily_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;

// Sales This Week - This resets every 7 days
$weekly_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'] ?? 0;

// Sales This Month - This resets at the start of each new month
$monthly_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Fetch All Sales History
$sales = $conn->query("SELECT * FROM sales ORDER BY sale_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="col-md-10 mt-4">
            <h2>Sales Management</h2>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Sales Today</h5>
                            <h3>₱<?php echo number_format($daily_sales, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>Sales This Week</h5>
                            <h3>₱<?php echo number_format($weekly_sales, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Sales This Month</h5>
                            <h3>₱<?php echo number_format($monthly_sales, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Sale Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Add Sale</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Item</label>
                            <input type="text" name="item" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" class="form-control" required>
                        </div>
                        <button type="submit" name="add_sale" class="btn btn-success">Add Sale</button>
                    </form>
                </div>
            </div>

            <!-- Sales History Table -->
            <div class="card">
                <div class="card-header bg-secondary text-white">Sales History</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $sales->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['item']); ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo $row['sale_date']; ?></td>
                                    <td>
                                        <a href="sales.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function logoutConfirmation() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to logout?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Logout!',
            cancelButtonText: 'No, Stay Logged In'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php'; // Make sure to create the logout.php
            }
        });
    }
</script>
</body>
</html>
