<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT firstName, lastName, email FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();

// Function to mark the user as absent if they haven't logged in within 24 hours
function markUserAbsent($conn, $user_id) {
    $current_date = new DateTime();
    $current_date->setTime(0, 0, 0);
    $yesterday = clone $current_date;
    $yesterday->modify('-1 day');
    $yesterday_date = $yesterday->format('Y-m-d');

    // Check if the user has logged in yesterday
    $result = $conn->query("
        SELECT *
        FROM attendance
        WHERE member_id = $user_id AND attendance_date = '$yesterday_date'
    ");

    if ($result->num_rows == 0) {
        // Insert absent record for the user
        $conn->query("INSERT INTO attendance (member_id, status, attendance_date) VALUES ($user_id, 'Absent', '$yesterday_date') ON DUPLICATE KEY UPDATE status='Absent'");
    }
}

// Check if the current date and time is past midnight on April 14, 2025
$current_datetime = new DateTime();
$target_datetime = new DateTime('2025-04-14 00:00:00');
if ($current_datetime >= $target_datetime) {
    markUserAbsent($conn, $user_id);
}

// Record attendance automatically when the user logs in
$attendance_date = date('Y-m-d');
$attendance_check = $conn->query("SELECT * FROM attendance WHERE member_id = $user_id AND attendance_date = '$attendance_date'");

if ($attendance_check->num_rows == 0) {
    $conn->query("INSERT INTO attendance (member_id, status, attendance_date) VALUES ($user_id, 'Present', '$attendance_date') ON DUPLICATE KEY UPDATE status='Present'");
}

// Fetching attendance data for the current day
$current_date = new DateTime();
$start_of_day = $current_date->format('Y-m-d 00:00:00');
$end_of_day = $current_date->format('Y-m-d 23:59:59');

$attendance_result = $conn->query("
    SELECT status, attendance_date
    FROM attendance
    WHERE member_id = $user_id AND attendance_date BETWEEN '$start_of_day' AND '$end_of_day'
    ORDER BY attendance_date DESC
    LIMIT 1
");

$attendance_status = $attendance_result->fetch_assoc()['status'] ?? 'Absent';

// Fetching recent attendance records
$recent_attendance_result = $conn->query("
    SELECT attendance_date, status
    FROM attendance
    WHERE member_id = $user_id
    ORDER BY attendance_date DESC
    LIMIT 10
");

// Fetching subscription data
$subscription = $conn->query("
    SELECT id, start_date, end_date, amount
    FROM subscriptions
    WHERE member_id = $user_id
    ORDER BY end_date DESC
    LIMIT 1
")->fetch_assoc();

// Fetching payment history
$payment_history_result = $conn->query("
    SELECT amount, payment_date
    FROM payments
    WHERE member_id = $user_id
    ORDER BY payment_date DESC
");

// Check if subscription is active
$subscription_active = false;
$days_remaining = 0;
$subscription_expired = false;

if ($subscription) {
    $end_date = new DateTime($subscription['end_date']);
    $today = new DateTime();
    $subscription_active = ($today <= $end_date);

    if ($subscription_active) {
        $interval = $today->diff($end_date);
        $days_remaining = $interval->days;
    } else {
        $subscription_expired = true;
    }
}

// Handle subscription renewal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['renew_subscription'])) {
    $subscription_type = $_POST['subscription_type'] ?? '1month';

    $start_date = date('Y-m-d');

    // Set end date based on subscription type
    switch ($subscription_type) {
        case '3months':
            $end_date = date('Y-m-d', strtotime('+3 months'));
            $amount = 2999;
            break;
        case '6months':
            $end_date = date('Y-m-d', strtotime('+6 months'));
            $amount = 5499;
            break;
        case '12months':
            $end_date = date('Y-m-d', strtotime('+12 months'));
            $amount = 9999;
            break;
        default: // 1 month
            $end_date = date('Y-m-d', strtotime('+1 month'));
            $amount = 1299;
    }

    // Check if the user_id exists in the users table
    $user_check = $conn->query("SELECT id FROM users WHERE id = $user_id");
    if ($user_check->num_rows > 0) {
        // Insert new subscription
        $conn->query("INSERT INTO subscriptions (member_id, start_date, end_date, amount) VALUES ($user_id, '$start_date', '$end_date', $amount)");

        // Insert payment record
        $conn->query("INSERT INTO payments (member_id, amount, payment_date) VALUES ($user_id, $amount, '$start_date')");

        // Refresh the page to show the updated subscription
        header("Location: user_dashboard.php?renewal=success");
        exit();
    } else {
        echo "Error: Invalid user ID.";
    }
}

// Handle subscription cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_subscription'])) {
    $subscription_id = $_POST['subscription_id'];

    // Delete the subscription record
    $conn->query("DELETE FROM subscriptions WHERE id = $subscription_id");

    // Refresh the page to show the updated subscription status
    header("Location: user_dashboard.php?cancellation=success");
    exit();
}

// Count streak (consecutive present days)
$streak_query = $conn->query("
    SELECT COUNT(*) as streak_count
    FROM (
        SELECT
            attendance_date,
            status,
            @rn := IF(@prev_status = status AND status = 'Present' AND DATEDIFF(attendance_date, @prev_date) = 1, @rn + 1, 1) as rn,
            @prev_status := status,
            @prev_date := attendance_date
        FROM
            attendance,
            (SELECT @rn := 0, @prev_status := '', @prev_date := '') as vars
        WHERE
            member_id = $user_id
        ORDER BY
            attendance_date DESC
    ) as t
    WHERE status = 'Present' AND rn = (SELECT MAX(rn) FROM (
        SELECT
            @rn2 := IF(@prev_status2 = status AND status = 'Present' AND DATEDIFF(attendance_date, @prev_date2) = 1, @rn2 + 1, 1) as rn,
            @prev_status2 := status,
            @prev_date2 := attendance_date
        FROM
            attendance,
            (SELECT @rn2 := 0, @prev_status2 := '', @prev_date2 := '') as vars
        WHERE
            member_id = $user_id AND status = 'Present'
        ORDER BY
            attendance_date DESC
    ) as t2)
");

$streak = $streak_query->fetch_assoc()['streak_count'] ?? 0;

// Get attendance percentage for this month
$month_attendance_query = $conn->query("
    SELECT
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
        COUNT(*) as total_count
    FROM
        attendance
    WHERE
        member_id = $user_id AND
        MONTH(attendance_date) = MONTH(CURRENT_DATE()) AND
        YEAR(attendance_date) = YEAR(CURRENT_DATE())
");

$month_stats = $month_attendance_query->fetch_assoc();
$attendance_percentage = ($month_stats['total_count'] > 0) ?
    round(($month_stats['present_count'] / $month_stats['total_count']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .card-box {
      border-radius: 1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
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
    .logout-btn {
      color: white;
      padding: 0.5rem 0;
      display: block;
      text-decoration: none;
      border-radius: 0.5rem;
    }
    .badge-large {
      font-size: 1.2rem;
      padding: 0.5rem 1rem;
    }
    .streak-badge {
      background-color: #ffc107;
      color: #212529;
      border-radius: 0.5rem;
      padding: 0.5rem;
      display: inline-block;
      margin-top: 0.5rem;
    }
    .progress {
      height: 20px;
      margin-top: 10px;
    }
    .attendance-card {
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      overflow: hidden;
      transition: transform 0.3s ease;
    }
    .attendance-card:hover {
      transform: translateY(-5px);
    }
    .attendance-date {
      font-weight: bold;
    }
    .plan-card {
      border: 1px solid #dee2e6;
      border-radius: 10px;
      padding: 1.5rem;
      height: 100%;
      transition: all 0.3s ease;
    }
    .plan-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .plan-price {
      font-size: 1.8rem;
      font-weight: bold;
      color: #0d6efd;
    }
    .plan-title {
      font-size: 1.4rem;
      font-weight: bold;
      margin-bottom: 1rem;
    }
    .recommended {
      position: absolute;
      top: 0;
      right: 1rem;
      background-color: #198754;
      color: white;
      padding: 0.3rem 1rem;
      border-radius: 0 0 10px 10px;
      font-size: 0.8rem;
    }
    .modal-subscription-plans .modal-xl {
      max-width: 1140px;
    }
    .modal-subscription-plans .modal-body {
      padding: 2rem;
    }
    .alert-success-custom {
      background-color: #d1e7dd;
      color: #0f5132;
      border-color: #badbcc;
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1rem;
    }
    .attendance-calendar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 15px;
    }
    .calendar-day {
      width: calc(20% - 8px);
      text-align: center;
      padding: 10px 5px;
      border-radius: 8px;
      margin-bottom: 5px;
    }
    .calendar-present {
      background-color: #d1e7dd;
      color: #0f5132;
      border: 1px solid #badbcc;
    }
    .calendar-absent {
      background-color: #f8d7da;
      color: #842029;
      border: 1px solid #f5c2c7;
    }
    .calendar-date {
      font-size: 0.85rem;
      font-weight: bold;
    }
    .calendar-status {
      font-size: 0.75rem;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <h5 class="mb-4">User Panel</h5>
      <p>Welcome, <?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?>!</p>
      <a href="user_dashboard.php" class="sidebar-link active">Dashboard</a>
      <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a>
    </div>
    <div class="col-md-10 py-4">
      <?php if (isset($_GET['renewal']) && $_GET['renewal'] == 'success'): ?>
      <div class="alert-success-custom">
        <i class="fas fa-check-circle me-2"></i> Your subscription has been successfully renewed!
      </div>
      <?php elseif (isset($_GET['cancellation']) && $_GET['cancellation'] == 'success'): ?>
      <div class="alert-success-custom">
        <i class="fas fa-check-circle me-2"></i> Your subscription has been successfully canceled!
      </div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-md-4">
          <!-- Profile Info -->
          <div class="card card-box">
            <div class="card-header bg-primary text-white">
              <i class="fas fa-user me-2"></i> Profile
            </div>
            <div class="card-body">
              <p><strong>Name:</strong> <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
          </div>

          <!-- Subscription -->
          <div class="card card-box">
            <div class="card-header bg-info text-white">
              <i class="fas fa-credit-card me-2"></i> Subscription
            </div>
            <div class="card-body">
              <?php if ($subscription_active): ?>
                <div class="alert alert-success">
                  <strong><i class="fas fa-check-circle me-2"></i> Active Subscription</strong>
                </div>
                <p><strong>Started:</strong> <?php echo htmlspecialchars($subscription['start_date']); ?></p>
                <p><strong>Expires:</strong> <?php echo htmlspecialchars($subscription['end_date']); ?></p>
                <p><strong>Days Remaining:</strong> <?php echo $days_remaining; ?></p>
                <p><strong>Amount Paid:</strong> ₱<?php echo number_format($subscription['amount'], 2); ?></p>

                <?php if ($days_remaining <= 7): ?>
                <div class="alert alert-warning mt-3">
                  <strong><i class="fas fa-exclamation-triangle me-2"></i> Your subscription will expire soon!</strong>
                </div>
                <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                  <i class="fas fa-sync-alt me-2"></i> Renew Subscription
                </button>
                <?php endif; ?>

                <form method="POST" style="display:inline;">
                  <input type="hidden" name="subscription_id" value="<?php echo $subscription['id']; ?>">
                  <button type="submit" name="cancel_subscription" class="btn btn-danger btn-sm mt-3" onclick="return confirm('Are you sure you want to cancel this subscription?');">Cancel Subscription</button>
                </form>

              <?php elseif ($subscription_expired): ?>
                <div class="alert alert-danger">
                  <strong><i class="fas fa-times-circle me-2"></i> Subscription Expired</strong>
                </div>
                <p><strong>Previous Subscription:</strong></p>
                <p>Started: <?php echo htmlspecialchars($subscription['start_date']); ?></p>
                <p>Expired: <?php echo htmlspecialchars($subscription['end_date']); ?></p>
                <p>Amount Paid: ₱<?php echo number_format($subscription['amount'], 2); ?></p>

                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                  <i class="fas fa-sync-alt me-2"></i> Renew Subscription
                </button>
              <?php else: ?>
                <div class="alert alert-warning">
                  <strong><i class="fas fa-exclamation-triangle me-2"></i> No active subscription</strong>
                </div>
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                  <i class="fas fa-shopping-cart me-2"></i> Purchase Subscription
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Payment History -->
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">
              <i class="fas fa-history me-2"></i> Payment History
              <button type="button" class="btn btn-sm btn-primary float-end" id="togglePaymentHistory">Show</button>
            </div>
            <div class="card-body" id="paymentHistory" style="display: none;">
              <?php if ($payment_history_result->num_rows > 0): ?>
                <table class="table">
                  <thead>
                    <tr>
                      <th>Amount</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($row = $payment_history_result->fetch_assoc()): ?>
                      <tr>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p>No payment history yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <!-- Attendance Today -->
          <div class="card card-box">
            <div class="card-header bg-success text-white">
              <i class="fas fa-calendar-check me-2"></i> Today's Attendance
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h5>Status:</h5>
                  <?php if ($attendance_status == 'Present'): ?>
                    <span class="badge bg-success badge-large"><i class="fas fa-check me-1"></i> PRESENT</span>
                  <?php else: ?>
                    <span class="badge bg-danger badge-large"><i class="fas fa-times me-1"></i> ABSENT</span>
                  <?php endif; ?>
                </div>
                <div>
                  <h5>Current Streak:</h5>
                  <div class="streak-badge">
                    <i class="fas fa-fire me-2"></i> <?php echo $streak; ?> days
                  </div>
                </div>
              </div>

              <div class="mt-4">
                <h5>Monthly Attendance Rate:</h5>
                <div class="progress">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendance_percentage; ?>%;"
                       aria-valuenow="<?php echo $attendance_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php echo $attendance_percentage; ?>%
                  </div>
                </div>
                <div class="text-muted mt-2">
                  Present: <?php echo $month_stats['present_count']; ?> days |
                  Total: <?php echo $month_stats['total_count']; ?> days this month
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Attendance -->
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">
              <i class="fas fa-history me-2"></i> Recent Attendance
            </div>
            <div class="card-body">
              <?php if ($recent_attendance_result->num_rows > 0): ?>
                <div class="attendance-calendar">
                  <?php
                  // Reset data pointer
                  $recent_attendance_result->data_seek(0);
                  while ($row = $recent_attendance_result->fetch_assoc()):
                    $is_present = $row['status'] == 'Present';
                    $class = $is_present ? 'calendar-present' : 'calendar-absent';
                    $icon = $is_present ? 'fa-check-circle' : 'fa-times-circle';
                  ?>
                    <div class="calendar-day <?php echo $class; ?>">
                      <div class="calendar-date">
                        <?php echo htmlspecialchars(date('M d', strtotime($row['attendance_date']))); ?>
                      </div>
                      <div class="calendar-status">
                        <i class="fas <?php echo $icon; ?> me-1"></i>
                        <?php echo $row['status']; ?>
                      </div>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <p>No attendance records yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Subscription Modal -->
<div class="modal fade modal-subscription-plans" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="subscriptionModalLabel">
          <i class="fas fa-tags me-2"></i> Choose Your Subscription Plan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-3 mb-4">
            <div class="plan-card">
              <div class="plan-title">1 Month Plan</div>
              <div class="plan-price">₱1,299</div>
              <p class="text-muted">Basic plan for one month</p>
              <ul class="list-unstyled mt-3">
                <li><i class="fas fa-check text-success me-2"></i> Full access to all features</li>
                <li><i class="fas fa-check text-success me-2"></i> Personal attendance tracking</li>
                <li><i class="fas fa-check text-success me-2"></i> Basic support</li>
              </ul>
              <form method="POST" class="mt-4">
                <input type="hidden" name="subscription_type" value="1month">
                <button type="submit" name="renew_subscription" class="btn btn-outline-primary w-100">Choose Plan</button>
              </form>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="plan-card position-relative">
              <div class="recommended">RECOMMENDED</div>
              <div class="plan-title">3 Months Plan</div>
              <div class="plan-price">₱2,999</div>
              <p class="text-muted"><span class="text-success">Save 23%</span> compared to monthly</p>
              <ul class="list-unstyled mt-3">
                <li><i class="fas fa-check text-success me-2"></i> Full access to all features</li>
                <li><i class="fas fa-check text-success me-2"></i> Personal attendance tracking</li>
                <li><i class="fas fa-check text-success me-2"></i> Priority support</li>
              </ul>
              <form method="POST" class="mt-4">
                <input type="hidden" name="subscription_type" value="3months">
                <button type="submit" name="renew_subscription" class="btn btn-primary w-100">Choose Plan</button>
              </form>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="plan-card">
              <div class="plan-title">6 Months Plan</div>
              <div class="plan-price">₱5,499</div>
              <p class="text-muted"><span class="text-success">Save 30%</span> compared to monthly</p>
              <ul class="list-unstyled mt-3">
                <li><i class="fas fa-check text-success me-2"></i> Full access to all features</li>
                <li><i class="fas fa-check text-success me-2"></i> Personal attendance tracking</li>
                <li><i class="fas fa-check text-success me-2"></i> Priority support</li>
                <li><i class="fas fa-check text-success me-2"></i> Monthly reports</li>
              </ul>
              <form method="POST" class="mt-4">
                <input type="hidden" name="subscription_type" value="6months">
                <button type="submit" name="renew_subscription" class="btn btn-outline-primary w-100">Choose Plan</button>
              </form>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="plan-card">
              <div class="plan-title">12 Months Plan</div>
              <div class="plan-price">₱9,999</div>
              <p class="text-muted"><span class="text-success">Save 36%</span> compared to monthly</p>
              <ul class="list-unstyled mt-3">
                <li><i class="fas fa-check text-success me-2"></i> Full access to all features</li>
                <li><i class="fas fa-check text-success me-2"></i> Personal attendance tracking</li>
                <li><i class="fas fa-check text-success me-2"></i> Premium support</li>
                <li><i class="fas fa-check text-success me-2"></i> Monthly reports</li>
                <li><i class="fas fa-check text-success me-2"></i> Advanced analytics</li>
              </ul>
              <form method="POST" class="mt-4">
                <input type="hidden" name="subscription_type" value="12months">
                <button type="submit" name="renew_subscription" class="btn btn-outline-primary w-100">Choose Plan</button>
              </form>
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
      text: "You will be logged out.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, log out",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = "logout.php";
      }
    });
  }

  // Show success message if subscription was renewed
  <?php if (isset($_GET['renewal']) && $_GET['renewal'] == 'success'): ?>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      title: "Success!",
      text: "Your subscription has been successfully renewed.",
      icon: "success",
      confirmButtonColor: "#28a745"
    });
  });
  <?php endif; ?>

  // Show success message if subscription was canceled
  <?php if (isset($_GET['cancellation']) && $_GET['cancellation'] == 'success'): ?>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      title: "Success!",
      text: "Your subscription has been successfully canceled.",
      icon: "success",
      confirmButtonColor: "#28a745"
    });
  });
  <?php endif; ?>

  // Toggle payment history visibility
  document.getElementById('togglePaymentHistory').addEventListener('click', function() {
    var paymentHistory = document.getElementById('paymentHistory');
    var button = document.getElementById('togglePaymentHistory');
    if (paymentHistory.style.display === 'none') {
      paymentHistory.style.display = 'block';
      button.textContent = 'Hide';
    } else {
      paymentHistory.style.display = 'none';
      button.textContent = 'Show';
    }
  });
</script>
</body>
</html>
