<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$user_id = $_SESSION['user_id'];

if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: cart.php");
    exit();
}

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT * FROM foods WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($food = $res->fetch_assoc()) {
        $qty = $_SESSION['cart'][$food['id']];
        $subtotal = $food['price'] * $qty;
        $total += $subtotal;
        $cartItems[] = ['food' => $food, 'qty' => $qty, 'subtotal' => $subtotal];
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cartItems)) {
        header("Location: cart.php");
        exit();
    }
    $allowedMethods = ['cod', 'esewa', 'khalti'];
    $payment_method = in_array($_POST['payment_method'] ?? '', $allowedMethods) ? $_POST['payment_method'] : 'cod';
    $notes = trim($_POST['notes'] ?? '');
    if (strlen($notes) > 300) {
        $notes = substr($notes, 0, 300);
    }

    $delivery_address = trim($_POST['delivery_address'] ?? '');
    if ($delivery_address === '') {
        $error = "Please enter a delivery address.";
    }
    $delivery_lat = !empty($_POST['delivery_lat']) ? (float)$_POST['delivery_lat'] : null;
    $delivery_lng = !empty($_POST['delivery_lng']) ? (float)$_POST['delivery_lng'] : null;

    // Every order starts unpaid. COD stays unpaid until delivery.
    // eSewa and Khalti orders stay unpaid until their real gateway confirms
    // payment on callback — nothing is marked paid until independently verified.
    $payment_status = 'unpaid';

    $restaurant_id = $_SESSION['cart_restaurant_id'];

    if ($error === '') {
        $conn->begin_transaction();
        try {
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, total_amount, payment_method, payment_status, notes, delivery_address, delivery_lat, delivery_lng, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $orderStmt->bind_param("iidssssdd", $user_id, $restaurant_id, $total, $payment_method, $payment_status, $notes, $delivery_address, $delivery_lat, $delivery_lng);
            $orderStmt->execute();
            $order_id = $conn->insert_id;

            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $food_id = $item['food']['id'];
                $qty = $item['qty'];
                $price = $item['food']['price'];
                $itemStmt->bind_param("iiid", $order_id, $food_id, $qty, $price);
                $itemStmt->execute();
            }

            $conn->commit();
            unset($_SESSION['cart']);
            unset($_SESSION['cart_restaurant_id']);

            if ($payment_method === 'esewa') {
                // Send them to the real eSewa test gateway instead of straight to success
                header("Location: esewa_pay.php?order_id=" . $order_id);
                exit();
            }

            if ($payment_method === 'khalti') {
                // Send them to the real Khalti test gateway
                header("Location: khalti_pay.php?order_id=" . $order_id);
                exit();
            }

            header("Location: order_success.php?id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to place order. Try again.";
        }
    }
}

$pageTitle = "Your Cart";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h3>Your Cart</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table bg-white align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th style="width:110px;">Qty</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['food']['name']); ?></td>
                        <td>Rs. <?php echo number_format($item['food']['price'], 2); ?></td>
                        <td>
                            <input type="number"
                                   class="form-control form-control-sm cart-qty-input"
                                   min="1"
                                   value="<?php echo $item['qty']; ?>"
                                   data-food-id="<?php echo $item['food']['id']; ?>"
                                   data-price="<?php echo $item['food']['price']; ?>">
                            <small class="text-muted sync-status"></small>
                        </td>
                        <td class="row-subtotal">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                        <td><a href="cart.php?remove=<?php echo $item['food']['id']; ?>" class="btn btn-sm btn-outline-danger">Remove</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h5>Total: <span id="grand-total">Rs. <?php echo number_format($total, 2); ?></span></h5>

        <form method="POST">
            <div class="card p-3 mb-3" style="max-width:400px;">
                <label class="form-label fw-bold">Payment Method</label>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="cod" checked>
                    <label class="form-check-label" for="pm_cod">
                        <i class="fa-solid fa-money-bill-wave me-1"></i> Cash on Delivery
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm_esewa" value="esewa">
                    <label class="form-check-label" for="pm_esewa">
                        <i class="fa-solid fa-wallet me-1"></i> eSewa
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="pm_khalti" value="khalti">
                    <label class="form-check-label" for="pm_khalti">
                        <i class="fa-solid fa-mobile-screen-button me-1"></i> Khalti
                    </label>
                </div>

                <small class="text-muted mt-2">
                    Selecting eSewa takes you to eSewa's real test payment gateway.
                </small>
            </div>

            <div class="mb-3" style="max-width:400px;">
                <label class="form-label fw-bold">Delivery Address</label>
                <div class="input-group">
                    <input type="text" name="delivery_address" id="deliveryAddress" class="form-control"
                           placeholder="e.g. Baneshwor, Kathmandu" required>
                    <button type="button" id="useLocationBtn" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-location-crosshairs"></i> Use My Location
                    </button>
                </div>
                <input type="hidden" name="delivery_lat" id="deliveryLat">
                <input type="hidden" name="delivery_lng" id="deliveryLng">
                <small class="text-muted" id="locationStatus"></small>
            </div>

            <div class="mb-3" style="max-width:400px;">
                <label class="form-label fw-bold">Delivery Notes (optional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. No onions, call before arriving, leave at gate..." maxlength="300"></textarea>
            </div>

            <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.getElementById('useLocationBtn')?.addEventListener('click', function () {
    const statusEl = document.getElementById('locationStatus');

    if (!navigator.geolocation) {
        statusEl.textContent = 'Geolocation is not supported by your browser.';
        return;
    }

    statusEl.textContent = 'Detecting your location...';

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            document.getElementById('deliveryLat').value = lat;
            document.getElementById('deliveryLng').value = lng;

            // Reverse geocode using OpenStreetMap's free Nominatim API (no API key needed)
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('deliveryAddress').value = data.display_name;
                        statusEl.textContent = 'Location detected and filled in.';
                    } else {
                        statusEl.textContent = `Coordinates captured (${lat.toFixed(4)}, ${lng.toFixed(4)}) — please type your address manually.`;
                    }
                })
                .catch(() => {
                    statusEl.textContent = `Coordinates captured (${lat.toFixed(4)}, ${lng.toFixed(4)}) — please type your address manually.`;
                });
        },
        function (error) {
            statusEl.textContent = 'Could not get your location. Please type your address manually.';
        }
    );
});
</script>

<script src="/food_ordering_system/assets/js/cart.js"></script>

<?php include '../includes/footer.php'; ?>