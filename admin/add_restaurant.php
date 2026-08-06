<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $owner_password = $_POST['owner_password'] ?? '';
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // ---- Validation ----
    if (empty($owner_name) || empty($owner_email) || empty($owner_password) || empty($restaurant_name)) {
        $error = "Owner name, email, password, and restaurant name are all required.";
    } elseif (strlen($owner_password) < 6) {
        $error = "Owner password must be at least 6 characters.";
    } else {
        // Check email isn't already used
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $owner_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "That owner email is already registered.";
        } else {
            $image_name = null;

            // Handle optional restaurant image upload
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
                    // Create the owner account
                    $hashed = password_hash($owner_password, PASSWORD_DEFAULT);
                    $role = 'restaurant';
                    $userStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
                    $userStmt->bind_param("ssss", $owner_name, $owner_email, $hashed, $role);
                    $userStmt->execute();
                    $new_user_id = $conn->insert_id;

                    // Create the restaurant — pre-approved since admin is adding it directly
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

$pageTitle = "Add Restaurant";
include '../includes/header.php';
?>

<div class="container mt-4" style="max-width:600px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Add New Restaurant</h3>
        <a href="manage_restaurants.php" class="btn btn-outline-secondary btn-sm">Back to Restaurants</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <div class="card p-4 shadow-sm">
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

<?php include '../includes/footer.php'; ?>