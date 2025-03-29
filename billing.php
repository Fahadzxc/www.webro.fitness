<?php
include 'db.php';

// Handle Create Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $status = 'Paid';
    
    $stmt = $conn->prepare("INSERT INTO invoices (member_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $member_id, $amount, $due_date, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: billing.php");
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
        .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; }
        .sidebar a:hover { background-color: #495057; border-radius: 0.5rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?> 
        <!-- Main Content -->
        <div class="col-md-10 mt-4">
            <div class="container">
                <h2>Billing Management</h2>

                <!-- Create Invoice Form -->
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

                <!-- View Invoices -->
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
</body>
</html>
