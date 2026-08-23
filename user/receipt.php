<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

// Fetch order, making sure it belongs to this user (security check)
$stmt = $conn->prepare("SELECT o.*, r.name AS restaurant_name, r.address AS restaurant_address, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN restaurants r ON o.restaurant_id = r.id JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

// Fetch order items
$itemsStmt = $conn->prepare("SELECT oi.*, f.name AS food_name FROM order_items oi JOIN foods f ON oi.food_id = f.id WHERE oi.order_id = ?");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

$paymentLabels = ['cod' => 'Cash on Delivery', 'esewa' => 'eSewa'];

$pageTitle = "Receipt - Order #" . $order['id'];
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm p-4" style="max-width:700px; margin:0 auto;">

        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
            <div>
                <h4 class="mb-0"><i class="fa-solid fa-utensils me-2"></i>DokoBites</h4>
                <small class="text-muted">Order Receipt</small>
            </div>
            <div class="text-end">
                <strong>Order #<?php echo $order['id']; ?></strong><br>
                <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></small>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <h6 class="text-muted">Billed To</h6>
                <p class="mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p class="mb-0 text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></p>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted">Restaurant</h6>
                <p class="mb-0"><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                <p class="mb-0 text-muted"><?php echo htmlspecialchars($order['restaurant_address']); ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['food_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">Rs. <?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-end">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end fw-bold">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-3 pt-3 border-top">
            <div class="col-6">
                <h6 class="text-muted mb-1">Payment Method</h6>
                <p class="mb-0"><?php echo $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']); ?></p>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted mb-1">Payment Status</h6>
                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($order['payment_status']); ?>
                </span>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-6">
                <h6 class="text-muted mb-1">Order Status</h6>
                <span class="badge bg-info"><?php echo ucfirst($order['status']); ?></span>
            </div>
        </div>

        <?php if (!empty($order['delivery_address'])): ?>
        <div class="mt-3 pt-3 border-top">
            <h6 class="text-muted mb-1"><i class="fa-solid fa-location-dot me-1"></i>Delivery Address</h6>
            <p class="mb-0"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($order['notes'])): ?>
        <div class="mt-3 pt-3 border-top">
            <h6 class="text-muted mb-1">Delivery Notes</h6>
            <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($order['notes']); ?>"</p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-print me-1"></i> Print Receipt
            </button>
            <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">Back to My Orders</a>
        </div>

    </div>
</div>

<style>
    @media print {
        .navbar, .custom-footer, .no-print { display: none !important; }
    }
</style>

<?php include '../includes/footer.php'; ?>