<?php
include '../includes/db.php';
include '../includes/esewa_config.php';

$error = "";
$order = null;

// eSewa sends back a base64-encoded JSON string in the "data" parameter.
// Use REQUEST so we accept either GET or POST redirects from the gateway.
$encodedData = $_REQUEST['data'] ?? '';

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
        $stmt = $conn->prepare("SELECT * FROM orders WHERE transaction_uuid = ?");
        $stmt->bind_param("s", $transaction_uuid);
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

            // Use cURL instead of file_get_contents — many shared hosts (including
            // free hosts like InfinityFree) disable allow_url_fopen, which makes
            // file_get_contents() silently fail on external URLs.
            $ch = curl_init($statusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $verifyResponse = curl_exec($ch);
            curl_close($ch);

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