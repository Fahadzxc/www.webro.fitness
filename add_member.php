<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("INSERT INTO members (name, email, phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $phone);
    $stmt->execute();

    header("Location: members.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Member</title>
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
        <?php include 'sidebar.php'; ?> 
        
        <!-- Main Content -->
        <div class="col-md-10 mt-5">
            <div class="container">
                <h2>Add Member</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
