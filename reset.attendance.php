<?php
include 'connect.php';

// Calculate the date and time 24 hours ago
$cutoff_time = date('Y-m-d H:i:s', strtotime('-24 hours'));

// Fetch all users
$users_result = $conn->query("SELECT id FROM users");

while ($user = $users_result->fetch_assoc()) {
    $user_id = $user['id'];

    // Check if the user has logged in within the last 24 hours
    $attendance_check = $conn->query("SELECT * FROM attendance WHERE member_id = $user_id AND attendance_date >= '$cutoff_time'");

    if ($attendance_check->num_rows == 0) {
        // Mark the user as absent
        $conn->query("INSERT INTO attendance (member_id, status, attendance_date) VALUES ($user_id, 'Absent', NOW())");
    }
}
?>
