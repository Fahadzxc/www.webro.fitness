<?php
session_start();
include 'connect.php'; // For login database
include 'db.php'; // For membership database

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch Dashboard Stats from membership_db
$members_result = $conn->query("SELECT COUNT(*) as total FROM members");
$paid_result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND status='Paid'");
$new_result = $conn->query("SELECT COUNT(*) as total FROM members WHERE MONTH(joined_date) = MONTH(CURRENT_DATE())");
$cancelled_result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status='Cancelled' AND MONTH(updated_at) = MONTH(CURRENT_DATE())");

$members = $members_result->fetch_assoc()['total'] ?? 0;
$paid = $paid_result->fetch_assoc()['total'] ?? 0;
$new = $new_result->fetch_assoc()['total'] ?? 0;
$cancelled = $cancelled_result->fetch_assoc()['total'] ?? 0;

$member_list = $conn->query("SELECT name FROM members ORDER BY name ASC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <h5 class="mb-4">Admin Panel</h5>
      <p>Welcome, <?php echo ($username); ?>!</p>
      <a href="admin_dashboard.php">Dashboard</a>
      <a href="members.php">Members</a>
      <a href="billing.php">Billing</a>
      <a href="sales.php">Sales</a>
      <a href="settings.php">Settings</a>
      <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a>
    </div>
    <div class="col-md-10 py-4">
      <div class="row mb-4">
        <div class="col-md-8">
          <div class="card card-box">
            <div class="card-header bg-primary text-white">Payment Overview (This Month)</div>
            <div class="card-body">
              <ul class="list-group">
                <li class="list-group-item">Schedule: <strong><?php echo date('F d, Y'); ?></strong></li>
                <li class="list-group-item">Paid: <strong><?php echo $paid; ?></strong></li>
                <li class="list-group-item">Overdue: <strong><?php echo rand(5, 20); ?></strong></li>
              </ul>
            </div>
          </div>
          <div class="card card-box mt-4">
            <div class="card-header bg-info text-white">Statistics</div>
            <div class="card-body">
              <div class="row text-center">
                <div class="col-6 mb-3">Current Members<br><strong><?php echo $members; ?></strong></div>
                <div class="col-6 mb-3">Paid This Month<br><strong><?php echo $paid; ?></strong></div>
                <div class="col-6 mb-3">New Members<br><strong><?php echo $new; ?></strong></div>
                <div class="col-6 mb-3">Cancelled<br><strong><?php echo $cancelled; ?></strong></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-box mb-4">
            <div class="card-header bg-success text-white">Attendance Overview</div>
            <div class="card-body">
              <canvas id="attendanceChart"></canvas>
            </div>
          </div>
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">Members List</div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
              <ul class="list-group">
                <?php while ($row = $member_list->fetch_assoc()): ?>
                  <li class="list-group-item"><?php echo htmlspecialchars($row['name']); ?></li>
                <?php endwhile; ?>
              </ul>
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

  const ctx = document.getElementById('attendanceChart').getContext('2d');
  const attendanceChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [{
        label: 'Attendance Count',
        data: [45, 60, 55, 70],
        backgroundColor: '#198754'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 10
          }
        }
      }
    }
  });
</script>

</body>
</html>
