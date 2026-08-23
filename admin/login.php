<?php
include '../includes/db.php';
include '../includes/config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // This login only accepts admin accounts — anyone else is rejected here,
        // even if their email/password is correct, to keep this portal admin-only.
        if ($user['role'] !== 'admin') {
            $error = "This login is for administrators only.";
        } elseif ($user['status'] === 'blocked') {
            $error = "Your account has been blocked.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            header("Location: " . APP_BASE_URL . "/admin/dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}

$pageTitle = "Admin Login";
include '../includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh;">
    <div class="card shadow p-4" style="max-width:420px; width:100%;">
        <h3 class="text-center mb-1"><i class="fa-solid fa-user-shield me-2"></i>Admin Login</h3>
        <p class="text-center text-muted small mb-3">Restricted access — administrators only</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Login as Admin</button>
        </form>

        <p class="text-center mt-3 small">
            Need an admin account? <a href="register.php">Register here</a>
        </p>
        <p class="text-center small">
            <a href="<?php echo APP_BASE_URL; ?>/auth/login.php">Back to regular login</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>