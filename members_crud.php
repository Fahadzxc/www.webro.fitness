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

// Set default values for search, sort, filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_direction = isset($_GET['direction']) ? $_GET['direction'] : 'ASC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Member
    if (isset($_POST['add_member'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $joined_date = date('Y-m-d');
        
        $stmt = $conn->prepare("INSERT INTO members (name, email, phone, address, joined_date, status) VALUES (?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("sssss", $name, $email, $phone, $address, $joined_date);
        $stmt->execute();
        
        // Create user account
        $password = password_hash("password123", PASSWORD_DEFAULT); // Default password
        $firstName = explode(' ', $name)[0];
        $lastName = count(explode(' ', $name)) > 1 ? explode(' ', $name)[1] : '';
        
        $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, role) VALUES (?, ?, ?, ?, 'member')");
        $stmt->bind_param("ssss", $firstName, $lastName, $email, $password);
        $stmt->execute();
        
        header("Location: members_crud.php?success=added");
        exit();
    }
    
    // Edit Member
    if (isset($_POST['edit_member'])) {
        $id = $_POST['member_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE members SET name = ?, email = ?, phone = ?, address = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $status, $id);
        $stmt->execute();
        
        // Update user account
        $firstName = explode(' ', $name)[0];
        $lastName = count(explode(' ', $name)) > 1 ? explode(' ', $name)[1] : '';
        
        $stmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ? WHERE email = (SELECT email FROM members WHERE id = ?)");
        $stmt->bind_param("sssi", $firstName, $lastName, $email, $id);
        $stmt->execute();
        
        header("Location: members_crud.php?success=updated");
        exit();
    }
    
    // Delete Member
    if (isset($_POST['delete_member'])) {
        $id = $_POST['member_id'];
        
        // Get member email before deleting
        $result = $conn->query("SELECT email FROM members WHERE id = $id");
        $member = $result->fetch_assoc();
        $email = $member['email'];
        
        // Delete associated subscriptions
        $conn->query("DELETE FROM subscriptions WHERE member_id = $id");
        
        // Delete member
        $conn->query("DELETE FROM members WHERE id = $id");
        
        // Delete user account
        $conn->query("DELETE FROM users WHERE email = '$email'");
        
        header("Location: members_crud.php?success=deleted");
        exit();
    }
}

// Build query with search and filters
$query = "SELECT * FROM members WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

if ($status_filter != 'all') {
    $query .= " AND status = '$status_filter'";
}

$query .= " ORDER BY $sort $sort_direction";

$result = $conn->query($query);
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

// Get count for pagination later
$total_members = count($members);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Members</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    
    .table {
      margin-bottom: 0;
    }
    
    .table th {
      background-color: rgba(0,0,0,0.03);
      font-weight: 600;
      border-bottom: 2px solid rgba(0,0,0,0.05);
    }
    
    .action-btns .btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
    }
    
    .action-btns .btn i {
      margin-right: 0.25rem;
    }
    
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1050;
    }
    
    .filter-row {
      background-color: #f8f9fa;
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1rem;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      border: none;
    }
    
    .btn-success {
      background: linear-gradient(135deg, #28a745 0%, #218838 100%);
      border: none;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
      border: none;
    }
    
    .btn-warning {
      background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);
      border: none;
      color: #212529;
    }
    
    .btn-info {
      background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
      border: none;
      color: #fff;
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
    
    .logout-btn {
      margin-top: 2rem;
      background-color: rgba(220, 53, 69, 0.6);
      color: white;
      transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
      background-color: rgba(220, 53, 69, 0.9);
    }
    
    .status-badge {
      padding: 0.35em 0.65em;
      border-radius: 0.25rem;
      font-size: 0.75em;
      font-weight: 700;
      text-transform: uppercase;
    }
    
    .modal-header {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
    }
    
    .modal-title {
      font-weight: 600;
    }
    
    .modal-footer {
      border-top: none;
    }
    
    .content-area {
      padding: 2rem;
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
      <a href="members_crud.php" class="sidebar-link active">
        <i class="fas fa-users"></i> Members
      </a>
      <a href="billing.php" class="sidebar-link">
        <i class="fas fa-credit-card"></i> Billing
      </a>
      <a href="sales.php" class="sidebar-link">
        <i class="fas fa-chart-line"></i> Sales
      </a>
      <a href="settings.php" class="sidebar-link">
        <i class="fas fa-cog"></i> Settings
      </a>
      <a href="#" onclick="logoutConfirmation()" class="sidebar-link logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <div class="col-md-10 content-area">
      <!-- Success messages -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php
            $success = $_GET['success'];
            if ($success == 'added') echo 'Member successfully added!';
            if ($success == 'updated') echo 'Member successfully updated!';
            if ($success == 'deleted') echo 'Member successfully deleted!';
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <!-- Page Header -->
      <div class="page-header">
        <h2><i class="fas fa-users me-2"></i> Manage Members</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addMemberModal">
          <i class="fas fa-plus me-1"></i> Add New Member
        </button>
      </div>
      
      <!-- Search and Filters -->
      <div class="card card-box mb-4">
        <div class="card-body">
          <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" name="status">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="Suspended" <?php echo $status_filter == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select" name="sort">
                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                <option value="joined_date" <?php echo $sort == 'joined_date' ? 'selected' : ''; ?>>Sort by Date Joined</option>
                <option value="status" <?php echo $sort == 'status' ? 'selected' : ''; ?>>Sort by Status</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" name="direction">
                <option value="ASC" <?php echo $sort_direction == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sort_direction == 'DESC' ? 'selected' : ''; ?>>Descending</option>
              </select>
            </div>
            <div class="col-md-12 text-end">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Apply Filters
              </button>
              <a href="members_crud.php" class="btn btn-secondary">
                <i class="fas fa-sync-alt me-1"></i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Members Table -->
      <div class="card card-box">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-table me-2"></i> Members List
          <span class="badge bg-light text-dark float-end"><?php echo $total_members; ?> Total</span>
        </div>
        <div class="card-body">
          <?php if (count($members) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($members as $index => $member): ?>
                    <tr>
                      <td><?php echo $index + 1; ?></td>
                      <td><?php echo htmlspecialchars($member['name']); ?></td>
                      <td><?php echo htmlspecialchars($member['email']); ?></td>
                      <td><?php echo htmlspecialchars($member['phone']); ?></td>
                      <td><?php echo date('M d, Y', strtotime($member['joined_date'])); ?></td>
                      <td>
                        <?php if ($member['status'] == 'Active'): ?>
                          <span class="badge bg-success status-badge">Active</span>
                        <?php elseif ($member['status'] == 'Inactive'): ?>
                          <span class="badge bg-secondary status-badge">Inactive</span>
                        <?php elseif ($member['status'] == 'Suspended'): ?>
                          <span class="badge bg-danger status-badge">Suspended</span>
                        <?php else: ?>
                          <span class="badge bg-warning status-badge"><?php echo htmlspecialchars($member['status']); ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="action-btns">
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewMemberModal<?php echo $member['id']; ?>">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editMemberModal<?php echo $member['id']; ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteMemberModal<?php echo $member['id']; ?>">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                    
                    <!-- View Member Modal -->
                    <div class="modal fade" id="viewMemberModal<?php echo $member['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                              <i class="fas fa-user me-2"></i> Member Details
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label fw-bold">Name:</label>
                              <p><?php echo htmlspecialchars($member['name']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-bold">Email:</label>
                              <p><?php echo htmlspecialchars($member['email']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-bold">Phone:</label>
                              <p><?php echo htmlspecialchars($member['phone']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-bold">Address:</label>
                              <p><?php echo htmlspecialchars($member['address']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-bold">Date Joined:</label>
                              <p><?php echo date('F d, Y', strtotime($member['joined_date'])); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-bold">Status:</label>
                              <?php if ($member['status'] == 'Active'): ?>
                                <span class="badge bg-success status-badge">Active</span>
                              <?php elseif ($member['status'] == 'Inactive'): ?>
                                <span class="badge bg-secondary status-badge">Inactive</span>
                              <?php elseif ($member['status'] == 'Suspended'): ?>
                                <span class="badge bg-danger status-badge">Suspended</span>
                              <?php else: ?>
                                <span class="badge bg-warning status-badge"><?php echo htmlspecialchars($member['status']); ?></span>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Edit Member Modal -->
                    <div class="modal fade" id="editMemberModal<?php echo $member['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                              <i class="fas fa-edit me-2"></i> Edit Member
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form method="POST" action="">
                            <div class="modal-body">
                              <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                              <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                              </div>
                              <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                              </div>
                              <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>">
                              </div>
                              <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($member['address']); ?></textarea>
                              </div>
                              <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                  <option value="Active" <?php echo $member['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                  <option value="Inactive" <?php echo $member['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                  <option value="Suspended" <?php echo $member['status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
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
                    
                    <!-- Delete Member Modal -->
                    <div class="modal fade" id="deleteMemberModal<?php echo $member['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                              <i class="fas fa-exclamation-triangle me-2"></i> Delete Member
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p>Are you sure you want to delete the member: <strong><?php echo htmlspecialchars($member['name']); ?></strong>?</p>
                            <p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i> This action cannot be undone and will also delete associated user accounts and subscriptions.</p>
                          </div>
                          <form method="POST" action="">
                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="delete_member" class="btn btn-danger">Delete</button>
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
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i> No members found.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fas fa-user-plus me-2"></i> Add New Member
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone">
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
          </div>
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> A user account will be automatically created with the default password: <strong>password123</strong>
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
  
  // Auto close alerts after 3 seconds
  window.setTimeout(function() {
    const alerts = document.getElementsByClassName('alert-dismissible');
    for (let i = 0; i < alerts.length; i++) {
      const alert = new bootstrap.Alert(alerts[i]);
      alert.close();
    }
  }, 3000);
</script>
</body>
</html>