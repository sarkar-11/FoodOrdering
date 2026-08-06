<?php
include '../includes/db.php';
include '../includes/auth_check.php';
include '../includes/khalti_config.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_method = 'khalti'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

// Khalti expects amount in paisa (Rs. x 100), and a minimum of Rs. 10
$amountPaisa = (int)round($order['total_amount'] * 100);

$payload = [
    "return_url"          => KHALTI_RETURN_URL,
    "website_url"         => KHALTI_WEBSITE_URL,
    "amount"               => $amountPaisa,
    "purchase_order_id"    => (string)$order_id,
    "purchase_order_name"  => "FoodOrder #" . $order_id,
];

// Server-side call to Khalti's initiate endpoint using cURL
$ch = curl_init(KHALTI_INITIATE_URL);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // avoids local XAMPP/Windows CA cert issues
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Key " . KHALTI_SECRET_KEY,
    "Content-Type: application/json",
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

if (!$result || !isset($result['payment_url'])) {
    // Show Khalti's actual error message when available, so it's clear
    // whether this is a bad key, bad amount, or a network issue.
    $detail = isset($result['detail']) ? $result['detail'] : ($curlError ?: 'Unknown error.');
    $error = "Could not initiate Khalti payment: " . $detail;

    $pageTitle = "Payment Error";
    include '../includes/header.php';
    ?>
    <div class="container mt-5 text-center">
        <h4 class="text-danger">Payment Initiation Failed</h4>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="my_orders.php" class="btn btn-outline-secondary">Back to My Orders</a>
    </div>
    <?php
    include '../includes/footer.php';
    exit();
}

// Save the pidx (Khalti's unique payment identifier) so we can verify it later
$pidx = $result['pidx'];
$update = $conn->prepare("UPDATE orders SET transaction_uuid = ? WHERE id = ?");
$update->bind_param("si", $pidx, $order_id);
$update->execute();

// Redirect the customer to Khalti's hosted checkout page
header("Location: " . $result['payment_url']);
exit();