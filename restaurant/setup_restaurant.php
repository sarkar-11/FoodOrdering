<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $image_name = null;

    if (empty($name)) {
        $error = "Restaurant name is required.";
    } else {
        // Handle optional image upload
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
            $stmt = $conn->prepare("INSERT INTO restaurants (user_id, name, description, address, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $name, $description, $address, $image_name);
            if ($stmt->execute()) {
                header("Location: dashboard.php?created=1");
                exit();
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}

$pageTitle = "Setup Restaurant";
include '../includes/header.php';
?>

<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4 shadow">
        <h4>Set Up Your Restaurant</h4>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Restaurant Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Restaurant Image (optional, max 3MB)</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save & Continue</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>