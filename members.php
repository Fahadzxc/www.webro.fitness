<?php
include 'db.php';

// Fetch all members
$result = $conn->query("SELECT * FROM members ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <h5 class="mb-4"></h5>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="billing.php">Billing</a>
           
            <a href="sales.php">Sales</a>
            <a href="settings.php">Settings</a> <!-- Settings Link -->
            <a href="#" onclick="logoutConfirmation()" class="logout-btn">Logout</a> <!-- Updated Logout Button -->
    <!-- SweetAlert2 Library -->
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
            window.location.href = "logout.php"; // Redirect to logout page
        }
    });
}
</script>
            
        </div>

        <!-- Main Content -->
        <div class="col-md-10 mt-5">
            <div class="container">
                <h2>Manage Members</h2>
                <a href="add_member.php" class="btn btn-success mb-3">Add Member</a>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo $row['joined_date']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td>
                                <a href="edit_member.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_member.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
