<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

if (isset($_GET['mark_paid'])) {
    $order_id = (int)$_GET['mark_paid'];
    $stmt = $conn->prepare("UPDATE orders SET payment_status='paid' WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: manage_orders.php?updated=1");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','preparing','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
    }
    header("Location: manage_orders.php?updated=1");
    exit();
}

if (isset($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    // ON DELETE CASCADE on order_items automatically removes its line items too
    $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: manage_orders.php?deleted=1");
    exit();
}

$orders = $conn->query("
    SELECT o.*, u.name AS customer_name, r.name AS restaurant_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.restaurant_id = r.id
    ORDER BY o.created_at DESC
");

$totalCount = $orders ? $orders->num_rows : 0;
$paymentLabels = ['cod' => 'Cash on Delivery', 'esewa' => 'eSewa', 'khalti' => 'Khalti'];

$pageTitle = "Manage Orders";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">All Orders</h3>
        <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Order status updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Order deleted.</div>
    <?php endif; ?>

    <?php if ($totalCount === 0): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table bg-white shadow-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Restaurant</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $o['id']; ?></td>
                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($o['restaurant_name']); ?></td>
                        <td>Rs. <?php echo number_format($o['total_amount'], 2); ?></td>
                        <td>
                            <?php echo $paymentLabels[$o['payment_method']] ?? ucfirst($o['payment_method']); ?>
                            <br>
                            <span class="badge bg-<?php echo $o['payment_status']==='paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($o['payment_status']); ?>
                            </span>
                            <?php if ($o['payment_status'] !== 'paid'): ?>
                                <br><a href="?mark_paid=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-success mt-1"
                                       onclick="return confirm('Mark Order #<?php echo $o['id']; ?> as paid? Only do this if payment was actually confirmed.');">
                                    Mark as Paid
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-info"><?php echo ucfirst($o['status']); ?></span></td>
                        <td>
                            <form method="POST" class="d-flex gap-1 mb-1">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach (['pending','confirmed','preparing','delivered','cancelled'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $o['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                            </form>
                            <a href="?delete=<?php echo $o['id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete Order #<?php echo $o['id']; ?> permanently? This cannot be undone.');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small">Updating "Order Status" here is what the customer sees change live on their My Orders page — no separate step needed on their end.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>