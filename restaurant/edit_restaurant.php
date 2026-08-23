<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM restaurants WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header("Location: " . APP_BASE_URL . "/restaurant/setup_restaurant.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $image_name = $restaurant['image']; // keep old image by default

    if (empty($name)) {
        $error = "Restaurant name is required.";
    } else {
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
                    $newImageName = uniqid('resto_') . '.' . $ext;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName)) {
                        $error = "Failed to save the uploaded image.";
                    } else {
                        $image_name = $newImageName;
                    }
                }
            }
        }

        if ($error === '') {
            $update = $conn->prepare("UPDATE restaurants SET name = ?, description = ?, address = ?, image = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("ssssii", $name, $description, $address, $image_name, $restaurant['id'], $user_id);
            if ($update->execute()) {
                $success = "Restaurant info updated.";
                $restaurant['name'] = $name;
                $restaurant['description'] = $description;
                $restaurant['address'] = $address;
                $restaurant['image'] = $image_name;
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}

$pageTitle = "Edit Restaurant";
include '../includes/header.php';
?>

<div class="container mt-5 mb-5" style="max-width:500px;">
    <div class="card p-4 shadow">
        <h4>Edit Restaurant Info</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($restaurant['image'])): ?>
            <img src="<?php echo APP_BASE_URL; ?>/assets/uploads/<?php echo htmlspecialchars($restaurant['image']); ?>"
                 alt="Current image" class="mb-3" style="max-width:100%; border-radius:8px;">
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Restaurant Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($restaurant['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($restaurant['address'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Replace Image (optional, max 3MB)</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
        <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
