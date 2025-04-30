<?php
session_start();
include 'connect.php'; // For database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user-specific subscription data
$user_id = $_SESSION['user_id'];
$subscription_result = $conn->query("
    SELECT subscription_date, due_date, status
    FROM subscriptions
    WHERE member_id = $user_id
    ORDER BY subscription_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Subscription</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Subscription Details</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Subscription Date</th>
        <th>Due Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $subscription_result->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['subscription_date']); ?></td>
          <td><?php echo htmlspecialchars($row['due_date']); ?></td>
          <td><?php echo htmlspecialchars($row['status']); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
