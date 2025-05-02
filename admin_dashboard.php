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

// Function to mark users as absent if they haven't logged in within 24 hours
function markAbsentUsers($conn) {
    $current_date = new DateTime();
    $current_date->setTime(0, 0, 0);
    $yesterday = clone $current_date;
    $yesterday->modify('-1 day');
    $yesterday_date = $yesterday->format('Y-m-d');

    // Get all users who did not log in yesterday
    $result = $conn->query("
        SELECT id
        FROM users
        WHERE id NOT IN (
            SELECT member_id
            FROM attendance
            WHERE attendance_date = '$yesterday_date'
        )
    ");

    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        // Insert absent record for the user
        $conn->query("INSERT INTO attendance (member_id, status, attendance_date) VALUES ($user_id, 'Absent', '$yesterday_date') ON DUPLICATE KEY UPDATE status='Absent'");
    }
}

// Check if the current date and time is past midnight on April 14, 2025
$current_datetime = new DateTime();
$target_datetime = new DateTime('2025-04-14 00:00:00');
if ($current_datetime >= $target_datetime) {
    markAbsentUsers($conn);
}

// Fetch Dashboard Stats from membership_db
$members_result = $conn->query("SELECT COUNT(*) as total FROM members");
$paid_result = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE status='Paid' AND MONTH(due_date) = MONTH(CURRENT_DATE())");
$overdue_result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE due_date < CURDATE() AND status != 'Paid'");
$new_result = $conn->query("SELECT COUNT(*) as total FROM members WHERE MONTH(joined_date) = MONTH(CURRENT_DATE())");
$cancelled_result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status='Cancelled' AND MONTH(updated_at) = MONTH(CURRENT_DATE())");

$members = $members_result->fetch_assoc()['total'] ?? 0;
$paid = $paid_result->fetch_assoc()['total'] ?? 0;
$overdue = $overdue_result->fetch_assoc()['total'] ?? 0;
$new = $new_result->fetch_assoc()['total'] ?? 0;
$cancelled = $cancelled_result->fetch_assoc()['total'] ?? 0;

// Calculate the start and end dates for each day
$current_date = new DateTime();
$days = [];

for ($i = 0; $i < 7; $i++) {
    $day = clone $current_date;
    $day->modify("-$i days")->setTime(0, 0, 0);
    $days[] = [
        'start' => $day->format('Y-m-d H:i:s'),
        'end' => $day->modify('+23 hours 59 minutes 59 seconds')->format('Y-m-d H:i:s')
    ];
}

// Fetch attendance data for each day
$attendance_data = [];
foreach ($days as $index => $day) {
    $attendance_result = $conn->query("
        SELECT COUNT(DISTINCT member_id) as unique_logins
        FROM attendance
        WHERE attendance_date BETWEEN '{$day['start']}' AND '{$day['end']}'
    ");
    $unique_logins = $attendance_result->fetch_assoc()['unique_logins'] ?? 0;
    $attendance_percentage = min($unique_logins * 5, 100); // Each unique login is 5%, capped at 100%
    $attendance_data[] = $attendance_percentage;
}

$member_list = $conn->query("SELECT name FROM members ORDER BY name ASC LIMIT 10");

// Fetch recent attendance records, excluding admin users
$recent_attendance_result = $conn->query("
    SELECT u.firstName, u.lastName, a.attendance_date, a.status
    FROM attendance a
    JOIN users u ON a.member_id = u.id
    WHERE u.role != 'admin'
    ORDER BY a.attendance_date DESC
    LIMIT 10
");

// Fetch subscription data for all members
$subscriptions_result = $conn->query("
    SELECT u.firstName, u.lastName, s.start_date, s.end_date, s.member_id, s.id as subscription_id, s.amount
    FROM subscriptions s
    JOIN users u ON s.member_id = u.id
    ORDER BY s.end_date DESC
");

// Handle Subscription Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subscription'])) {
    $member_id = $_POST['member_id'];

    // Check if the selected member is not an admin
    $check_admin = $conn->query("SELECT role FROM users WHERE id = $member_id");
    $user_role = $check_admin->fetch_assoc()['role'];

    if ($user_role != 'admin') {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 month'));

        $conn->query("INSERT INTO subscriptions (member_id, start_date, end_date) VALUES ($member_id, '$start_date', '$end_date')");
    }

    header("Location: admin_dashboard.php");
    exit();
}

// Handle Subscription Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_subscription'])) {
    $subscription_id = $_POST['subscription_id'];
    $conn->query("DELETE FROM subscriptions WHERE id = $subscription_id");
    header("Location: admin_dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
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
    
    .stat-box {
      text-align: center;
      padding: 1.5rem;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .stat-box:hover {
      background-color: rgba(0,0,0,0.03);
      border-radius: 0.5rem;
    }
    
    .stat-box .number {
      font-size: 2rem;
      font-weight: 700;
      margin: 0.5rem 0;
    }
    
    .stat-box .label {
      font-weight: 500;
      color: #6c757d;
    }
    
    .bg-primary {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    
    .bg-info {
      background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    }
    
    .bg-danger {
      background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%) !important;
    }
    
    .bg-success {
      background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
    }
    
    .bg-warning {
      background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%) !important;
    }
    
    .bg-secondary {
      background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
    }
    
    table {
      border-collapse: separate;
      border-spacing: 0;
    }
    
    table th {
      background-color: rgba(0,0,0,0.03);
      font-weight: 600;
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
    
    .btn-primary {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      border: none;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
      border: none;
    }
    
    .list-group-item {
      border-left: none;
      border-right: none;
      padding: 1rem 1.25rem;
    }
    
    .list-group-item:first-child {
      border-top: none;
    }
    
    .list-group-item:last-child {
      border-bottom: none;
    }
    
    .text-success {
      color: #28a745 !important;
    }
    
    .text-danger {
      color: #dc3545 !important;
    }
    
    /* Add styles for clickable stat boxes */
    .clickable-stat {
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .clickable-stat:hover:before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(255,255,255,0.1);
      border-radius: 5px;
    }
    
    .clickable-stat:hover .number,
    .clickable-stat:hover .label {
      color: #0056b3;
    }
    
    .clickable-stat:after {
      content: "\f107";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      position: absolute;
      bottom: 5px;
      right: 10px;
      opacity: 0;
      transition: all 0.3s ease;
    }
    
    .clickable-stat:hover:after {
      opacity: 1;
      transform: translateY(2px);
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
      <a href="members.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Members
      </a>
      <a href="billing.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'active' : ''; ?>">
        <i class="fas fa-credit-card"></i> Billing
      </a>
      <a href="sales.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Sales
      </a>
      <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="logoutConfirmation()" class="sidebar-link logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    <div class="col-md-10 content-area">
      <div class="row mb-4">
        <div class="col-md-8">
          <div class="card card-box">
            <div class="card-header bg-primary text-white">
              <i class="fas fa-money-bill-wave me-2"></i> Payment Overview (This Month)
            </div>
            <div class="card-body">
              <ul class="list-group">
                <li class="list-group-item">Schedule: <strong><?php echo date('F d, Y'); ?></strong></li>
                <li class="list-group-item">Paid: <strong><?php echo $paid; ?></strong></li>
                <li class="list-group-item">Overdue: <strong><?php echo $overdue; ?></strong></li>
              </ul>
            </div>
          </div>
          <div class="card card-box mt-4">
            <div class="card-header bg-info text-white">
              <i class="fas fa-chart-pie me-2"></i> Statistics
            </div>
            <div class="card-body">
              <div class="row text-center">
                <!-- Make the stat boxes clickable -->
                <div class="col-6 col-md-3 mb-3 stat-box clickable-stat" onclick="window.location.href='members_crud.php'">
                  <i class="fas fa-users fa-2x text-primary"></i>
                  <div class="number"><?php echo $members; ?></div>
                  <div class="label">Current Members</div>
                </div>
                <div class="col-6 col-md-3 mb-3 stat-box clickable-stat" onclick="window.location.href='paid_members_crud.php'">
                  <i class="fas fa-check-circle fa-2x text-success"></i>
                  <div class="number"><?php echo $paid; ?></div>
                  <div class="label">Paid This Month</div>
                </div>
                <div class="col-6 col-md-3 mb-3 stat-box clickable-stat" onclick="window.location.href='new_members_crud.php'">
                  <i class="fas fa-user-plus fa-2x text-info"></i>
                  <div class="number"><?php echo $new; ?></div>
                  <div class="label">New Members</div>
                </div>
                <div class="col-6 col-md-3 mb-3 stat-box">
                  <i class="fas fa-user-minus fa-2x text-danger"></i>
                  <div class="number"><?php echo $cancelled; ?></div>
                  <div class="label">Cancelled</div>
                </div>
              </div>
            </div>
          </div>
          <div class="card card-box mt-4">
            <div class="card-header bg-danger text-white">
              <i class="fas fa-clipboard-list me-2"></i> Subscriptions
            </div>
            <div class="card-body">
              <form method="POST">
                <div class="mb-3">
                  <label for="member_id" class="form-label">Member</label>
                  <select class="form-select" id="member_id" name="member_id" required>
                    <?php
                    // Modified query to exclude admin users
                    $members_list = $conn->query("SELECT id, firstName, lastName FROM users WHERE role != 'admin'");
                    while ($member = $members_list->fetch_assoc()): ?>
                      <option value="<?php echo $member['id']; ?>">
                        <?php echo htmlspecialchars($member['firstName'] . ' ' . $member['lastName']); ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <button type="submit" name="add_subscription" class="btn btn-primary">
                  <i class="fas fa-plus me-1"></i> Add Subscription
                </button>
              </form>
              <?php if ($subscriptions_result->num_rows > 0): ?>
                <div class="table-responsive mt-4">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Amount Paid</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $subscriptions_result->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                          <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                          <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                          <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="subscription_id" value="<?php echo $row['subscription_id']; ?>">
                              <button type="submit" name="delete_subscription" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this subscription?');">
                                <i class="fas fa-trash me-1"></i> Delete
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info mt-3">
                  <i class="fas fa-info-circle me-2"></i> No active subscriptions.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-box mb-4">
            <div class="card-header bg-success text-white">
              <i class="fas fa-chart-bar me-2"></i> Attendance Overview (Last 7 Days)
            </div>
            <div class="card-body">
              <canvas id="attendanceChart"></canvas>
            </div>
          </div>
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">
              <i class="fas fa-list me-2"></i> Members List
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
              <ul class="list-group">
                <?php while ($row = $member_list->fetch_assoc()): ?>
                  <li class="list-group-item">
                    <i class="fas fa-user me-2 text-secondary"></i>
                    <?php echo htmlspecialchars($row['name']); ?>
                  </li>
                <?php endwhile; ?>
              </ul>
            </div>
          </div>

          <!-- Recent Attendance -->
          <div class="card card-box mt-4">
            <div class="card-header bg-warning text-white">
              <i class="fas fa-history me-2"></i> Recent Attendance
            </div>
            <div class="card-body">
              <?php if ($recent_attendance_result->num_rows > 0): ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $recent_attendance_result->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                          <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                          <td class="<?php echo $row['status'] == 'Present' ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas <?php echo $row['status'] == 'Present' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                            <?php echo htmlspecialchars($row['status']); ?>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="fas fa-info-circle me-2"></i> No recent attendance records.
                </div>
              <?php endif; ?>
            </div>
          </div>
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

  const ctx = document.getElementById('attendanceChart').getContext('2d');
  const attendanceChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
      datasets: [{
        label: 'Attendance Percentage',
        data: [<?php echo implode(', ', $attendance_data); ?>],
        backgroundColor: '#28a745',
        borderColor: '#218838',
        borderWidth: 1,
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { 
          mode: 'index', 
          intersect: false,
          backgroundColor: 'rgba(0,0,0,0.7)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#fff',
          borderWidth: 1
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: function(value) {
              return value + '%';
            }
          },
          grid: {
            color: 'rgba(0,0,0,0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      animation: {
        duration: 2000,
        easing: 'easeOutQuart'
      }
    }
  });
</script>

</body>
</html>