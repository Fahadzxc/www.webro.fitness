<?php
session_start();
include 'db.php';

// Check if the user is logged in and if user data is available in the session
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit();
}

// Fetch the logged-in user's data from the session or database
$user_result = $conn->query("SELECT firstName, lastName, email FROM users WHERE id = {$_SESSION['user_id']}");
$user = $user_result->fetch_assoc();

if (!$user) {
    echo "User data not found.";
    exit();
}

// Handle Create Invoice
$invoiceCreated = false; // Flag to check if invoice was created

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $status = 'Paid'; // Automatically set status to 'Paid' after creation

    // Insert the invoice into the database
    $stmt = $conn->prepare("INSERT INTO invoices (member_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $member_id, $amount, $due_date, $status);
    $stmt->execute();
    $stmt->close();

    // Set flag to true if invoice is successfully created
    $invoiceCreated = true;

    // Refresh the page after success
    header("Location: billing.php"); // Redirect to the same page
    exit();
}

// Fetch all invoices
$invoices = $conn->query("SELECT LPAD(invoices.id, 6, '0') AS formatted_id, members.name, invoices.amount, invoices.due_date, invoices.status
                          FROM invoices
                          JOIN members ON invoices.member_id = members.id
                          ORDER BY invoices.id DESC");

// Fetch members for dropdown
$members = $conn->query("SELECT id, name FROM members ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-box { border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dashboard-title { font-size: 1.5rem; font-weight: bold; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding: 1rem; }
        .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; border-radius: 0.5rem; transition: background-color 0.3s ease, transform 0.3s ease; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; transform: scale(1.05); }
        .sidebar a.active { background-color: #495057; color: #fff; }
        .welcome-message {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            background-color: #4a4a4a;
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

        <div class="col-md-10 mt-4">
            <div class="container">
                <h2>Billing Management</h2>

                <?php if ($invoiceCreated): ?>
                    <div id="welcomeMessage" class="welcome-message">
                        <?php echo "Successfully created an invoice! Your email (" . htmlspecialchars($user['email']) . ")"; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">Create Invoice</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label>Member</label>
                                <select name="member_id" class="form-control" required>
                                    <option value="">Select Member</option>
                                    <?php while ($row = $members->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['id']; ?>"> <?php echo $row['name']; ?> </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Amount</label>
                                <input type="number" name="amount" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                            <button type="submit" name="create_invoice" class="btn btn-success">Create Invoice</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">Invoices</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $invoices->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo $row['formatted_id']; ?></td>
                                        <td><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['amount']; ?></td>
                                        <td><?php echo $row['due_date']; ?></td>
                                        <td><?php echo $row['status']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($invoiceCreated): ?>
        document.getElementById("welcomeMessage").style.display = "block";
        setTimeout(function() {
            document.getElementById("welcomeMessage").style.display = "none";
        }, 5000);  // Hide after 5 seconds
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
                window.location.href = "logout.php"; 
            }
        });
    }
</script>

</body>
</html>
