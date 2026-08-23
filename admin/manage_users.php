<?php
include '../includes/db.php';
include '../includes/auth_check.php';


if (isset($_GET['block'])) {
    $id = (int)$_GET['block'];
    $stmt = $conn->prepare("UPDATE users SET status='blocked' WHERE id=? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?updated=1");
    exit();
}
if (isset($_GET['unblock'])) {
    $id = (int)$_GET['unblock'];
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?updated=1");
    exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?deleted=1");
    exit();
}

$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$totalCount = $users ? $users->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
        <a href="manage_users.php" class="admin-nav-link active"><i class="fa-solid fa-users"></i> Customers</a>
        <a href="manage_restaurants.php" class="admin-nav-link"><i class="fa-solid fa-store"></i> Restaurants</a>
        <a href="add_restaurant.php" class="admin-nav-link"><i class="fa-solid fa-circle-plus"></i> Add Restaurant</a>
        <a href="add_food.php" class="admin-nav-link"><i class="fa-solid fa-utensils"></i> Add Food</a>
        <a href="manage_foods.php" class="admin-nav-link"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Users</h3>
        <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">User status updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">User deleted.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table bg-white shadow-sm align-middle">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($u['role']); ?></span></td>
                    <td>
                        <span class="badge bg-<?php echo $u['status']==='active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($u['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <a href="?block=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">Block</a>
                        <?php else: ?>
                            <a href="?unblock=<?php echo $u['id']; ?>" class="btn btn-sm btn-success">Unblock</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this user permanently?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>