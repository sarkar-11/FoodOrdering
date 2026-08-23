<?php
include '../includes/db.php';
include '../includes/auth_check.php';

$error = "";
$success = "";
$old_name = "";
$old_description = "";
$old_price = "";
$old_restaurant_id = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_id = (int)($_POST['restaurant_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');

    $old_name = $name;
    $old_description = $description;
    $old_price = $price;
    $old_restaurant_id = $restaurant_id;

    if ($restaurant_id <= 0) {
        $error = "Please select a restaurant.";
    } elseif ($name === '') {
        $error = "Food name is required.";
    } elseif ($price === '' || !is_numeric($price) || (float)$price <= 0) {
        $error = "Please enter a valid price greater than 0.";
    } else {
        $check = $conn->prepare("SELECT id FROM restaurants WHERE id = ?");
        $check->bind_param("i", $restaurant_id);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $error = "Selected restaurant not found.";
        } else {
            $image_name = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $maxSizeBytes = 3 * 1024 * 1024;
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    $error = "Image must be jpg, jpeg, png, or webp.";
                } elseif ($_FILES['image']['size'] > $maxSizeBytes) {
                    $error = "Image must be smaller than 3MB.";
                } else {
                    $uploadDir = '../assets/uploads/';
                    if (!is_dir($uploadDir)) {
                        $error = "Upload folder is missing on the server.";
                    } else {
                        $image_name = uniqid('food_') . '.' . $ext;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_name)) {
                            $error = "Failed to save the uploaded image.";
                            $image_name = null;
                        }
                    }
                }
            }

            if ($error === '') {
                $stmt = $conn->prepare("INSERT INTO foods (restaurant_id, name, description, price, image, status) VALUES (?, ?, ?, ?, ?, 'available')");
                $stmt->bind_param("issds", $restaurant_id, $name, $description, $price, $image_name);

                if ($stmt->execute()) {
                    $success = "Food item \"" . htmlspecialchars($name) . "\" was added and is now live on the menu.";
                    $old_name = $old_description = $old_price = "";
                    $old_restaurant_id = "";
                } else {
                    $error = "Database error: could not save the food item.";
                }
            }
        }
    }
}

// Fetch restaurants fresh, right before rendering (avoids stale/rewind issues)
$restaurants = $conn->query("SELECT id, name FROM restaurants ORDER BY name ASC");

// Fetch admin avatar safely
$avatarImg = null;
$avatarStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$avatarStmt->bind_param("i", $_SESSION['user_id']);
$avatarStmt->execute();
$avatarRow = $avatarStmt->get_result()->fetch_assoc();
if ($avatarRow) {
    $avatarImg = $avatarRow['profile_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Food - Admin</title>
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
            <?php if ($avatarImg): ?>
                <img src="/food_ordering_system/assets/uploads/<?php echo htmlspecialchars($avatarImg); ?>" alt="Profile">
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
        <a href="add_food.php" class="admin-nav-link active"><i class="fa-solid fa-utensils"></i> Add Food</a>
        <a href="manage_foods.php" class="admin-nav-link"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-3" style="max-width:550px;">
        <h3 class="mb-0">Add Food Item</h3>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="max-width:550px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="max-width:550px;"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm border-0" style="max-width:550px;">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Restaurant</label>
                <select name="restaurant_id" class="form-select" required>
                    <option value="">-- Select a restaurant --</option>
                    <?php while ($r = $restaurants->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo ($old_restaurant_id == $r['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Food Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($old_name); ?>" required maxlength="150">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($old_description); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($old_price); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image (optional, max 3MB)</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Food Item</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>