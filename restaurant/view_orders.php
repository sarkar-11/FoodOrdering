<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('restaurant');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM restaurants WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header("Location: setup_restaurant.php");
    exit();
}
$restaurant_id = $restaurant['id'];

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','preparing','delivered','cancelled'];

    if (in_array($status, $allowed)) {
        // Ownership check: only allow updating orders that belong to THIS restaurant
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("sii", $status, $order_id, $restaurant_id);
        $stmt->execute();
    }
    header("Location: view_orders.php?updated=1");
    exit();
}

$ordersStmt = $conn->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.restaurant_id = ?
    ORDER BY o.created_at DESC
");
$ordersStmt->bind_param("i", $restaurant_id);
$ordersStmt->execute();
$orders = $ordersStmt->get_result();

$paymentLabels = ['cod' => 'Cash on Delivery', 'esewa' => 'eSewa', 'khalti' => 'Khalti'];

$pageTitle = "Orders";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h3>Incoming Orders</h3>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Order status updated.</div>
    <?php endif; ?>

    <?php if ($orders->num_rows === 0): ?>
        <p>No orders yet.</p>
    <?php endif; ?>

    <?php while ($o = $orders->fetch_assoc()): ?>
        <div class="card mb-3 p-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <strong>Order #<?php echo $o['id']; ?></strong> — <?php echo htmlspecialchars($o['customer_name']); ?>
                    <br><small class="text-muted"><?php echo $o['created_at']; ?></small>
                    <br><small>
                        <?php echo $paymentLabels[$o['payment_method']] ?? ucfirst($o['payment_method']); ?>
                        (<span class="badge bg-<?php echo $o['payment_status']==='paid'?'success':'warning'; ?>"><?php echo ucfirst($o['payment_status']); ?></span>)
                    </small>
                </div>
                <div class="text-end">
                    <span class="badge bg-info"><?php echo ucfirst($o['status']); ?></span>
                    <br>Rs. <?php echo number_format($o['total_amount'], 2); ?>
                </div>
            </div>

            <?php if (!empty($o['delivery_address'])): ?>
                <div class="mt-2 mb-0 text-muted small">
                    <i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($o['delivery_address']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($o['notes'])): ?>
                <div class="alert alert-warning mt-2 mb-0 py-2">
                    <i class="fa-solid fa-note-sticky me-1"></i>
                    <strong>Customer note:</strong> "<?php echo htmlspecialchars($o['notes']); ?>"
                </div>
            <?php endif; ?>

            <?php if ($o['status'] !== 'delivered' && $o['status'] !== 'cancelled'): ?>
                <form method="POST" class="d-flex gap-2 mt-2">
                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width:200px;">
                        <?php foreach (['pending','confirmed','preparing','delivered','cancelled'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $o['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update Status</button>
                </form>
            <?php else: ?>
                <p class="text-muted small mt-2 mb-0">This order is <?php echo $o['status']; ?> and can no longer be changed.</p>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>