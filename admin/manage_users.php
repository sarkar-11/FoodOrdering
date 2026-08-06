<?php
include '../includes/db.php';
include '../includes/auth_check.php';
require_role('admin');

if (isset($_GET['block'])) {
    $id = (int)$_GET['block'];
    $stmt = $conn->prepare("UPDATE users SET status='blocked' WHERE id=? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?updated=1");
    exit();
}
if (isset($_GET['unblock'])) {
    $id = (int)$_GET['unblock'];
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?updated=1");
    exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_users.php?deleted=1");
    exit();
}

$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$totalCount = $users ? $users->num_rows : 0;

$pageTitle = "Manage Users";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Users</h3>
        <span class="badge bg-secondary"><?php echo $totalCount; ?> total</span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">User status updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">User deleted.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table bg-white shadow-sm align-middle">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($u['role']); ?></span></td>
                    <td>
                        <span class="badge bg-<?php echo $u['status']==='active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($u['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <a href="?block=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">Block</a>
                        <?php else: ?>
                            <a href="?unblock=<?php echo $u['id']; ?>" class="btn btn-sm btn-success">Unblock</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this user permanently?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>