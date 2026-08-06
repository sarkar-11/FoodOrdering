<?php
include '../includes/db.php';
include '../includes/mailer.php';

$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Please enter your email.";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Always show the same generic success message whether or not the email exists —
        // this prevents attackers from using this form to discover which emails are registered.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            $update->execute();

            $resetLink = "http://localhost/food_ordering_system/auth/reset_password.php?token=" . $token;

            // Actually send the email now instead of displaying the link
            sendResetEmail($email, $user['name'], $resetLink);
        }

        $success = true;
    }
}

$pageTitle = "Forgot Password";
include '../includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height:70vh;">
    <div class="card shadow p-4" style="max-width:450px; width:100%;">
        <h4 class="text-center mb-3">Forgot Password</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                If an account exists with that email, a password reset link has been sent.
                Please check your inbox (and spam folder).
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Registered Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
        <?php endif; ?>

        <p class="text-center mt-3"><a href="login.php">Back to Login</a></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>