<div class="col-md-2 sidebar">
    <h5 class="mb-4"></h5>
    <?php
if (isset($_SESSION['user_id'])) {
    echo '<li><a href="admin_dashboard.php">Admin Panel</a></li>';
}
?>



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
