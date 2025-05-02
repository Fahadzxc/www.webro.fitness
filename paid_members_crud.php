<?php
session_start();
include 'connect.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT firstName, lastName FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();

// Handle Delete
if (isset($_GET['delete'])) {
    $invoice_id = $_GET['delete'];
    $delete_query = "DELETE FROM invoices WHERE id = $invoice_id";

    if ($conn->query($delete_query)) {
        $_SESSION['message'] = "Invoice deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting invoice: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: paid_members_crud.php");
    exit();
}

// Handle Mark as Unpaid
if (isset($_GET['unpaid'])) {
    $invoice_id = $_GET['unpaid'];
    $update_query = "UPDATE invoices SET status = 'Unpaid' WHERE id = $invoice_id";

    if ($conn->query($update_query)) {
        $_SESSION['message'] = "Invoice marked as unpaid";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating invoice: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: paid_members_crud.php");
    exit();
}

// Get all paid invoices for the current month
$current_month = date('m');
$current_year = date('Y');
$query = "
    SELECT i.id, i.invoice_number, i.amount, i.due_date, i.status,
           u.firstName, u.lastName, u.email
    FROM invoices i
    JOIN users u ON i.member_id = u.id
    WHERE i.status = 'Paid'
    AND MONTH(i.due_date) = $current_month
    AND YEAR(i.due_date) = $current_year
    ORDER BY i.due_date DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paid Members - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    .welcome-text {
      color: rgba(255,255,255,0.9);
      font-weight: 500;
      margin-bottom: 1.5rem;
    }
    .user-name {
      font-weight: 700;
    }
    .btn {
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .table-hover tbody tr:hover {
      background-color: rgba(0,0,0,0.02);
      transform: scale(1.005);
      transition: all 0.3s ease;
    }
    .table th {
      background-color: rgba(0,0,0,0.03);
      font-weight: 600;
    }
    .alert {
      border-radius: 0.5rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .page-header {
      margin-bottom: 2rem;
      border-bottom: 1px solid rgba(0,0,0,0.1);
      padding-bottom: 1rem;
    }
    .action-buttons .btn {
      margin-right: 0.5rem;
    }
    .back-btn {
      margin-right: 1rem;
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
      <a href="admin_dashboard.php" class="sidebar-link">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
      <a href="members.php" class="sidebar-link">
        <i class="fas fa-users"></i> Members
      </a>
      <a href="billing.php" class="sidebar-link">
        <i class="fas fa-credit-card"></i> Billing
      </a>
      <a href="sales.php" class="sidebar-link">
        <i class="fas fa-chart-line"></i> Sales
      </a>
      <a href="settings.php" class="sidebar-link">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="logoutConfirmation()" class="sidebar-link logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    <div class="col-md-10 content-area">
      <div class="page-header d-flex justify-content-between align-items-center">
        <div>
          <h1><i class="fas fa-check-circle text-success me-2"></i> Paid Members (<?php echo date('F Y'); ?>)</h1>
          <p class="text-muted">Manage members who have paid for the current month</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-primary back-btn">
          <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>

      <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i> <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
      endif;
      ?>

      <div class="card card-box">
        <div class="card-header bg-success text-white">
          <i class="fas fa-list me-2"></i> Paid Members List
        </div>
        <div class="card-body">
          <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Invoice #</th>
                  <th>Member Name</th>
                  <th>Email</th>
                  <th>Amount</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                  <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                  <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                  <td><span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span></td>
                  <td class="action-buttons">
                    <a href="invoice_details.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="paid_members_crud.php?unpaid=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to mark this invoice as unpaid?');">
                      <i class="fas fa-times-circle me-1"></i> Mark as Unpaid
                    </a>
                    <a href="paid_members_crud.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.');">
                      <i class="fas fa-trash-alt me-1"></i> Delete
                    </a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No paid invoices found for the current month.
          </div>
          <?php endif; ?>
        </div>
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