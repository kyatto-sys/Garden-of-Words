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

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $new_email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Validation
    if (empty($new_username)) {
        $error = "Username cannot be empty!";
    } elseif (empty($new_email)) {
        $error = "Email cannot be empty!";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Check if username is taken by another user
        $check_username = "SELECT id FROM users WHERE username = '$new_username' AND id != $user_id";
        $username_result = mysqli_query($conn, $check_username);

        if (mysqli_num_rows($username_result) > 0) {
            $error = "This username is already taken!";
        } else {
            // Check if email is taken by another user
            $check_email = "SELECT id FROM users WHERE email = '$new_email' AND id != $user_id";
            $email_result = mysqli_query($conn, $check_email);

            if (mysqli_num_rows($email_result) > 0) {
                $error = "This email is already registered!";
            } else {
                // Update profile
                $update_query = "UPDATE users SET username = '$new_username', email = '$new_email' WHERE id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success = "Profile updated successfully!";
                    $_SESSION['username'] = $new_username;
                    $username = $new_username;
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}

// Get user profile data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get user statistics
$stats_query = "
    SELECT
        (SELECT COUNT(*) FROM manuscripts WHERE user_id = $user_id) as total_manuscripts,
        (SELECT COUNT(*) FROM manuscripts WHERE user_id = $user_id AND is_public = 1) as public_manuscripts,
        (SELECT SUM(views) FROM manuscripts WHERE user_id = $user_id) as total_views,
        (SELECT COUNT(*) FROM manuscript_reactions mr JOIN manuscripts m ON mr.manuscript_id = m.id WHERE m.user_id = $user_id AND mr.reaction = 'like') as total_likes
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get user's manuscripts
$manuscripts_query = "
    SELECT m.*,
           (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'like') as like_count,
           (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'dislike') as dislike_count,
           (SELECT COUNT(*) FROM manuscript_comments WHERE manuscript_id = m.id) as comment_count
    FROM manuscripts m
    WHERE m.user_id = $user_id
    ORDER BY m.created_at DESC
";
$manuscripts_result = mysqli_query($conn, $manuscripts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Garden of Words 🌿</title>
    <link rel="stylesheet" href="includes/style.css">
    <link rel="stylesheet" href="includes/profile.css">
</head>
<body>
    <!-- Floating Leaves -->
    <div class="leaf">🍃</div><div class="leaf">🍃</div><div class="leaf">🍃</div><div class="leaf">🍃</div><div class="leaf">🍃</div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo"><img src="assets/garden.png" alt="Garden"> Garden of Words</div>
        <div class="nav-links">
            <a href="home.php">Discover</a>
            <a href="my-manuscripts.php">My Manuscripts</a>
            <a href="upload.php" class="upload-btn">Upload Manuscript</a>
            <a href="profile.php" class="active">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <div class="avatar-placeholder"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p class="join-date">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <img src="assets/manuscript.png" alt="Manuscripts" class="stat-icon">
                <div class="stat-content">
                    <h3><?php echo $stats['total_manuscripts']; ?></h3>
                    <p>Total Manuscripts</p>
                </div>
            </div>
            <div class="stat-card">
                <img src="assets/view.png" alt="Views" class="stat-icon">
                <div class="stat-content">
                    <h3><?php echo $stats['total_views'] ?? 0; ?></h3>
                    <p>Total Views</p>
                </div>
            </div>
            <div class="stat-card">
                <img src="assets/like.png" alt="Likes" class="stat-icon">
                <div class="stat-content">
                    <h3><?php echo $stats['total_likes'] ?? 0; ?></h3>
                    <p>Likes Received</p>
                </div>
            </div>
            <div class="stat-card">
                <img src="assets/public.png" alt="Public" class="stat-icon">
                <div class="stat-content">
                    <h3><?php echo $stats['public_manuscripts']; ?></h3>
                    <p>Public Manuscripts</p>
                </div>
            </div>
        </div>

        <!-- Profile Edit Section -->
        <div class="profile-section">
            <h2><img src="assets/edit.png" alt="Edit" class="section-icon"> Edit Profile</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="profile-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>

        <!-- Recent Manuscripts -->
        <div class="profile-section">
            <h2><img src="assets/manuscript.png" alt="Manuscripts" class="section-icon"> My Manuscripts</h2>

            <?php if (mysqli_num_rows($manuscripts_result) > 0): ?>
                <div class="manuscripts-list">
                    <?php while ($manuscript = mysqli_fetch_assoc($manuscripts_result)): ?>
                        <div class="manuscript-item">
                            <div class="manuscript-info">
                                <h3><?php echo htmlspecialchars($manuscript['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($manuscript['description'], 0, 100)) . (strlen($manuscript['description']) > 100 ? '...' : ''); ?></p>
                                <div class="manuscript-meta">
                                    <span class="meta-item">
                                        <img src="assets/view.png" alt="Views" class="meta-icon">
                                        <?php echo $manuscript['views']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/like.png" alt="Likes" class="meta-icon">
                                        <?php echo $manuscript['like_count']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/comments.png" alt="Comments" class="meta-icon">
                                        <?php echo $manuscript['comment_count']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/calendar.png" alt="Date" class="meta-icon">
                                        <?php echo date('M j, Y', strtotime($manuscript['created_at'])); ?>
                                    </span>
                                    <span class="status-badge <?php echo $manuscript['is_public'] ? 'public' : 'private'; ?>">
                                        <?php echo $manuscript['is_public'] ? 'Public' : 'Private'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="manuscript-actions">
                                <a href="read.php?id=<?php echo $manuscript['id']; ?>" class="btn btn-secondary">Read</a>
                                <a href="edit-manuscript.php?id=<?php echo $manuscript['id']; ?>" class="btn btn-outline">Edit</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No manuscripts yet</h3>
                    <p>Share your creative work with the community!</p>
                    <a href="upload.php" class="btn btn-primary">Upload Your First Manuscript</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="includes/script.js"></script>
</body>
</html>
