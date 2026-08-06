<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Placed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
    <h2 class="text-success">✅ Order Placed Successfully!</h2>
    <p>Your order ID is #<?php echo (int)$_GET['id']; ?></p>
    <a href="dashboard.php" class="btn btn-primary">Continue Browsing</a>
    <a href="my_orders.php" class="btn btn-outline-secondary">View My Orders</a>
</div>
</body>
</html>