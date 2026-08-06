<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('user');

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT o.*, r.name AS restaurant_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

$steps = ['pending' => 'Order Placed', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'delivered' => 'Delivered'];
$stepKeys = array_keys($steps);
$currentIndex = array_search($order['status'], $stepKeys);
$isCancelled = $order['status'] === 'cancelled';

$pageTitle = "Track Order #" . $order['id'];
include '../includes/header.php';
?>

<div class="container mt-4" style="max-width:700px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Track Order #<?php echo $order['id']; ?></h3>
        <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
    </div>
    <p class="text-muted"><?php echo htmlspecialchars($order['restaurant_name']); ?> — Rs. <?php echo number_format($order['total_amount'], 2); ?></p>

    <?php if ($isCancelled): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-xmark me-1"></i> This order was cancelled.
        </div>
    <?php else: ?>
        <div class="card p-4 shadow-sm">
            <div id="tracker-steps" class="d-flex justify-content-between position-relative">
                <?php foreach ($steps as $key => $label): ?>
                    <?php
                        $stepIndex = array_search($key, $stepKeys);
                        $isDone = $stepIndex <= $currentIndex;
                        $isCurrent = $stepIndex === $currentIndex;
                    ?>
                    <div class="text-center flex-fill tracker-step" data-step="<?php echo $key; ?>" style="z-index:2;">
                        <div class="tracker-circle mx-auto mb-2 <?php echo $isDone ? 'tracker-done' : ''; ?> <?php echo $isCurrent ? 'tracker-current' : ''; ?>">
                            <?php if ($isDone && !$isCurrent): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php else: ?>
                                <?php echo $stepIndex + 1; ?>
                            <?php endif; ?>
                        </div>
                        <small class="<?php echo $isDone ? 'fw-bold' : 'text-muted'; ?>"><?php echo $label; ?></small>
                    </div>
                <?php endforeach; ?>

                <div class="tracker-line" style="position:absolute; top:20px; left:10%; right:10%; height:3px; background:#e0e0e0; z-index:1;">
                    <div id="tracker-line-fill" style="height:100%; background:#198754; width:<?php echo ($currentIndex / (count($steps) - 1)) * 100; ?>%; transition: width 0.5s ease;"></div>
                </div>
            </div>

            <p class="text-center text-muted small mt-4 mb-0" id="tracker-live-note">
                <i class="fa-solid fa-rotate fa-spin"></i> Live tracking — this page updates automatically
            </p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-3">
        <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">View Receipt</a>
    </div>
</div>

<style>
.tracker-circle {
    width: 42px; height: 42px; border-radius: 50%;
    background: #e0e0e0; color: #888;
    display: flex; align-items: center; justify-content: center;
    font-weight: bold; transition: all 0.3s ease;
}
.tracker-done { background: #198754; color: #fff; }
.tracker-current { background: #ffc107; color: #212529; box-shadow: 0 0 0 4px rgba(255,193,7,0.3); }
</style>

<script>
const orderId = <?php echo $order['id']; ?>;
const stepOrder = ['pending', 'confirmed', 'preparing', 'delivered'];

function updateTracker(status) {
    const currentIndex = stepOrder.indexOf(status);
    const steps = document.querySelectorAll('.tracker-step');

    steps.forEach((stepEl, i) => {
        const circle = stepEl.querySelector('.tracker-circle');
        circle.classList.remove('tracker-done', 'tracker-current');
        if (i < currentIndex) {
            circle.classList.add('tracker-done');
            circle.innerHTML = '<i class="fa-solid fa-check"></i>';
        } else if (i === currentIndex) {
            circle.classList.add('tracker-current');
            circle.innerHTML = (i + 1);
        } else {
            circle.innerHTML = (i + 1);
        }
    });

    const fill = document.getElementById('tracker-line-fill');
    if (fill) fill.style.width = (currentIndex / (stepOrder.length - 1) * 100) + '%';
}

function pollStatus() {
    fetch(`order_status.php?order_id=${orderId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && stepOrder.includes(data.status)) {
                updateTracker(data.status);

                if (data.status === 'delivered') {
                    const note = document.getElementById('tracker-live-note');
                    if (note) note.innerHTML = '<i class="fa-solid fa-circle-check text-success"></i> Order delivered!';
                    clearInterval(pollInterval);
                }
            }
        })
        .catch(() => {});
}

// Poll every 8 seconds for a live-updating feel
const pollInterval = setInterval(pollStatus, 8000);
</script>

<?php include '../includes/footer.php'; ?>