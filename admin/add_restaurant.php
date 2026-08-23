<?php
include '../includes/db.php';
include '../includes/auth_check.php';


$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $owner_password = $_POST['owner_password'] ?? '';
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($owner_name) || empty($owner_email) || empty($owner_password) || empty($restaurant_name)) {
        $error = "Owner name, email, password, and restaurant name are all required.";
    } elseif (strlen($owner_password) < 6) {
        $error = "Owner password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $owner_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "That owner email is already registered.";
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
                        $image_name = uniqid('resto_') . '.' . $ext;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_name)) {
                            $error = "Failed to save the uploaded image.";
                            $image_name = null;
                        }
                    }
                }
            }

            if ($error === '') {
                $conn->begin_transaction();
                try {
                    $hashed = password_hash($owner_password, PASSWORD_DEFAULT);
                    $role = 'restaurant';
                    $userStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
                    $userStmt->bind_param("ssss", $owner_name, $owner_email, $hashed, $role);
                    $userStmt->execute();
                    $new_user_id = $conn->insert_id;

                    $restoStmt = $conn->prepare("INSERT INTO restaurants (user_id, name, description, address, image, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                    $restoStmt->bind_param("issss", $new_user_id, $restaurant_name, $description, $address, $image_name);
                    $restoStmt->execute();

                    $conn->commit();
                    $success = "Restaurant \"" . htmlspecialchars($restaurant_name) . "\" was created and approved. Owner can log in with email: " . htmlspecialchars($owner_email);
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Something went wrong while creating the restaurant. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Restaurant - Admin</title>
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
        <a href="add_restaurant.php" class="admin-nav-link active"><i class="fa-solid fa-circle-plus"></i> Add Restaurant</a>
        <a href="add_food.php" class="admin-nav-link"><i class="fa-solid fa-utensils"></i> Add Food</a>
        <a href="manage_foods.php" class="admin-nav-link"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div style="max-width:600px;">
        <h3 class="mb-3">Add New Restaurant</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <div class="card p-4 shadow-sm border-0">
                <form method="POST" enctype="multipart/form-data">
                    <h6 class="text-muted mb-2">Owner Account</h6>
                    <div class="mb-3">
                        <label class="form-label">Owner Full Name</label>
                        <input type="text" name="owner_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner Email</label>
                        <input type="email" name="owner_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner Password</label>
                        <input type="password" name="owner_password" class="form-control" required minlength="6">
                        <small class="text-muted">Share this with the restaurant owner so they can log in.</small>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-2">Restaurant Details</h6>
                    <div class="mb-3">
                        <label class="form-label">Restaurant Name</label>
                        <input type="text" name="restaurant_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Restaurant Image (optional, max 3MB)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create Restaurant</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>