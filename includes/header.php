<?php
if (!defined('APP_BASE_URL')) {
    require_once __DIR__ . '/config.php';
}
// Ensure a session is started so header can safely read $_SESSION.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - DokoBites' : 'DokoBites'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo APP_BASE_URL; ?>/index.php">
            <i class="fa-solid fa-utensils me-2"></i>DokoBites
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><span class="nav-link">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span></li>

                    <?php if ($_SESSION['role'] === 'user'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/dashboard.php">Restaurants</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/my_orders.php">My Orders</a></li>
                    <?php elseif ($_SESSION['role'] === 'restaurant'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/restaurant/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?php echo APP_BASE_URL; ?>/auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?php echo APP_BASE_URL; ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>