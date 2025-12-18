<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch manuscripts + reactions
$query = "
    SELECT 
        m.*,
        SUM(CASE WHEN r.reaction = 'like' THEN 1 ELSE 0 END) AS likes,
        SUM(CASE WHEN r.reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes
    FROM manuscripts m
    LEFT JOIN manuscript_reactions r 
        ON m.id = r.manuscript_id
    WHERE m.user_id = $user_id
    GROUP BY m.id
    ORDER BY m.created_at DESC
";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Manuscripts</title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">🌿 Garden of Words</div>
    <div class="nav-links">
        <a href="home.php">Discover</a>
        <a href="my-manuscripts.php" class="active">My Manuscripts</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <h1>My Manuscripts</h1>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <p class="empty">You haven’t uploaded anything yet 🥲</p>
    <?php endif; ?>

    <div class="manuscripts-grid">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="manuscript-card">

                <!-- Cover -->
                <?php if (!empty($row['cover_image'])): ?>
                    <img src="<?= htmlspecialchars($row['cover_image']) ?>" class="cover-image">
                <?php else: ?>
                    <div class="pdf-icon">📄</div>
                <?php endif; ?>

                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><?= htmlspecialchars($row['description']) ?></p>

                <!-- Reactions -->
                <div class="reactions" data-id="<?= $row['id'] ?>">
                    <button class="react-btn like-btn" data-action="like">
                        👍 <span class="like-count"><?= $row['likes'] ?? 0 ?></span>
                    </button>

                    <button class="react-btn dislike-btn" data-action="dislike">
                        👎 <span class="dislike-count"><?= $row['dislikes'] ?? 0 ?></span>
                    </button>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <a href="read.php?id=<?= $row['id'] ?>" class="btn">Read</a>
                    <a href="edit-manuscript.php?id=<?= $row['id'] ?>" class="btn">Edit</a>
                    <a href="delete-manuscript.php?id=<?= $row['id'] ?>" 
                       class="btn danger"
                       onclick="return confirm('Delete this manuscript?');">
                       Delete
                    </a>
                </div>

                <small class="date">
                    Uploaded: <?= date("M d, Y", strtotime($row['created_at'])) ?>
                </small>

            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="includes/script.js"></script>
</body>
</html>
