<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$search = trim($_GET['search'] ?? '');

$baseQuery = "SELECT r.*, MIN(f.price) AS min_price FROM restaurants r LEFT JOIN foods f ON f.restaurant_id = r.id AND f.status = 'available' WHERE r.status = 'approved'";

if ($search !== '') {
    $stmt = $conn->prepare($baseQuery . " AND r.name LIKE ? GROUP BY r.id ORDER BY r.name ASC");
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($baseQuery . " GROUP BY r.id ORDER BY r.name ASC");
}

$pageTitle = "Browse Restaurants";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h4>
    </div>

    <form method="GET" class="mb-4">
        <div class="input-group" style="max-width:450px;">
            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search restaurants..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?>
                <a href="dashboard.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($search !== ''): ?>
        <p class="text-muted">
            <?php echo $result->num_rows; ?> result<?php echo $result->num_rows !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search); ?>"
        </p>
    <?php endif; ?>

    <div class="row mt-2">
        <?php if ($result->num_rows === 0): ?>
            <p><?php echo $search !== '' ? 'No restaurants match your search.' : 'No restaurants available yet.'; ?></p>
        <?php endif; ?>

        <?php while ($r = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="restaurant-card h-100 shadow-sm">
                    <div class="restaurant-card-img-wrap">
                        <?php if (!empty($r['image'])): ?>
                            <img src="<?php echo APP_BASE_URL; ?>/assets/uploads/<?php echo htmlspecialchars($r['image']); ?>"
                                 alt="<?php echo htmlspecialchars($r['name']); ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="restaurant-placeholder" style="display:none; position:absolute; top:0; left:0;">
                                <i class="fa-solid fa-utensils fa-2x text-secondary"></i>
                            </div>
                        <?php else: ?>
                            <div class="restaurant-placeholder">
                                <i class="fa-solid fa-utensils fa-2x text-secondary"></i>
                            </div>
                        <?php endif; ?>

                        <?php if ($r['min_price'] !== null): ?>
                            <span class="restaurant-price-badge">From Rs. <?php echo number_format($r['min_price'], 0); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="restaurant-card-body">
                        <h5 class="restaurant-card-title"><?php echo htmlspecialchars($r['name']); ?></h5>
                        <p class="restaurant-card-desc"><?php echo htmlspecialchars($r['description']); ?></p>
                        <a href="view_restaurant.php?id=<?php echo $r['id']; ?>" class="btn btn-primary btn-sm w-100">View Menu</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>