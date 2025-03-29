<?php
include 'db.php';

// Handle Add Sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sale'])) {
    $item = $_POST['item'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("INSERT INTO sales (item, amount) VALUES (?, ?)");
    $stmt->bind_param("sd", $item, $amount);
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

// Fetch Sales Reports
$daily_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$weekly_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())")->fetch_assoc()['total'] ?? 0;
$monthly_sales = $conn->query("SELECT SUM(amount) AS total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Fetch All Sales
$sales = $conn->query("SELECT * FROM sales ORDER BY sale_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales Management</title>
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
            <h5 class="mb-4"></h5>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="billing.php">Billing</a>
            
            <a href="sales.php">Sales</a>
            <a href="settings.php">Settings</a> <!-- Settings Link -->
            <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a> <!-- Updated Logout Button -->
    <!-- SweetAlert2 Library -->
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
            window.location.href = "logout.php"; // Redirect to logout page
        }
    });
}
</script>
      
        </div>

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
</body>
</html>
