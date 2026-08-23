<?php
// Include database connection
include '../includes/db.php';

// Check if the user is logged in and is a restaurant owner
include '../includes/auth_check.php';
require_role('restaurant');

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Find the restaurant that belongs to this user
$sql = "SELECT * FROM restaurants WHERE user_id = ?";

// Prepare the SQL query
$stmt = $conn->prepare($sql);

// Bind the user ID
$stmt->bind_param("i", $user_id);

// Execute the query
$stmt->execute();

// Get the restaurant information
$result = $stmt->get_result();
$restaurant = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<!-- Navigation Bar -->
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand">Restaurant Panel</span>

    <!-- Logout Button -->
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">
        Logout
    </a>
</nav>

<div class="container mt-4">

    <!-- Display logged-in user's name -->
    <h3>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h3>

    <?php
    // Check if the restaurant profile exists
    if (!$restaurant):
    ?>

        <!-- If no restaurant profile -->
        <div class="alert alert-warning mt-3">
            You haven't set up your restaurant profile yet.

            <a href="setup_restaurant.php" class="btn btn-primary btn-sm ms-2">
                Set Up Restaurant
            </a>
        </div>

    <?php else: ?>

        <!-- Restaurant Information -->
        <div class="card mt-3 p-3">

            <!-- Restaurant Name -->
            <h5><?php echo htmlspecialchars($restaurant['name']); ?></h5>

            <!-- Restaurant Description -->
            <p><?php echo htmlspecialchars($restaurant['description']); ?></p>

            <!-- Restaurant Status -->
            <p>
                <strong>Status:</strong>

                <span class="badge bg-<?php echo ($restaurant['status'] == 'approved') ? 'success' : 'secondary'; ?>">

                    <?php echo ucfirst($restaurant['status']); ?>

                </span>
            </p>

            <!-- Action Buttons -->
            <div>

                <a href="edit_restaurant.php" class="btn btn-outline-primary btn-sm">
                    Edit Restaurant Info
                </a>

                <a href="manage_foods.php" class="btn btn-success btn-sm">
                    Manage Food Items
                </a>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>
</html>