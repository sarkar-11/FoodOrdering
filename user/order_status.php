<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

$stmt = $conn->prepare("SELECT status, payment_status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false]);
    exit();
}

echo json_encode([
    'success' => true,
    'status' => $order['status'],
    'payment_status' => $order['payment_status']
]);