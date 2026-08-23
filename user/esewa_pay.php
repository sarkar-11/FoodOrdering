<?php
include '../includes/db.php';
include '../includes/auth_check.php';
include '../includes/esewa_config.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

// Fetch the order, making sure it belongs to this user and is still unpaid
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_method = 'esewa'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: " . APP_BASE_URL . "/user/my_orders.php");
    exit();
}

// A unique transaction ID per payment attempt — required by eSewa, and used
// again later during the status verification callback
$transaction_uuid = $order_id . '-' . time();

$update = $conn->prepare("UPDATE orders SET transaction_uuid = ? WHERE id = ?");
$update->bind_param("si", $transaction_uuid, $order_id);
$update->execute();

$amount = number_format($order['total_amount'], 2, '.', '');
$tax_amount = "0";
$product_service_charge = "0";
$product_delivery_charge = "0";
$total_amount = $amount; // no tax/service/delivery charges added in this project

// eSewa requires a signature over these specific fields, in this exact order,
// generated with HMAC-SHA256 using the merchant's secret key.
$signed_field_names = "total_amount,transaction_uuid,product_code";
$message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=" . ESEWA_MERCHANT_CODE;
$signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));

$pageTitle = "Redirecting to eSewa";
include '../includes/header.php';
?>

<div class="container mt-5 text-center">
    <h4>Redirecting you to eSewa...</h4>
    <p class="text-muted">Please wait, do not close this page.</p>

    <form id="esewaForm" action="<?php echo ESEWA_PAYMENT_URL; ?>" method="POST">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
        <input type="hidden" name="tax_amount" value="<?php echo htmlspecialchars($tax_amount); ?>">
        <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($total_amount); ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transaction_uuid); ?>">
        <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_CODE; ?>">
        <input type="hidden" name="product_service_charge" value="<?php echo htmlspecialchars($product_service_charge); ?>">
        <input type="hidden" name="product_delivery_charge" value="<?php echo htmlspecialchars($product_delivery_charge); ?>">
        <input type="hidden" name="success_url" value="<?php echo ESEWA_SUCCESS_URL; ?>">
        <input type="hidden" name="failure_url" value="<?php echo ESEWA_FAILURE_URL; ?>">
        <input type="hidden" name="signed_field_names" value="<?php echo $signed_field_names; ?>">
        <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>">

        <button type="submit" class="btn btn-success mt-3">Continue to eSewa (click if not redirected)</button>
    </form>
</div>

<script>
    // Auto-submit so the user doesn't have to click manually
    document.getElementById('esewaForm').submit();
</script>

<?php include '../includes/footer.php'; ?>