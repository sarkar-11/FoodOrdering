<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM restaurants WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurant_id = $restaurant['id'];

$food_id = (int)$_GET['id'];

// Fetch the food, making sure it belongs to this restaurant (security check)
$stmt2 = $conn->prepare("SELECT * FROM foods WHERE id = ? AND restaurant_id = ?");
$stmt2->bind_param("ii", $food_id, $restaurant_id);
$stmt2->execute();
$food = $stmt2->get_result()->fetch_assoc();

if (!$food) {
    header("Location: manage_foods.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $status = $_POST['status'];
    $image_name = $food['image']; // keep old image by default

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $image_name = uniqid('food_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/uploads/' . $image_name);
        }
    }

    $update = $conn->prepare("UPDATE foods SET name=?, description=?, price=?, image=?, status=? WHERE id=? AND restaurant_id=?");
    $update->bind_param("ssdssii", $name, $description, $price, $image_name, $status, $food_id, $restaurant_id);
    if ($update->execute()) {
        header("Location: manage_foods.php");
        exit();
    } else {
        $error = "Something went wrong.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4 shadow">
        <h4>Edit Food Item</h4>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Food Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($food['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($food['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $food['price']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="available" <?php echo $food['status']==='available'?'selected':''; ?>>Available</option>
                    <option value="unavailable" <?php echo $food['status']==='unavailable'?'selected':''; ?>>Unavailable</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Replace Image (optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Food</button>
        </form>
    </div>
</div>
</body>
</html>