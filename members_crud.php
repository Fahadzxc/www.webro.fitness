<?php
session_start();
include 'connect.php';
include 'db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT firstName, lastName FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();

// Set default values for filtering and sorting
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'firstName';
$sort_direction = $_GET['direction'] ?? 'ASC';

// Handle member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        // Add to users table as member (role = 'user')
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $name_parts = explode(' ', $name);
        $firstName = $name_parts[0];
        $lastName = (count($name_parts) > 1) ? $name_parts[1] : '';
        
        // Current date for joined_date to match created_at
        $current_date = date('Y-m-d');

        // Check if user already exists by email
        $existing_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $existing_user->bind_param("s", $email);
        $existing_user->execute();
        $existing_user->store_result();
        if ($existing_user->num_rows > 0) {
            // User exists
            header("Location: members_crud.php?error=exists");
            exit();
        }
        $existing_user->close();

        // Insert into users with current date for joined_date - created_at will be set automatically by DB
        $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, role, joined_date) VALUES (?, ?, ?, ?, 'user', ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $password, $current_date);
        $stmt->execute();

        header("Location: members_crud.php?success=added");
        exit();
    }

    if (isset($_POST['edit_member'])) {
        $id = intval($_POST['member_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        $name_parts = explode(' ', $name);
        $firstName = $name_parts[0];
        $lastName = (count($name_parts) > 1) ? $name_parts[1] : '';
        
        // Set the current time for updated_at
        $current_datetime = date('Y-m-d H:i:s');

        // Update users table
        $stmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ?, updated_at = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param("ssssi", $firstName, $lastName, $email, $current_datetime, $id);
        $stmt->execute();

        header("Location: members_crud.php?success=updated");
        exit();
    }

    if (isset($_POST['delete_member'])) {
        $id = intval($_POST['member_id']);

        // Delete related data
        $conn->query("DELETE FROM subscriptions WHERE member_id = $id");
        $conn->query("DELETE FROM payments WHERE member_id = $id");
        $conn->query("DELETE FROM attendance WHERE member_id = $id");
        $conn->query("DELETE FROM users WHERE id = $id AND role = 'user'");

        header("Location: members_crud.php?success=deleted");
        exit();
    }
}

// Build query to list current members (users with role 'user')
$query = "SELECT id, firstName, lastName, email,
          created_at, updated_at
          FROM users WHERE role = 'user'";

// Search filter
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $query .= " AND (firstName LIKE '%$search_escaped%' OR lastName LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')";
}

// Sorting
$allowed_sort_columns = ['firstName', 'lastName', 'email', 'created_at'];
if (!in_array($sort, $allowed_sort_columns)) {
    $sort = 'firstName'; // default
}

// Replace 'joined_date' with 'created_at' in sort
if ($sort == 'joined_date') {
    $sort = 'created_at';
}

$sort_dir = strtoupper($sort_direction) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY $sort $sort_dir";

$result = $conn->query($query);

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$total_members = count($members);

// Format datetime function for consistent display
function formatDateTime($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00' || strtotime($datetime) <= 0) {
        return 'Not available';
    }
    $timestamp = strtotime($datetime);
    return date('M d, Y h:i A', $timestamp);
}

// Format date only (for joined_date display)
function formatDate($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00' || strtotime($datetime) <= 0) {
        return 'Not available';
    }
    $timestamp = strtotime($datetime);
    return date('M d, Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Members</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    
    .welcome-text {
      color: rgba(255,255,255,0.9);
      font-weight: 500;
      margin-bottom: 1.5rem;
    }
    
    .user-name {
      font-weight: 700;
    }

    .card-box {
      border-radius: 1rem;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 1.5rem;
    }
    
    .action-btns .btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
    }
    
    .page-header {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 1.5rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .content-area {
      padding: 2rem;
    }

    .back-btn {
      transition: all 0.3s ease;
    }
    
    .back-btn:hover {
      transform: translateX(-5px);
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar - Matched to admin_dashboard.php -->
    <div class="col-md-2 sidebar">
      <div class="sidebar-header">
        <h4>Admin Panel</h4>
        <p class="welcome-text">Welcome, <span class="user-name"><?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?></span>!</p>
      </div>
      <a href="admin_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
      <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="logoutConfirmation()" class="sidebar-link logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <!-- Content Area -->
    <div class="col-md-10 content-area">
      <!-- Success or error messages -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php
            $messages = [
              'added' => 'Member successfully added!',
              'updated' => 'Member successfully updated!',
              'deleted' => 'Member successfully deleted!'
            ];
            echo $messages[$_GET['success']] ?? '';
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php elseif (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          User with this email already exists.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Page Header -->
      <div class="page-header">
        <h2><i class="fas fa-users me-2"></i> Manage Members</h2>
        <a href="admin_dashboard.php" class="btn btn-primary back-btn">
          <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>

      <!-- Search and Filters -->
      <div class="card card-box mb-4">
        <div class="card-body">
          <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" name="sort">
                <option value="firstName" <?= $sort == 'firstName' ? 'selected' : '' ?>>Sort by First Name</option>
                <option value="lastName" <?= $sort == 'lastName' ? 'selected' : '' ?>>Sort by Last Name</option>
                <option value="created_at" <?= $sort == 'created_at' ? 'selected' : '' ?>>Sort by Joined Date</option>
                <option value="email" <?= $sort == 'email' ? 'selected' : '' ?>>Sort by Email</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" name="direction">
                <option value="ASC" <?= $sort_direction == 'ASC' ? 'selected' : '' ?>>Ascending</option>
                <option value="DESC" <?= $sort_direction == 'DESC' ? 'selected' : '' ?>>Descending</option>
              </select>
            </div>
            <div class="col-md-3"></div>
            <div class="col-md-12 text-end">
              <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
              <a href="members_crud.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-1"></i> Reset</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Members Table -->
      <div class="card card-box">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-table me-2"></i> Members List
          <span class="badge bg-light text-dark float-end"><?= $total_members; ?> Total</span>
        </div>
        <div class="card-body">
          <?php if ($total_members > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($members as $index => $member): ?>
                    <tr>
                      <td><?= $index + 1; ?></td>
                      <td><?= htmlspecialchars($member['firstName']); ?></td>
                      <td><?= htmlspecialchars($member['lastName']); ?></td>
                      <td><?= htmlspecialchars($member['email']); ?></td>
                      <td><?= formatDateTime($member['created_at']); ?></td>
                      <td class="action-btns">
                        <button class="btn btn-info btn-sm" title="View Member Details" data-bs-toggle="modal" data-bs-target="#viewMemberModal<?= $member['id']; ?>">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" title="Edit Member" data-bs-toggle="modal" data-bs-target="#editMemberModal<?= $member['id']; ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this member?');" style="display:inline;">
                          <input type="hidden" name="member_id" value="<?= $member['id']; ?>">
                          <button type="submit" name="delete_member" class="btn btn-danger btn-sm" title="Delete Member">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>

                    <!-- View Member Modal -->
                    <div class="modal fade" id="viewMemberModal<?= $member['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="fas fa-user me-2"></i> Member Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="fw-bold">Name:</label>
                              <p><?= htmlspecialchars($member['firstName'] . ' ' . $member['lastName']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="fw-bold">Email:</label>
                              <p><?= htmlspecialchars($member['email']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="fw-bold">Joined Date:</label>
                              <p><?= formatDateTime($member['created_at']); ?></p>
                            </div>
                            <?php if (!empty($member['updated_at']) && $member['updated_at'] != '0000-00-00 00:00:00'): ?>
                            <div class="mb-3">
                              <label class="fw-bold">Last Updated:</label>
                              <p><?= formatDateTime($member['updated_at']); ?></p>
                            </div>
                            <?php endif; ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Edit Member Modal -->
                    <div class="modal fade" id="editMemberModal<?= $member['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="POST" action="">
                            <div class="modal-header bg-warning text-dark">
                              <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Member</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="member_id" value="<?= $member['id']; ?>">
                              <div class="mb-3">
                                <label for="edit_name<?= $member['id']; ?>" class="form-label">Full Name</label>
                                <input type="text" id="edit_name<?= $member['id']; ?>" name="name" class="form-control" value="<?= htmlspecialchars($member['firstName'] . ' ' . $member['lastName']); ?>" required />
                              </div>
                              <div class="mb-3">
                                <label for="edit_email<?= $member['id']; ?>" class="form-label">Email</label>
                                <input type="email" id="edit_email<?= $member['id']; ?>" name="email" class="form-control" value="<?= htmlspecialchars($member['email']); ?>" required />
                              </div>
                              <div class="mb-3">
                                <label for="joined_date<?= $member['id']; ?>" class="form-label">Joined Date</label>
                                <input type="text" id="joined_date<?= $member['id']; ?>" class="form-control" value="<?= formatDateTime($member['created_at']); ?>" readonly />
                                <small class="text-muted">Joined date cannot be modified</small>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="edit_member" class="btn btn-warning">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> No members found.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true" aria-labelledby="addMemberModalLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addMemberModalLabel"><i class="fas fa-user-plus me-2"></i> Add New Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required />
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" />
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea id="address" name="address" rows="3" class="form-control"></textarea>
          </div>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> A user account will be created with default password: <strong>password123</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_member" class="btn btn-primary">Add Member</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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