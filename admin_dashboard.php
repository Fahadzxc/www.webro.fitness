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

// Calculate the start and end dates for the current day
$start_of_day = $current_date->format('Y-m-d 00:00:00');
$end_of_day = $current_date->format('Y-m-d 23:59:59');

// Fetch attendance data for the current day
$attendance_result = $conn->query("
    SELECT status, COUNT(*) as count
    FROM attendance
    WHERE attendance_date BETWEEN '$start_of_day' AND '$end_of_day'
    GROUP BY status
");

$attendance_overview = ['Present' => 0, 'Absent' => 0];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_overview[$row['status']] = $row['count'];
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .card-box {
      border-radius: 1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .dashboard-title {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .sidebar {
      min-height: 100vh;
      background-color: #343a40;
      color: white;
      padding: 1rem;
    }
    .sidebar a {
      color: white;
      display: block;
      padding: 0.5rem 0;
      text-decoration: none;
      border-radius: 0.5rem;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #495057;
      transform: scale(1.05);
    }
    .sidebar a.active {
      background-color: #495057;
      color: #fff;
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
    <div class="col-md-10 py-4">
      <div class="row mb-4">
        <div class="col-md-8">
          <div class="card card-box">
            <div class="card-header bg-primary text-white">Payment Overview (This Month)</div>
            <div class="card-body">
              <ul class="list-group">
                <li class="list-group-item">Schedule: <strong><?php echo date('F d, Y'); ?></strong></li>
                <li class="list-group-item">Paid: <strong><?php echo $paid; ?></strong></li>
                <li class="list-group-item">Overdue: <strong><?php echo $overdue; ?></strong></li>
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
          <div class="card card-box mt-4">
            <div class="card-header bg-danger text-white">Subscriptions</div>
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
                <button type="submit" name="add_subscription" class="btn btn-primary">Add Subscription</button>
              </form>
              <?php if ($subscriptions_result->num_rows > 0): ?>
                <table class="table mt-4">
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
                            <button type="submit" name="delete_subscription" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this subscription?');">Delete</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p>No active subscriptions.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-box mb-4">
            <div class="card-header bg-success text-white">Attendance Overview (Today)</div>
            <div class="card-body">
              <p>Present: <strong><?php echo $attendance_overview['Present']; ?></strong></p>
              <p>Absent: <strong><?php echo $attendance_overview['Absent']; ?></strong></p>
            </div>
          </div>
          <div class="card card-box mb-4">
            <div class="card-header bg-success text-white">Attendance Overview (Last 7 Days)</div>
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

          <!-- Recent Attendance -->
          <div class="card card-box mt-4">
            <div class="card-header bg-warning text-white">Recent Attendance</div>
            <div class="card-body">
              <?php if ($recent_attendance_result->num_rows > 0): ?>
                <table class="table">
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
                          <?php echo htmlspecialchars($row['status']); ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p>No recent attendance records.</p>
              <?php endif; ?>
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
      labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
      datasets: [{
        label: 'Attendance Percentage',
        data: [<?php echo implode(', ', $attendance_data); ?>],
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
          max: 100,
          ticks: {
            display: false
          }
        }
      }
    }
  });
</script>

</body>
</html>
