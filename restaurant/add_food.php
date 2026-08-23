<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];

// Get this owner's restaurant — required before any food can be added
$stmt = $conn->prepare("SELECT id FROM restaurants WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

    if (!$restaurant) {
        header("Location: " . APP_BASE_URL . "/restaurant/setup_restaurant.php");
        exit();
    }
$restaurant_id = $restaurant['id'];

$error = "";
$success = "";

// Keep submitted values so the form isn't wiped out if validation fails
$old_name = "";
$old_description = "";
$old_price = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');

    $old_name = $name;
    $old_description = $description;
    $old_price = $price;

    // ---- Validation ----
    if ($name === '') {
        $error = "Food name is required.";
    } elseif (strlen($name) > 150) {
        $error = "Food name is too long (max 150 characters).";
    } elseif ($price === '' || !is_numeric($price)) {
        $error = "Please enter a valid price.";
    } elseif ((float)$price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        // Any upload error other than "no file selected"
        $error = "There was a problem uploading the image. Try a smaller file.";
    } else {
        $image_name = null;

        // ---- Handle image upload (optional) ----
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSizeBytes = 3 * 1024 * 1024; // 3MB limit
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = "Image must be jpg, jpeg, png, or webp.";
            } elseif ($_FILES['image']['size'] > $maxSizeBytes) {
                $error = "Image must be smaller than 3MB.";
            } else {
                $uploadDir = '../assets/uploads/';

                // Make sure the folder actually exists before trying to save into it
                if (!is_dir($uploadDir)) {
                    $error = "Upload folder is missing on the server. Create assets/uploads/ and try again.";
                } else {
                    $image_name = uniqid('food_') . '.' . $ext;
                    $moved = move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_name);
                    if (!$moved) {
                        $error = "Failed to save the uploaded image. Check folder permissions.";
                        $image_name = null;
                    }
                }
            }
        }

        // ---- Insert into database only if no errors so far ----
        if ($error === '') {
            $stmt2 = $conn->prepare("INSERT INTO foods (restaurant_id, name, description, price, image, status) VALUES (?, ?, ?, ?, ?, 'available')");
            $stmt2->bind_param("issds", $restaurant_id, $name, $description, $price, $image_name);

            if ($stmt2->execute()) {
                header("Location: " . APP_BASE_URL . "/restaurant/manage_foods.php?added=1");
                exit();
            } else {
                $error = "Database error: could not save the food item. Please try again.";
            }
        }
    }
}

$pageTitle = "Add Food";
include '../includes/header.php';
?>

<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4 shadow">
        <h4>Add New Food Item</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label class="form-label">Food Name</label>
                <input type="text" name="name" class="form-control"
                       value="<?php echo htmlspecialchars($old_name); ?>" required maxlength="150">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($old_description); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control"
                       value="<?php echo htmlspecialchars($old_price); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image (optional, max 3MB)</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Food</button>
            <a href="manage_foods.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
