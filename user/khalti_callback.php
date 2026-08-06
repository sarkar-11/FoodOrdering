<?php
include '../includes/db.php';
include '../includes/auth_check.php';
include '../includes/khalti_config.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$error = "";
$order = null;

$pidx = $_GET['pidx'] ?? '';
$statusFromUrl = $_GET['status'] ?? '';

if ($pidx === '') {
    $error = "No payment reference received from Khalti.";
} else {
    // Find the matching order by the pidx we saved before redirecting
    $stmt = $conn->prepare("SELECT * FROM orders WHERE transaction_uuid = ? AND user_id = ?");
    $stmt->bind_param("si", $pidx, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        $error = "Order not found for this payment.";
    } elseif ($statusFromUrl === 'User canceled') {
        $error = "Payment was cancelled.";
    } else {
        // Verify independently via Khalti's lookup API rather than trusting
        // the redirect status alone — confirms the payment actually completed
        // on Khalti's side.
        $ch = curl_init(KHALTI_LOOKUP_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["pidx" => $pidx]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Key " . KHALTI_SECRET_KEY,
            "Content-Type: application/json",
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $verify = json_decode($response, true);

        if ($verify && isset($verify['status']) && $verify['status'] === 'Completed') {
            $update = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $update->bind_param("i", $order['id']);
            $update->execute();
        } else {
            $error = "Payment could not be verified with Khalti.";
        }
    }
}

$pageTitle = "Payment Result";
include '../includes/header.php';
?>

<div class="container mt-5 text-center">
    <?php if ($error): ?>
        <h3 class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Payment Issue</h3>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="my_orders.php" class="btn btn-outline-secondary">Go to My Orders</a>
    <?php else: ?>
        <h3 class="text-success"><i class="fa-solid fa-circle-check"></i> Payment Successful!</h3>
        <p>Your Khalti payment for Order #<?php echo $order['id']; ?> has been confirmed.</p>
        <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Receipt</a>
        <a href="dashboard.php" class="btn btn-outline-secondary">Continue Browsing</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>