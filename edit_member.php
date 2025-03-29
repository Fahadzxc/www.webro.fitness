<?php
include 'db.php';

$id = $_GET['id'];
$member = $conn->query("SELECT * FROM members WHERE id = $id")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE members SET name=?, email=?, phone=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $status, $id);
    $stmt->execute();

    header("Location: members.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Member</title>
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
                <h2>Edit Member</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $member['name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $member['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $member['phone']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($member['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Cancelled" <?php echo ($member['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Member</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
