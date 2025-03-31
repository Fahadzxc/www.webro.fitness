<?php
session_start();
require __DIR__ . "/vendor/autoload.php";
include 'connect.php'; // For login database

$client = new Google\Client();
$client->setClientId("804231969129-tmpr53569nhhsga15df4hcqebgd677j0.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/www.webro.fitness/user_dashboard.php");
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Fetch user info
        $oauth2 = new Google\Service\Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        // Check if user exists in the database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $email = $userInfo->email;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Insert new user into the database
            $fullName = explode(" ", $userInfo->name);
            $firstName = $fullName[0];
            $lastName = isset($fullName[1]) ? $fullName[1] : '';
            $username = $userInfo->email; // You can customize this as needed
            $profilePicture = $userInfo->picture;
            $membershipStatus = 'Google User';
            $role = 'user';

            $insertStmt = $conn->prepare("INSERT INTO users (username, firstName, lastName, email, profile_picture, membership_status, created_at, role) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $insertStmt->bind_param("sssssss", $username, $firstName, $lastName, $email, $profilePicture, $membershipStatus, $role);
            $insertStmt->execute();
            $user_id = $insertStmt->insert_id;
        } else {
            $user_data = $result->fetch_assoc();
            $user_id = $user_data['id'];
        }

        // Store user info in session
        $_SESSION['google_user'] = [
            'email' => $userInfo->email,
            'name' => $userInfo->name,
            'picture' => $userInfo->picture
        ];
        $_SESSION['user_id'] = $user_id;

        // Redirect to dashboard or desired page
        header('Location: user_dashboard.php');
        exit();
    } else {
        echo "Error: " . $token['error'];
    }
}

// Check if the user is logged in
if (!isset($_SESSION['google_user']) && !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user-specific data
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT firstName, lastName, email, profile_picture, membership_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->fetch_assoc();

    // Set user details
    $username = htmlspecialchars($user_data['firstName'] . ' ' . $user_data['lastName']);
    $profile_picture = $user_data['profile_picture'];
    $membership_status = htmlspecialchars($user_data['membership_status'] ?? 'Inactive');
} else {
    $username = $_SESSION['google_user']['name'];
    $profile_picture = $_SESSION['google_user']['picture'];
    $membership_status = 'Google User';
}

// Fetch quick stats
$stats_query = $conn->prepare("SELECT last_checkin, active_days, remaining_sessions FROM user_stats WHERE user_id = ?");
$stats_query->bind_param("i", $user_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();

$last_checkin = $stats['last_checkin'] ?? 'No check-ins';
$active_days = $stats['active_days'] ?? 0;
$remaining_sessions = $stats['remaining_sessions'] ?? 'Unlimited';

// Fetch announcements
$announcements = $conn->query("SELECT message FROM announcements ORDER BY created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-box { border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding: 1rem; }
    .sidebar a { color: white; display: block; padding: 0.5rem 0; text-decoration: none; }
    .sidebar a:hover { background-color: #495057; border-radius: 0.5rem; }
    .user-profile img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <div class="text-center mb-4">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="user-profile">
        <h5><?php echo $username; ?></h5>
        <p>Membership: <strong><?php echo $membership_status; ?></strong></p>
      </div>
      <a href="dashboard.php">Dashboard</a>
      <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a>
    </div>

    <div class="col-md-10 py-4">
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card card-box">
            <div class="card-header bg-primary text-white">Quick Stats</div>
            <div class="card-body">
              <ul class="list-group">
                <li class="list-group-item">Last Check-in: <strong><?php echo $last_checkin; ?></strong></li>
                <li class="list-group-item">Active Days: <strong><?php echo $active_days; ?></strong></li>
                <li class="list-group-item">Remaining Sessions: <strong><?php echo $remaining_sessions; ?></strong></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card card-box">
            <div class="card-header bg-secondary text-white">Gym Announcements</div>
            <div class="card-body">
              <ul class="list-group">
                <?php while ($row = $announcements->fetch_assoc()): ?>
                  <li class="list-group-item">📢 <?php echo htmlspecialchars($row['message']); ?></li>
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
</script>

</body>
</html>
