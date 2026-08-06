<?php
include '../includes/db.php';
include '../includes/auth_check.php';
include '../includes/esewa_config.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$error = "";
$order = null;

// eSewa sends back a base64-encoded JSON string in the "data" query param
$encodedData = $_GET['data'] ?? '';

if ($encodedData === '') {
    $error = "No payment data received from eSewa.";
} else {
    $decoded = json_decode(base64_decode($encodedData), true);

    if (!$decoded || !isset($decoded['transaction_uuid'])) {
        $error = "Could not read payment response from eSewa.";
    } else {
        $transaction_uuid = $decoded['transaction_uuid'];
        $total_amount = $decoded['total_amount'] ?? '';

        // Look up the matching order in our own database
        $stmt = $conn->prepare("SELECT * FROM orders WHERE transaction_uuid = ? AND user_id = ?");
        $stmt->bind_param("si", $transaction_uuid, $user_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            $error = "Order not found for this transaction.";
        } else {
            // Double-check with eSewa's own status API rather than trusting the
            // redirect data alone — this confirms the payment actually completed
            // on eSewa's side and wasn't tampered with in the browser.
            $statusUrl = ESEWA_STATUS_URL . '?' . http_build_query([
                'product_code' => ESEWA_MERCHANT_CODE,
                'total_amount' => $order['total_amount'],
                'transaction_uuid' => $transaction_uuid,
            ]);

            $verifyResponse = @file_get_contents($statusUrl);
            $verifyData = $verifyResponse ? json_decode($verifyResponse, true) : null;

            if ($verifyData && isset($verifyData['status']) && $verifyData['status'] === 'COMPLETE') {
                $update = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                $update->bind_param("i", $order['id']);
                $update->execute();
            } else {
                $error = "Payment could not be verified with eSewa. Please contact support if you were charged.";
            }
        }
    }
}

$pageTitle = "Payment Result";
include '../includes/header.php';
?>

<div class="container mt-5 text-center">
    <?php if ($error): ?>
        <h3 class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Payment Verification Issue</h3>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="my_orders.php" class="btn btn-outline-secondary">Go to My Orders</a>
    <?php else: ?>
        <h3 class="text-success"><i class="fa-solid fa-circle-check"></i> Payment Successful!</h3>
        <p>Your eSewa payment for Order #<?php echo $order['id']; ?> has been confirmed.</p>
        <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Receipt</a>
        <a href="dashboard.php" class="btn btn-outline-secondary">Continue Browsing</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>