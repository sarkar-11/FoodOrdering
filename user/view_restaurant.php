<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$restaurant_id = (int)$_GET['id'];
$search = trim($_GET['search'] ?? '');

$stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ? AND status = 'approved'");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header("Location: " . APP_BASE_URL . "/user/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['food_id'])) {
    $food_id = (int)$_POST['food_id'];
    $qty = max(1, (int)$_POST['quantity']);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (!empty($_SESSION['cart']) && $_SESSION['cart_restaurant_id'] != $restaurant_id) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart_restaurant_id'] = $restaurant_id;

    if (isset($_SESSION['cart'][$food_id])) {
        $_SESSION['cart'][$food_id] += $qty;
    } else {
        $_SESSION['cart'][$food_id] = $qty;
    }

    $redirectSearch = $search !== '' ? '&search=' . urlencode($search) : '';
    header("Location: view_restaurant.php?id=" . $restaurant_id . $redirectSearch . "&toast=" . urlencode("Added to cart!") . "&toast_type=success");
    exit();
}

if ($search !== '') {
    $foods = $conn->prepare("SELECT * FROM foods WHERE restaurant_id = ? AND status = 'available' AND name LIKE ?");
    $likeSearch = '%' . $search . '%';
    $foods->bind_param("is", $restaurant_id, $likeSearch);
} else {
    $foods = $conn->prepare("SELECT * FROM foods WHERE restaurant_id = ? AND status = 'available'");
    $foods->bind_param("i", $restaurant_id);
}
$foods->execute();
$foodList = $foods->get_result();

$pageTitle = $restaurant['name'] . " - Menu";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
    <p><?php echo htmlspecialchars($restaurant['description']); ?></p>

    <form method="GET" class="mb-3">
        <input type="hidden" name="id" value="<?php echo $restaurant_id; ?>">
        <div class="input-group" style="max-width:400px;">
            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search this menu..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?>
                <a href="view_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="row mt-3">
        <?php if ($foodList->num_rows === 0): ?>
            <p><?php echo $search !== '' ? 'No dishes match your search.' : 'No food items available right now.'; ?></p>
        <?php endif; ?>

        <?php while ($food = $foodList->fetch_assoc()): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($food['image'])): ?>
                        <img src="<?php echo APP_BASE_URL; ?>/assets/uploads/<?php echo htmlspecialchars($food['image']); ?>"
                             class="card-img-top" style="height:160px; object-fit:cover;"
                             onerror="this.onerror=null; this.parentElement.querySelector('.img-fallback')?.remove(); this.insertAdjacentHTML('afterend', '<div class=\'img-fallback bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center\' style=\'height:160px;\'><i class=\'fa-solid fa-utensils fa-2x text-secondary\'></i></div>'); this.remove();">
                    <?php else: ?>
                        <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height:160px;">
                            <i class="fa-solid fa-utensils fa-2x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($food['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($food['description']); ?></p>
                        <p class="fw-bold">Rs. <?php echo number_format($food['price'], 2); ?></p>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                            <input type="number" name="quantity" value="1" min="1" class="form-control" style="width:80px;">
                            <button type="submit" class="btn btn-success btn-sm">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>