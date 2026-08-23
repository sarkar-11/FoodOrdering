<?php
include '../includes/db.php';
include '../includes/auth_check.php';

if (isset($_GET['delete'])) {
    $food_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->bind_param("i", $food_id);
    $stmt->execute();

    // Preserve the current filter (restaurant + search) after deleting,
    // instead of always bouncing back to "All Restaurants"
    $redirectRestaurantId = (int)($_GET['restaurant_id'] ?? 0);
    $redirectSearch = trim($_GET['search'] ?? '');

    $redirectUrl = "manage_foods.php?deleted=1";
    if ($redirectRestaurantId > 0) {
        $redirectUrl .= "&restaurant_id=" . $redirectRestaurantId;
    }
    if ($redirectSearch !== '') {
        $redirectUrl .= "&search=" . urlencode($redirectSearch);
    }

    header("Location: " . $redirectUrl);
    exit();
}

$search = trim($_GET['search'] ?? '');
$filterRestaurantId = (int)($_GET['restaurant_id'] ?? 0);

$restaurantsList = $conn->query("SELECT id, name FROM restaurants ORDER BY name ASC");
$selectedRestaurantName = "";

$conditions = [];
$params = [];
$types = "";

if ($filterRestaurantId > 0) {
    $conditions[] = "f.restaurant_id = ?";
    $params[] = $filterRestaurantId;
    $types .= "i";

    $nameCheck = $conn->prepare("SELECT name FROM restaurants WHERE id = ?");
    $nameCheck->bind_param("i", $filterRestaurantId);
    $nameCheck->execute();
    $nameResult = $nameCheck->get_result()->fetch_assoc();
    $selectedRestaurantName = $nameResult['name'] ?? '';
}

if ($search !== '') {
    $conditions[] = "(f.name LIKE ? OR r.name LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "ss";
}

$sql = "SELECT f.*, r.name AS restaurant_name FROM foods f JOIN restaurants r ON f.restaurant_id = r.id";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY r.name ASC, f.name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $foods = $stmt->get_result();
} else {
    $foods = $conn->query($sql);
}

$totalCount = $foods->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Foods - Admin</title>
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
        <a href="manage_foods.php" class="admin-nav-link active"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0">
            Manage Foods
            <?php if ($selectedRestaurantName): ?>
                <span class="text-muted fs-6">&mdash; <?php echo htmlspecialchars($selectedRestaurantName); ?></span>
            <?php endif; ?>
        </h3>
        <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Food item deleted.</div>
    <?php endif; ?>

    <form method="GET" class="mb-3 d-flex flex-wrap gap-2">
        <select name="restaurant_id" class="form-select" style="max-width:260px;" onchange="this.form.submit()">
            <option value="0">All Restaurants</option>
            <?php while ($r = $restaurantsList->fetch_assoc()): ?>
                <option value="<?php echo $r['id']; ?>" <?php echo $filterRestaurantId == $r['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($r['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div class="input-group" style="max-width:350px;">
            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search dish name..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>

        <?php if ($search !== '' || $filterRestaurantId > 0): ?>
            <a href="manage_foods.php" class="btn btn-outline-secondary">Clear Filters</a>
        <?php endif; ?>
    </form>

    <?php if ($totalCount === 0): ?>
        <p>No food items found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table bg-white shadow-sm align-middle">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Dish</th>
                        <th>Restaurant</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($f = $foods->fetch_assoc()): ?>
                    <tr>
                        <td style="width:60px;">
                            <?php if (!empty($f['image'])): ?>
                                <img src="/food_ordering_system/assets/uploads/<?php echo htmlspecialchars($f['image']); ?>"
                                     style="width:44px; height:44px; object-fit:cover; border-radius:8px;"
                                     onerror="this.style.display='none';">
                            <?php else: ?>
                                <div style="width:44px; height:44px; border-radius:8px; background:#f1f1f1; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-utensils text-secondary"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                        <td><?php echo htmlspecialchars($f['restaurant_name']); ?></td>
                        <td>Rs. <?php echo number_format($f['price'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $f['status']==='available' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($f['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $f['id']; ?><?php echo $filterRestaurantId > 0 ? '&restaurant_id='.$filterRestaurantId : ''; ?><?php echo $search !== '' ? '&search='.urlencode($search) : ''; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete \'<?php echo htmlspecialchars($f['name'], ENT_QUOTES); ?>\' from <?php echo htmlspecialchars($f['restaurant_name'], ENT_QUOTES); ?>?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>