<?php
include '../includes/db.php';
include '../includes/config.php';

$error = "";
$flashMessage = null;

if (isset($_GET['msg']) && $_GET['msg'] === 'login_required') {
    $flashMessage = ['type' => 'warning', 'text' => 'Please login to continue.'];
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'unauthorized') {
    $flashMessage = ['type' => 'danger', 'text' => "You don't have permission to access that page."];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] === 'blocked') {
            $error = "Your account has been blocked. Contact admin.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: " . APP_BASE_URL . "/admin/dashboard.php");
            } elseif ($user['role'] === 'restaurant') {
                header("Location: " . APP_BASE_URL . "/restaurant/dashboard.php");
            } else {
                header("Location: " . APP_BASE_URL . "/user/dashboard.php");
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow p-4" style="max-width:420px; width:100%;">
        <h3 class="text-center mb-3">Login</h3>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful! Please login.</div>
        <?php endif; ?>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashMessage['type']); ?>">
                <?php echo htmlspecialchars($flashMessage['text']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
            <p class="text-center mb-3"><a href="forgot_password.php" class="small">Forgot Password?</a></p>
        </form>
        <p class="text-center mt-3">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>
</body>
</html>