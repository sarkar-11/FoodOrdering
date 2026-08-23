<?php
include '../includes/db.php';
include '../includes/auth_check.php';


$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSizeBytes = 2 * 1024 * 1024;
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Image must be jpg, jpeg, png, or webp.";
        } elseif ($_FILES['photo']['size'] > $maxSizeBytes) {
            $error = "Image must be smaller than 2MB.";
        } else {
            $uploadDir = '../assets/uploads/';
            if (!is_dir($uploadDir)) {
                $error = "Upload folder is missing on the server.";
            } else {
                $image_name = uniqid('admin_') . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $image_name)) {
                    $update = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $update->bind_param("si", $image_name, $user_id);
                    $update->execute();

                    $_SESSION['profile_image'] = $image_name;
                    $admin['profile_image'] = $image_name;
                    $success = "Profile photo updated.";
                } else {
                    $error = "Failed to save the uploaded image.";
                }
            }
        }
    } else {
        $error = "Please choose an image to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Admin</title>
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
        .admin-sidebar-profile a { color: #ffb27a; font-size: 0.78rem; text-decoration: none; }
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
            <?php if (!empty($admin['profile_image'])): ?>
                <img src="/food_ordering_system/assets/uploads/<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Profile">
            <?php else: ?>
                <i class="fa-solid fa-user-shield"></i>
            <?php endif; ?>
        </div>
        <h6 class="mt-2 mb-0">Hello, <?php echo htmlspecialchars($_SESSION['name']); ?></h6>
        <a href="profile.php">Edit Photo</a>
    </div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="admin-nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="manage_users.php" class="admin-nav-link"><i class="fa-solid fa-users"></i> Customers</a>
        <a href="manage_restaurants.php" class="admin-nav-link"><i class="fa-solid fa-store"></i> Restaurants</a>
        <a href="add_restaurant.php" class="admin-nav-link"><i class="fa-solid fa-circle-plus"></i> Add Restaurant</a>
        <a href="add_food.php" class="admin-nav-link"><i class="fa-solid fa-utensils"></i> Add Food</a>
        <a href="manage_foods.php" class="admin-nav-link"><i class="fa-solid fa-list"></i> Manage Foods</a>
        <a href="manage_orders.php" class="admin-nav-link"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="analytics.php" class="admin-nav-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <a href="profile.php" class="admin-nav-link active"><i class="fa-solid fa-id-badge"></i> My Profile</a>
        <a href="../auth/logout.php" class="admin-nav-link mt-auto"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div style="max-width:450px;">
        <h3 class="mb-3">My Profile</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm border-0 text-center">
            <div class="admin-avatar mx-auto mb-3" style="width:100px; height:100px; font-size:2.5rem;">
                <?php if (!empty($admin['profile_image'])): ?>
                    <img src="/food_ordering_system/assets/uploads/<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fa-solid fa-user-shield text-white"></i>
                <?php endif; ?>
            </div>
            <h5><?php echo htmlspecialchars($admin['name']); ?></h5>
            <p class="text-muted small"><?php echo htmlspecialchars($admin['email']); ?></p>

            <form method="POST" enctype="multipart/form-data" class="mt-3 text-start">
                <label class="form-label">Upload New Photo (jpg/png/webp, max 2MB)</label>
                <input type="file" name="photo" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" required>
                <button type="submit" class="btn btn-primary w-100">Update Photo</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>