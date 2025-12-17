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

// Get recent public manuscripts with user info and reaction counts
$manuscripts_query = "
    SELECT 
        m.*,
        u.username as author_username,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'like') as like_count,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'dislike') as dislike_count,
        (SELECT COUNT(*) FROM manuscript_comments WHERE manuscript_id = m.id) as comment_count,
        (SELECT reaction FROM manuscript_reactions WHERE manuscript_id = m.id AND user_id = $user_id) as user_reaction
    FROM manuscripts m
    JOIN users u ON m.user_id = u.id
    WHERE m.is_public = 1
    ORDER BY m.created_at DESC
    LIMIT 20
";
$manuscripts_result = mysqli_query($conn, $manuscripts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garden of Words - Manuscript Library 🌿</title>
    <link rel="stylesheet" href="includes/home.css">
</head>
<body>
    <!-- Floating Leaves Background -->
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">🌿 Garden of Words</div>
        <div class="nav-links">
            <a href="home.php" class="active">Discover</a>
            <a href="my-manuscripts.php">My Manuscripts</a>
            <a href="upload.php" class="upload-btn">Upload Manuscript</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="header-section">
            <h1>The Literary Garden</h1>
            <p>Discover manuscripts from our creative community</p>
        </div>

        <!-- Floating Manuscripts Grid -->
        <div class="manuscripts-grid">
            <?php if (mysqli_num_rows($manuscripts_result) > 0): ?>
                <?php while ($manuscript = mysqli_fetch_assoc($manuscripts_result)): ?>
                    <div class="manuscript-card" data-id="<?php echo $manuscript['id']; ?>">
                        <div class="manuscript-header">
                            <div class="pdf-icon">📄</div>
                            <div class="manuscript-meta">
                                <h3 class="manuscript-title"><?php echo htmlspecialchars($manuscript['title']); ?></h3>
                                <p class="manuscript-author">by <?php echo htmlspecialchars($manuscript['author_username']); ?></p>
                            </div>
                        </div>

                        <div class="manuscript-description">
                            <?php 
                            $desc = htmlspecialchars($manuscript['description']);
                            echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                            ?>
                        </div>

                        <div class="manuscript-stats">
                            <span class="stat">
                                <span class="icon">👁️</span> <?php echo $manuscript['views']; ?>
                            </span>
                            <span class="stat">
                                <span class="icon">💬</span> <?php echo $manuscript['comment_count']; ?>
                            </span>
                            <span class="stat">
                                <span class="icon">📅</span> <?php echo date('M j', strtotime($manuscript['created_at'])); ?>
                            </span>
                        </div>

                        <div class="manuscript-actions">
                            <button class="reaction-btn like-btn <?php echo ($manuscript['user_reaction'] == 'like') ? 'active' : ''; ?>" 
                                    data-manuscript-id="<?php echo $manuscript['id']; ?>" 
                                    data-action="like">
                                <span class="icon">👍</span>
                                <span class="count"><?php echo $manuscript['like_count']; ?></span>
                            </button>
                            
                            <button class="reaction-btn dislike-btn <?php echo ($manuscript['user_reaction'] == 'dislike') ? 'active' : ''; ?>" 
                                    data-manuscript-id="<?php echo $manuscript['id']; ?>" 
                                    data-action="dislike">
                                <span class="icon">👎</span>
                                <span class="count"><?php echo $manuscript['dislike_count']; ?></span>
                            </button>

                            <a href="read.php?id=<?php echo $row['id']; ?>" class="btn-read"> Read Now 📖</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-manuscripts">
                    <div class="empty-state">
                        <span class="empty-icon">📚</span>
                        <h2>No manuscripts yet!</h2>
                        <p>Be the first to share your work with the community</p>
                        <a href="upload.php" class="upload-btn-large">Upload Your Manuscript</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="includes/script.js"></script>
</body>
</html>