<?php
session_start();
include 'connect.php'; // For database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user-specific attendance data
$user_id = $_SESSION['user_id'];
$attendance_result = $conn->query("
    SELECT attendance_date, status
    FROM attendance
    WHERE member_id = $user_id
    ORDER BY attendance_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Attendance Records</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $attendance_result->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
          <td><?php echo htmlspecialchars($row['status']); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
