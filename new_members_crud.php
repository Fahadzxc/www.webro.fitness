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

// Check if the required columns exist
$check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'is_new_member'");
if ($check_columns->num_rows == 0) {
    $_SESSION['message'] = "Database setup required. Please run the setup first.";
    $_SESSION['message_type'] = "warning";
    header("Location: setup_new_member_columns.php");
    exit();
}

// AUTOMATIC RESET SYSTEM
// This query will automatically reset members who have been "new" for 7+ days
$reset_query = "
    UPDATE users 
    SET is_new_member = 0 
    WHERE is_new_member = 1 
    AND DATE(created_at) <= DATE(NOW() - INTERVAL 7 DAY)
";

if ($conn->query($reset_query)) {
    // Log the reset operation if any members were reset
    $reset_count = $conn->affected_rows;
    if ($reset_count > 0) {
        // Check if activity_log table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if ($check_table->num_rows > 0) {
            $log_query = "INSERT INTO activity_log (user_id, action, details, created_at) 
                         VALUES ($user_id, 'member_reset', '$reset_count members automatically reset from new status', NOW())";
            $conn->query($log_query);
        }
        
        // Add a session message if members were reset
        $_SESSION['message'] = "$reset_count members were automatically removed from new member status";
        $_SESSION['message_type'] = "info";
    }
}

// Handle Extend New Status
if (isset($_GET['extend'])) {
    $member_id = $_GET['extend'];
    // Update the created_at date to today, giving them a fresh 7 days
    $update_query = "UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE id = $member_id";

    if ($conn->query($update_query)) {
        // Log the extension
        $log_query = "INSERT INTO activity_log (user_id, action, details, created_at) 
                     VALUES ($user_id, 'status_extended', 'Extended new member status for member ID $member_id', NOW())";
        $conn->query($log_query);
        
        $_SESSION['message'] = "Member status extended for another 7 days";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error extending member status: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: new_members.php");
    exit();
}

// Get specific time periods for filtering
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $filterClause = "";
    
    switch ($filter) {
        case 'last_24h':
            $filterClause = "AND DATEDIFF(NOW(), u.created_at) <= 1";
            $filterTitle = "New Members - Last 24 Hours";
            break;
        case 'last_3_days':
            $filterClause = "AND DATEDIFF(NOW(), u.created_at) <= 3";
            $filterTitle = "New Members - Last 3 Days";
            break;
        case 'with_subscription':
            $filterClause = "AND s.end_date >= CURDATE()";
            $filterTitle = "New Members - With Active Subscription";
            break;
        default:
            $filterTitle = "All New Members";
    }
} else {
    $filter = '';
    $filterClause = "";
    $filterTitle = "All New Members";
}

// Get all new members with filter if applied
$query = "
    SELECT u.id, u.firstName, u.lastName, u.email, u.created_at, u.is_new_member,
           DATEDIFF(NOW(), u.created_at) as days_since_registration,
           (7 - DATEDIFF(NOW(), u.created_at)) as days_remaining,
           s.start_date, s.end_date, s.amount,
           CASE 
             WHEN s.end_date >= CURDATE() THEN 'Active' 
             ELSE 'Expired' 
           END as subscription_status
    FROM users u
    LEFT JOIN subscriptions s ON u.id = s.member_id AND s.end_date = (
        SELECT MAX(end_date) FROM subscriptions WHERE member_id = u.id
    )
    WHERE u.is_new_member = 1 $filterClause
    ORDER BY u.created_at DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Error: " . $conn->error);
}

// Get statistics for new members
$stats_query = "
    SELECT 
        COUNT(*) as total_new_members,
        COUNT(CASE WHEN DATEDIFF(NOW(), created_at) <= 1 THEN 1 END) as last_24h,
        COUNT(CASE WHEN DATEDIFF(NOW(), created_at) <= 3 THEN 1 END) as last_3_days,
        SUM(CASE WHEN s.end_date >= CURDATE() THEN 1 ELSE 0 END) as with_active_subscription
    FROM users u
    LEFT JOIN subscriptions s ON u.id = s.member_id AND s.end_date = (
        SELECT MAX(end_date) FROM subscriptions WHERE member_id = u.id
    )
    WHERE u.is_new_member = 1
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Members - Admin Dashboard</title>
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
    .stats-card {
      border-left: 4px solid;
      display: flex;
      align-items: center;
      background-color: #fff;
      padding: 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
      cursor: pointer;
    }
    .stats-card:hover {
      transform: translateY(-5px);
    }
    .stats-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin-right: 1rem;
      color: white;
    }
    .stats-info h4 {
      margin-bottom: 0.2rem;
      font-size: 2rem;
      font-weight: 700;
    }
    .stats-info p {
      margin-bottom: 0;
      color: #6c757d;
      font-size: 0.9rem;
    }
    .days-remaining {
      font-weight: bold;
    }
    .days-critical {
      color: #dc3545;
    }
    .days-warning {
      color: #fd7e14;
    }
    .days-good {
      color: #198754;
    }
    .progress {
      height: 8px;
      margin-top: 5px;
      border-radius: 10px;
    }
    .badge-pill {
      border-radius: 30px;
      padding: 0.35em 0.65em;
    }
    .tooltip-inner {
      max-width: 300px;
    }
    .active-filter {
      border: 2px solid #0d6efd;
      box-shadow: 0 0 15px rgba(13, 110, 253, 0.3);
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
          <h1><i class="fas fa-user-plus text-primary me-2"></i> <?php echo $filterTitle; ?></h1>
          <p class="text-muted">Track new members with automatic status reset after 7 days.</p>
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

      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <a href="new_members.php" class="text-decoration-none">
            <div class="stats-card <?php echo ($filter == '') ? 'active-filter' : ''; ?>" style="border-left-color: #0d6efd;">
              <div class="stats-icon" style="background-color: #0d6efd;">
                <i class="fas fa-users fa-2x"></i>
              </div>
              <div class="stats-info">
                <h4><?php echo $stats['total_new_members'] ?? 0; ?></h4>
                <p>Total New Members</p>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-3">
          <a href="new_members.php?filter=last_24h" class="text-decoration-none">
            <div class="stats-card <?php echo ($filter == 'last_24h') ? 'active-filter' : ''; ?>" style="border-left-color: #198754;">
              <div class="stats-icon" style="background-color: #198754;">
                <i class="fas fa-user-clock fa-2x"></i>
              </div>
              <div class="stats-info">
                <h4><?php echo $stats['last_24h'] ?? 0; ?></h4>
                <p>New in Last 24 Hours</p>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-3">
          <a href="new_members.php?filter=last_3_days" class="text-decoration-none">
            <div class="stats-card <?php echo ($filter == 'last_3_days') ? 'active-filter' : ''; ?>" style="border-left-color: #ffc107;">
              <div class="stats-icon" style="background-color: #ffc107;">
                <i class="fas fa-calendar-day fa-2x"></i>
              </div>
              <div class="stats-info">
                <h4><?php echo $stats['last_3_days'] ?? 0; ?></h4>
                <p>New in Last 3 Days</p>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-3">
          <a href="new_members.php?filter=with_subscription" class="text-decoration-none">
            <div class="stats-card <?php echo ($filter == 'with_subscription') ? 'active-filter' : ''; ?>" style="border-left-color: #dc3545;">
              <div class="stats-icon" style="background-color: #dc3545;">
                <i class="fas fa-credit-card fa-2x"></i>
              </div>
              <div class="stats-info">
                <h4><?php echo $stats['with_active_subscription'] ?? 0; ?></h4>
                <p>With Active Subscription</p>
              </div>
            </div>
          </a>
        </div>
      </div>

      <div class="card card-box">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-list me-2"></i> <?php echo $filterTitle; ?> (Auto-Reset After 7 Days)
        </div>
        <div class="card-body">
          <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Member Name</th>
                  <th>Email</th>
                  <th>Registered On</th>
                  <th>Days Remaining</th>
                  <th>Status</th>
                  <th>Subscription</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $result->fetch_assoc()): 
                  $days_remaining_class = '';
                  $badge_class = '';
                  $progress_class = '';
                  
                  if ($row['days_remaining'] <= 1) {
                    $days_remaining_class = 'days-critical';
                    $badge_class = 'bg-danger';
                    $progress_class = 'bg-danger';
                  } elseif ($row['days_remaining'] <= 3) {
                    $days_remaining_class = 'days-warning';
                    $badge_class = 'bg-warning text-dark';
                    $progress_class = 'bg-warning';
                  } else {
                    $days_remaining_class = 'days-good';
                    $badge_class = 'bg-success';
                    $progress_class = 'bg-success';
                  }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                  <td>
                    <div class="days-remaining <?php echo $days_remaining_class; ?>">
                      <?php echo $row['days_remaining']; ?> days
                      <div class="progress">
                        <div class="progress-bar <?php echo $progress_class; ?>" 
                             role="progressbar" 
                             style="width: <?php echo (($row['days_remaining'] / 7) * 100); ?>%" 
                             aria-valuenow="<?php echo $row['days_remaining']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="7">
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?php echo $badge_class; ?> badge-pill">
                      <?php if ($row['days_remaining'] <= 1): ?>
                      Critical
                      <?php elseif ($row['days_remaining'] <= 3): ?>
                      Warning
                      <?php else: ?>
                      Good
                      <?php endif; ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($row['subscription_status'] == 'Active'): ?>
                      <span class="badge bg-success">Active Until <?php echo date('M d, Y', strtotime($row['end_date'])); ?></span>
                    <?php else: ?>
                      <span class="badge bg-danger">No Active Subscription</span>
                    <?php endif; ?>
                  </td>
                  <td class="action-buttons">
                    <a href="view_member.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <?php if ($row['days_remaining'] <= 2): ?>
                    <a href="new_members.php?extend=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" 
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Extend new member status for an additional 7 days">
                      <i class="fas fa-clock me-1"></i> Extend
                    </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No members found matching your criteria.
          </div>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-light">
          <div class="d-flex align-items-center justify-content-between">
            <small class="text-muted">The system automatically resets new member status after 7 days</small>
            <a href="export_new_members.php<?php echo $filter ? "?filter=$filter" : ""; ?>" class="btn btn-sm btn-outline-primary">
              <i class="fas fa-download me-1"></i> Export List
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Initialize all tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  });

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