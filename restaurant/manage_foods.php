<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];

// Get this owner's restaurant
$stmt = $conn->prepare("SELECT id FROM restaurants WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header("Location: setup_restaurant.php");
    exit();
}
$restaurant_id = $restaurant['id'];

// Handle delete
if (isset($_GET['delete'])) {
    $food_id = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM foods WHERE id = ? AND restaurant_id = ?");
    $del->bind_param("ii", $food_id, $restaurant_id);
    $del->execute();
    header("Location: manage_foods.php?deleted=1");
    exit();
}

// Fetch all foods for this restaurant
$foods = $conn->prepare("SELECT * FROM foods WHERE restaurant_id = ? ORDER BY created_at DESC");
$foods->bind_param("i", $restaurant_id);
$foods->execute();
$foodList = $foods->get_result();

$pageTitle = "Manage Foods";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Food Items</h3>
        <a href="add_food.php" class="btn btn-success">+ Add New Food</a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">Food item added successfully!</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Food item updated successfully!</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Food item deleted.</div>
    <?php endif; ?>

    <div class="row">
        <?php if ($foodList->num_rows === 0): ?>
            <p>No food items yet. Add your first one!</p>
        <?php endif; ?>

        <?php while ($food = $foodList->fetch_assoc()): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <?php if ($food['image']): ?>
                        <img src="../assets/uploads/<?php echo htmlspecialchars($food['image']); ?>" class="card-img-top" style="height:180px; object-fit:cover;">
                    <?php else: ?>
                        <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height:180px;">
                            <i class="fa-solid fa-utensils fa-2x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($food['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($food['description']); ?></p>
                        <p class="fw-bold">Rs. <?php echo number_format($food['price'], 2); ?></p>
                        <span class="badge bg-<?php echo $food['status'] === 'available' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($food['status']); ?>
                        </span>
                        <div class="mt-2">
                            <a href="edit_food.php?id=<?php echo $food['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="manage_foods.php?delete=<?php echo $food['id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete this food item?');">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
