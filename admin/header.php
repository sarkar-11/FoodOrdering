<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Admin' : 'Admin Panel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="/food_ordering_system/assets/css/style.css" rel="stylesheet">
    <link href="/food_ordering_system/assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-sidebar">
    <div class="admin-sidebar-profile">
        <div class="admin-avatar">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <h6 class="mt-2 mb-0">Hello, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h6>
    </div>

    <nav class="admin-nav">
        <a href="/food_ordering_system/admin/dashboard.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="/food_ordering_system/admin/manage_users.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Customers
        </a>
        <a href="/food_ordering_system/admin/manage_restaurants.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_restaurants.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i> Restaurants
        </a>
        <a href="/food_ordering_system/admin/add_restaurant.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'add_restaurant.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-plus"></i> Add Restaurant
        </a>
        <a href="/food_ordering_system/admin/manage_orders.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_orders.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-receipt"></i> Orders
        </a>
        <a href="/food_ordering_system/auth/logout.php" class="admin-nav-link mt-auto">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>
</div>

<div class="admin-content">