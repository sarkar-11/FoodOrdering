<?php
include '../includes/db.php';
include '../includes/config.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = "";
$success = false;

if ($token === '') {
    header("Location: " . APP_BASE_URL . "/auth/forgot_password.php");
    exit();
}

// Validate token exists and hasn't expired
$stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$validToken = false;
if ($user) {
    if (strtotime($user['reset_expires']) >= time()) {
        $validToken = true;
    } else {
        $error = "This reset link has expired. Please request a new one.";
    }
} else {
    $error = "This reset link is invalid.";
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password and clear the token so the link can't be reused
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user['id']);
        $update->execute();

        $success = true;
    }
}

$pageTitle = "Reset Password";
include '../includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height:70vh;">
    <div class="card shadow p-4" style="max-width:450px; width:100%;">
        <h4 class="text-center mb-3">Reset Password</h4>

        <?php if ($success): ?>
            <div class="alert alert-success">Your password has been reset successfully.</div>
            <a href="login.php" class="btn btn-primary w-100">Go to Login</a>

        <?php elseif ($validToken): ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>

        <?php else: ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <a href="forgot_password.php" class="btn btn-outline-primary w-100">Request a New Link</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
