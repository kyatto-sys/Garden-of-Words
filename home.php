<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user info
$user_query = "SELECT email, created_at FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// You can add more queries here later for postcards, etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Garden of Words 🌿</title>
    <link rel="stylesheet" href="includes\style.css">
</head>
<body>
    <!-- Floating Leaves -->
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">🌿 Garden of Words</div>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="my-postcards.php">My Garden</a>
            <a href="write.php">Add New</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>Welcome back, <?php echo htmlspecialchars($username); ?>! 🍵</h1>
            <p>Your personal matcha postcard garden awaits</p>
            <div class="user-info">
                <strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?>
                <br>
                <strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="action-grid">
            <div class="action-card">
                <div class="icon">✍️</div>
                <h3>Leave a piece of your thoughts here</h3>
                <p>Share your thoughts and feelings</p>
                <a href="write.php" class="action-btn">Start Writing</a>
            </div>

            <div class="action-card">
                <div class="icon">📬</div>
                <h3>My Garden</h3>
                <p>Revisit the words you’ve planted and left behind</p>
                <a href="my-postcards.php" class="action-btn">View Collection</a>
            </div>

            <div class="action-card">
                <div class="icon">🌱</div>
                <h3>Explore Garden</h3>
                <p>Wander through the words others have quietly shared</p>
                <a href="explore.php" class="action-btn">Explore Now</a>
            </div>
        </div>
    </div>
</body>
</html>