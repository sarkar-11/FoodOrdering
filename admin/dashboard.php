<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

$totalUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
$totalRestaurants = $conn->query("SELECT COUNT(*) c FROM restaurants")->fetch_assoc()['c'];
$pendingRestaurants = $conn->query("SELECT COUNT(*) c FROM restaurants WHERE status='pending'")->fetch_assoc()['c'];
$totalOrders = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$paidRevenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE payment_status='paid'")->fetch_assoc()['t'];
$pendingPayments = $conn->query("SELECT COUNT(*) c FROM orders WHERE payment_status='unpaid'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; margin: 0; }
        .admin-sidebar {
            position: fixed; top: 0; left: 0; width: 240px; height: 100vh;
            background: #2e2422; color: #fff; display: flex; flex-direction: column;
            padding: 24px 16px; z-index: 1000; overflow-y: auto;
        }
        .admin-sidebar-profile { text-align: center; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 16px; }
        .admin-avatar {
            width: 64px; height: 64px; border-radius: 50%; background: #4a3d3a;
            display: flex; align-items: center; justify-content: center; margin: 0 auto;
            font-size: 1.6rem; color: #fff; border: 2px solid rgba(255,255,255,0.15);
        }
        .admin-sidebar-profile h6 { color: #fff; font-weight: 600; }
        .admin-nav { display: flex; flex-direction: column; gap: 4px; flex: 1; }
        .admin-nav-link {
            color: #d8cfcc; text-decoration: none; padding: 11px 14px; border-radius: 8px;
            font-size: 0.92rem; display: flex; align-items: center; gap: 10px;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .admin-nav-link i { width: 18px; text-align: center; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .admin-nav-link.active { background: #d9480f; color: #fff; font-weight: 600; }
        .admin-nav-link.mt-auto { margin-top: auto; }
        .admin-content { margin-left: 240px; padding: 28px 32px; min-height: 100vh; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 100%; height: auto; position: relative; flex-direction: row; flex-wrap: wrap; align-items: center; padding: 12px; }
            .admin-sidebar-profile { display: none; }
            .admin-nav { flex-direction: row; flex-wrap: wrap; gap: 6px; }
            .admin-nav-link { padding: 8px 10px; font-size: 0.8rem; }
            .admin-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="admin-sidebar">
    <div class="admin-sidebar-profile">
        <div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div>
        <h6 class="mt-2 mb-0">Hello, <?php echo htmlspecialchars($_SESSION['name']); ?></h6>
    </div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="admin-nav-link active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="manage_users.php" class="admin-nav-link"><i class="fa-solid fa-users"></i> Customers</a>
        <a href="manage_restaurants.php" class="admin-nav-link"><i class="fa-solid fa-store"></i> Restaurants</a>
        <a href="add_restaurant.php" class="admin-nav-link"><i class="fa-solid fa-circle-plus"></i> Add Restaurant</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <h3 class="mb-4">Dashboard</h3>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Total Customers</h6>
                <h3><?php echo $totalUsers; ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Total Restaurants</h6>
                <h3><?php echo $totalRestaurants; ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Pending Approvals</h6>
                <h3 class="text-warning"><?php echo $pendingRestaurants; ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Total Orders</h6>
                <h3><?php echo $totalOrders; ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Revenue (Paid Orders)</h6>
                <h3 class="text-success">Rs. <?php echo number_format($paidRevenue, 2); ?></h3>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-3 text-center shadow-sm border-0">
                <h6 class="text-muted">Pending Payments</h6>
                <h3 class="text-danger"><?php echo $pendingPayments; ?></h3>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>