<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

// Handle status change
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE restaurants SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_restaurants.php?updated=1");
    exit();
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $conn->prepare("UPDATE restaurants SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_restaurants.php?updated=1");
    exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_restaurants.php?deleted=1");
    exit();
}

// Fetch all restaurants with their owner's email
$restaurants = $conn->query("
    SELECT r.*, u.email AS owner_email
    FROM restaurants r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");

$totalCount = $restaurants ? $restaurants->num_rows : 0;

$pageTitle = "Manage Restaurants";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Restaurants</h3>
        <div>
            <a href="add_restaurant.php" class="btn btn-success btn-sm">+ Add Restaurant</a>
            <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Restaurant status updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Restaurant deleted.</div>
    <?php endif; ?>

    <?php if ($totalCount === 0): ?>
        <p>No restaurants registered yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table bg-white shadow-sm align-middle">
                <thead>
                    <tr><th>Name</th><th>Owner Email</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while ($r = $restaurants->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                        <td><?php echo htmlspecialchars($r['owner_email']); ?></td>
                        <td>
                            <span class="badge bg-<?php
                                echo $r['status']==='approved' ? 'success' : ($r['status']==='rejected' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($r['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['status'] !== 'approved'): ?>
                                <a href="?approve=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                            <?php endif; ?>
                            <?php if ($r['status'] !== 'rejected'): ?>
                                <a href="?reject=<?php echo $r['id']; ?>" class="btn btn-sm btn-warning">Reject</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this restaurant and all its foods?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>