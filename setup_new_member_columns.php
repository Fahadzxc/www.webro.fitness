<?php
// Database setup script to add necessary columns for the new member tracking system
session_start();
include 'connect.php';
include 'db.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$messages = [];
$errors = [];

// Check if is_new_member column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_new_member'");
if ($check_column->num_rows == 0) {
    // Column doesn't exist, add it
    $add_column = "ALTER TABLE users ADD COLUMN is_new_member TINYINT(1) DEFAULT 1 AFTER email";
    if ($conn->query($add_column)) {
        $messages[] = "Added 'is_new_member' column to users table successfully.";
        
        // Update all existing users to have is_new_member = 1 if they registered in last 7 days
        $update_existing = "UPDATE users SET is_new_member = 1 WHERE created_at >= DATE(NOW() - INTERVAL 7 DAY)";
        if ($conn->query($update_existing)) {
            $messages[] = "Set is_new_member status for recently registered users.";
        } else {
            $errors[] = "Failed to update existing users: " . $conn->error;
        }
    } else {
        $errors[] = "Failed to add 'is_new_member' column: " . $conn->error;
    }
} else {
    $messages[] = "Column 'is_new_member' already exists.";
}

// Check if created_at column exists
$check_created = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($check_created->num_rows == 0) {
    // Column doesn't exist, add it
    $add_created = "ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_new_member";
    if ($conn->query($add_created)) {
        $messages[] = "Added 'created_at' column to users table successfully.";
        
        // Set default values for existing users
        $update_dates = "UPDATE users SET created_at = NOW() WHERE created_at IS NULL";
        if ($conn->query($update_dates)) {
            $messages[] = "Set default dates for existing users.";
        } else {
            $errors[] = "Failed to set default dates: " . $conn->error;
        }
    } else {
        $errors[] = "Failed to add 'created_at' column: " . $conn->error;
    }
} else {
    $messages[] = "Column 'created_at' already exists.";
}

// Check if activity_log table exists
$check_table = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($check_table->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table = "CREATE TABLE activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_table)) {
        $messages[] = "Created 'activity_log' table successfully.";
    } else {
        $errors[] = "Failed to create 'activity_log' table: " . $conn->error;
    }
} else {
    $messages[] = "Table 'activity_log' already exists.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Member System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 50px 0;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background-color: #1a237e;
            color: white;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
        }
        .setup-step {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .step-success {
            background-color: rgba(25, 135, 84, 0.1);
            border-left-color: #198754;
        }
        .step-error {
            background-color: rgba(220, 53, 69, 0.1);
            border-left-color: #dc3545;
        }
        .btn-next {
            background-color: #1a237e;
            color: white;
            border: none;
            transition: all 0.3s;
        }
        .btn-next:hover {
            background-color: #303f9f;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-cogs me-2"></i> New Member System Setup</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Setup Progress</h5>
                            <p>The system is checking and creating all required database components...</p>
                        </div>
                        
                        <?php if (!empty($messages)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-check-circle text-success me-2"></i> Successful Changes</h5>
                                <?php foreach ($messages as $message): ?>
                                    <div class="setup-step step-success">
                                        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-exclamation-triangle text-danger me-2"></i> Errors</h5>
                                <?php foreach ($errors as $error): ?>
                                    <div class="setup-step step-error">
                                        <i class="fas fa-times-circle me-2"></i> <?php echo $error; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert <?php echo empty($errors) ? 'alert-success' : 'alert-warning'; ?>">
                            <?php if (empty($errors)): ?>
                                <i class="fas fa-check-circle me-2"></i> Setup completed successfully! You can now use the New Members tracking system.
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle me-2"></i> Setup encountered some errors. Please resolve them before using the New Members system.
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i> Back to Dashboard
                        </a>
                        <a href="new_members.php" class="btn btn-next">
                            Go to New Members <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>