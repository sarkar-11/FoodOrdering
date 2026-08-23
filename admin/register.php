
<?php
include '../includes/db.php';
include '../includes/config.php';

// Change this to your own secret value — anyone who doesn't know this code
// cannot create an admin account, even if they find this page.
define('ADMIN_SECRET_CODE', 'foodorder-admin-2026');

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $secretCode = $_POST['secret_code'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($secretCode !== ADMIN_SECRET_CODE) {
        $error = "Invalid admin registration code.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin';

            $stmt2 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt2->execute()) {
                header("Location: " . APP_BASE_URL . "/admin/login.php?registered=1");
                exit();
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}

$pageTitle = "Admin Registration";
include '../includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh;">
    <div class="card shadow p-4" style="max-width:420px; width:100%;">
        <h3 class="text-center mb-1"><i class="fa-solid fa-user-shield me-2"></i>Admin Registration</h3>
        <p class="text-center text-muted small mb-3">Requires a valid admin registration code</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Registration Code</label>
                <input type="password" name="secret_code" class="form-control" required>
                <small class="text-muted">Provided only to authorized administrators.</small>
            </div>
            <button type="submit" class="btn btn-dark w-100">Create Admin Account</button>
        </form>

        <p class="text-center mt-3 small">Already an admin? <a href="login.php">Login here</a></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>