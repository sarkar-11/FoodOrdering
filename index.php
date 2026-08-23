<?php
include 'includes/db.php';
include 'includes/config.php';
$pageTitle = "Home";

$featured = $conn->query("SELECT * FROM restaurants WHERE status='approved' ORDER BY created_at DESC LIMIT 6");
if (!$featured) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DokoBites - Newari & Local Kitchens, Delivered</title>
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
                    <li class="nav-item">
                        <span class="nav-link">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </li>

                    <?php if ($_SESSION['role'] === 'user'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/dashboard.php">Restaurants</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/user/my_orders.php">My Orders</a></li>
                    <?php elseif ($_SESSION['role'] === 'restaurant'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/restaurant/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="btn btn-sm btn-light ms-lg-2" href="<?php echo APP_BASE_URL; ?>/auth/logout.php">Logout</a>
                    </li>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE_URL; ?>/auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?php echo APP_BASE_URL; ?>/auth/register.php">Register</a></li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <span class="hero-eyebrow"><i class="fa-solid fa-location-dot"></i> Experience the Best of Newari & Local Kitchens •</span>
        <h1>Experience the Taste of Food,<br><em>One Delicious Bite at a Time</em></h1>
       <p class="lead">Discover authentic Newari and Nepali dishes, freshly prepared by trusted local restaurants and delivered hot to your doorstep.</p>

        <div class="hero-actions">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="<?php echo APP_BASE_URL; ?>/auth/register.php" class="btn btn-brick">Get Started</a>
                <a href="<?php echo APP_BASE_URL; ?>/auth/login.php" class="btn btn-outline-ink">I have an account</a>
            <?php else: ?>
                <a href="<?php echo APP_BASE_URL; ?>/user/dashboard.php" class="btn btn-brick">Browse Restaurants</a>
                
            <?php endif; ?>
        </div>

        <div class="khaja-row">
            <div class="khaja-item"><span class="khaja-dot"><i class="fa-solid fa-bowl-food"></i></span> Local kitchens</div>
            <div class="khaja-item"><span class="khaja-dot"><i class="fa-solid fa-bolt"></i></span> Fast delivery</div>
            <div class="khaja-item"><span class="khaja-dot"><i class="fa-solid fa-wallet"></i></span> eSewa payments</div>

        </div>
    </div>
</section>

<div class="container mt-5 pt-3">
    <span class="section-label">Handpicked for you</span>
    <h3 class="section-heading">Featured Restaurants</h3>

    <div class="row">
        <?php if ($featured->num_rows === 0): ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fa-solid fa-kitchen-set fa-2x mb-3"></i>
                    <p class="mb-0">No restaurants yet — check back soon!</p>
                </div>
            </div>
        <?php endif; ?>

        <?php while ($r = $featured->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="food-card">
                    <?php if ($r['image']): ?>
                        <img src="<?php echo APP_BASE_URL; ?>/assets/uploads/<?php echo htmlspecialchars($r['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($r['name']); ?>">
                    <?php else: ?>
                        <div class="food-card-placeholder">
                            <i class="fa-solid fa-utensils fa-2x"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($r['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($r['description']); ?></p>
                        <a href="<?php echo isset($_SESSION['user_id']) ? APP_BASE_URL . '/user/view_restaurant.php?id=' . $r['id'] : APP_BASE_URL . '/auth/login.php'; ?>" class="btn btn-primary btn-sm">View Menu</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<footer class="custom-footer mt-5 py-4">
    <div class="container text-center">
        <p class="mb-1"><i class="fa-solid fa-utensils"></i> DokoBites</p>
        <p class="mb-1">© 2026 DokoBites. All rights reserved.</p>
        <p class="mb-0">Discover amazing flavors, order with ease, and enjoy every meal with DokoBites.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>