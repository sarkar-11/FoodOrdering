<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$pageTitle = "Payment Failed";
include '../includes/header.php';
?>

<div class="container mt-5 text-center">
    <h3 class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Payment Failed or Cancelled</h3>
    <p>Your eSewa payment was not completed. Your order is saved and marked unpaid — you can try paying again from My Orders.</p>
    <a href="my_orders.php" class="btn btn-primary">Go to My Orders</a>
    <a href="dashboard.php" class="btn btn-outline-secondary">Continue Browsing</a>
</div>

<?php include '../includes/footer.php'; ?>