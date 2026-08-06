<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*, r.name AS restaurant_name 
    FROM orders o 
    JOIN restaurants r ON o.restaurant_id = r.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

$paymentLabels = ['cod' => 'Cash on Delivery', 'esewa' => 'eSewa', 'khalti' => 'Khalti'];

$pageTitle = "My Orders";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h3>My Orders</h3>
    <?php if ($orders->num_rows === 0): ?>
        <p>You haven't placed any orders yet.</p>
    <?php endif; ?>

    <?php while ($o = $orders->fetch_assoc()): ?>
        <div class="card mb-2 p-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <strong>Order #<?php echo $o['id']; ?></strong> — <?php echo htmlspecialchars($o['restaurant_name']); ?>
                    <br><small class="text-muted"><?php echo $o['created_at']; ?></small>
                    <br><small>
                        Payment: <?php echo $paymentLabels[$o['payment_method']] ?? ucfirst($o['payment_method']); ?>
                        (<span class="badge bg-<?php echo $o['payment_status']==='paid'?'success':'warning'; ?>"><?php echo ucfirst($o['payment_status']); ?></span>)
                    </small>
                </div>
                <div class="text-end">
                    <span class="badge bg-info"><?php echo ucfirst($o['status']); ?></span>
                    <br>Rs. <?php echo number_format($o['total_amount'], 2); ?>
                    <br><a href="track_order.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-success mt-1">Track Order</a>
                    <a href="receipt.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary mt-1">View Receipt</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>