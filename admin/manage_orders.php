<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

if (isset($_GET['mark_paid'])) {
    $order_id = (int)$_GET['mark_paid'];
    $stmt = $conn->prepare("UPDATE orders SET payment_status='paid' WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: manage_orders.php?updated=1");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','preparing','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
    }
    header("Location: manage_orders.php?updated=1");
    exit();
}

if (isset($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    // ON DELETE CASCADE on order_items automatically removes its line items too
    $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: manage_orders.php?deleted=1");
    exit();
}

$orders = $conn->query("
    SELECT o.*, u.name AS customer_name, r.name AS restaurant_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.restaurant_id = r.id
    ORDER BY o.created_at DESC
");

$totalCount = $orders ? $orders->num_rows : 0;
$paymentLabels = ['cod' => 'Cash on Delivery', 'esewa' => 'eSewa'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
            overflow: hidden;
        }
        .admin-avatar img { width: 100%; height: 100%; object-fit: cover; }
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
        <div class="admin-avatar">
        <?php
        $avatarStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $avatarStmt->bind_param("i", $_SESSION['user_id']);
        $avatarStmt->execute();
        $avatarImg = $avatarStmt->get_result()->fetch_assoc()['profile_image'] ?? null;
        ?>
        <?php if ($avatarImg): ?>
            <img src="/food_ordering_system/assets/uploads/<?php echo htmlspecialchars($avatarImg); ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
            <i class="fa-solid fa-user-shield"></i>
        <?php endif; ?>
    </div>
        <h6 class="mt-2 mb-0">Hello, <?php echo htmlspecialchars($_SESSION['name']); ?></h6>
    </div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="admin-nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="manage_users.php" class="admin-nav-link"><i class="fa-solid fa-users"></i> Customers</a>
        <a href="manage_restaurants.php" class="admin-nav-link"><i class="fa-solid fa-store"></i> Restaurants</a>
        <a href="add_restaurant.php" class="admin-nav-link"><i class="fa-solid fa-circle-plus"></i> Add Restaurant</a>
        <a href="add_food.php" class="admin-nav-link"><i class="fa-solid fa-utensils"></i> Add Food</a>
        <a href="manage_foods.php" class="admin-nav-link"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link active"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">All Orders</h3>
        <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Order updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Order deleted.</div>
    <?php endif; ?>

    <?php if ($totalCount === 0): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table bg-white shadow-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Restaurant</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $o['id']; ?></td>
                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($o['restaurant_name']); ?></td>
                        <td>Rs. <?php echo number_format($o['total_amount'], 2); ?></td>
                        <td>
                            <?php echo $paymentLabels[$o['payment_method']] ?? ucfirst($o['payment_method']); ?>
                            <br>
                            <span class="badge bg-<?php echo $o['payment_status']==='paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($o['payment_status']); ?>
                            </span>
                            <?php if ($o['payment_status'] !== 'paid'): ?>
                                <br><a href="?mark_paid=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-success mt-1"
                                       onclick="return confirm('Mark Order #<?php echo $o['id']; ?> as paid? Only do this if payment was actually confirmed.');">
                                    Mark as Paid
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-info"><?php echo ucfirst($o['status']); ?></span></td>
                        <td>
                            <form method="POST" class="d-flex gap-1 mb-1">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach (['pending','confirmed','preparing','delivered','cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $o['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                            </form>
                            <a href="?delete=<?php echo $o['id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete Order #<?php echo $o['id']; ?> permanently? This cannot be undone.');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small">Updating "Order Status" here is what the customer sees change live on their My Orders page — no separate step needed on their end.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>