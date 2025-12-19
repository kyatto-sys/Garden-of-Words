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

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Verify the manuscript belongs to this user
    $check_query = "SELECT filepath, cover_image FROM manuscripts WHERE id = $delete_id AND user_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $manuscript = mysqli_fetch_assoc($check_result);
        
        // Delete files
        $pdf_path = $_SERVER['DOCUMENT_ROOT'] . $manuscript['filepath'];
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }
        
        if (!empty($manuscript['cover_image'])) {
            $cover_path = $_SERVER['DOCUMENT_ROOT'] . $manuscript['cover_image'];
            if (file_exists($cover_path)) {
                unlink($cover_path);
            }
        }
        
        // Delete from database (cascade will delete reactions and comments)
        $delete_query = "DELETE FROM manuscripts WHERE id = $delete_id AND user_id = $user_id";
        mysqli_query($conn, $delete_query);
        
        header("Location: my-manuscripts.php?deleted=1");
        exit();
    }
}

// Get user's manuscripts with statistics
$manuscripts_query = "
    SELECT 
        m.*,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'like') as like_count,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'dislike') as dislike_count,
        (SELECT COUNT(*) FROM manuscript_comments WHERE manuscript_id = m.id) as comment_count
    FROM manuscripts m
    WHERE m.user_id = $user_id
    ORDER BY m.created_at DESC
";
$manuscripts_result = mysqli_query($conn, $manuscripts_query);

// Get user statistics
$total_manuscripts = mysqli_num_rows($manuscripts_result);
mysqli_data_seek($manuscripts_result, 0); // Reset pointer

$total_views_query = "SELECT SUM(views) as total_views FROM manuscripts WHERE user_id = $user_id";
$total_views_result = mysqli_query($conn, $total_views_query);
$total_views = mysqli_fetch_assoc($total_views_result)['total_views'] ?? 0;

$total_likes_query = "SELECT COUNT(*) as total_likes FROM manuscript_reactions mr 
                      JOIN manuscripts m ON mr.manuscript_id = m.id 
                      WHERE m.user_id = $user_id AND mr.reaction = 'like'";
$total_likes_result = mysqli_query($conn, $total_likes_query);
$total_likes = mysqli_fetch_assoc($total_likes_result)['total_likes'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Manuscripts - Garden of Words 🌿</title>
    <link rel="stylesheet" href="includes/my-manuscripts.css">
</head>
<body>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <nav class="navbar">
        <div class="logo"><img src="assets/garden.png" alt="Garden"> Garden of Words</div>
        <div class="nav-links">
            <a href="home.php">Discover</a>
            <a href="my-manuscripts.php" class="active">My Manuscripts</a>
            <a href="upload.php" class="upload-btn">Upload Manuscript</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <div class="container">
        <!-- Success Message -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                Manuscript deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="header-section">
            <h1>My Manuscripts</h1>
            <p>Manage your creative works</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <img src="assets/manuscript.png" alt="Manuscripts" class="stat-icon">
                <div class="stat-info">
                    <div class="stat-number"><?php echo $total_manuscripts; ?></div>
                    <div class="stat-label">Total Manuscripts</div>
                </div>
            </div>

            <div class="stat-card">
                <img src="assets/view.png" alt="Views" class="stat-icon">
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format($total_views); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>

            <div class="stat-card">
                <img src="assets/like.png" alt="Likes" class="stat-icon">
                <div class="stat-info">
                    <div class="stat-number"><?php echo $total_likes; ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
            </div>
        </div>

        <!-- Manuscripts List -->
        <div class="manuscripts-section">
            <?php if ($total_manuscripts > 0): ?>
                <div class="manuscripts-list">
                    <?php while ($manuscript = mysqli_fetch_assoc($manuscripts_result)): ?>
                        <div class="manuscript-item">
                            <!-- Cover -->
                            <div class="manuscript-cover">
                                <?php if (!empty($manuscript['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($manuscript['cover_image']); ?>" 
                                         alt="Cover"
                                         class="cover-img">
                                <?php else: ?>
                                    <div class="default-cover-small">
                                        <img src="assets/cover.png" alt="Cover" class="pdf-icon-small">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Info -->
                            <div class="manuscript-info">
                                <h3 class="manuscript-title"><?php echo htmlspecialchars($manuscript['title']); ?></h3>
                                <p class="manuscript-description">
                                    <?php 
                                    $desc = htmlspecialchars($manuscript['description']);
                                    echo strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc;
                                    ?>
                                </p>
                                
                                <div class="manuscript-meta">
                                    <span class="meta-item">
                                        <img src="assets/view.png" alt="Views" class="icon"> <?php echo $manuscript['views']; ?> views
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/like.png" alt="Likes" class="icon"> <?php echo $manuscript['like_count']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/dislike.png" alt="Dislikes" class="icon"> <?php echo $manuscript['dislike_count']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/comments.png" alt="Comments" class="icon"> <?php echo $manuscript['comment_count']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <img src="assets/calendar.png" alt="Date" class="icon"> <?php echo date('M j, Y', strtotime($manuscript['created_at'])); ?>
                                    </span>
                                    <span class="meta-item visibility">
                                        <?php if ($manuscript['is_public']): ?>
                                            <img src="assets/public.png" alt="Public" class="icon"> Public
                                        <?php else: ?>
                                            <img src="assets/private.png" alt="Private" class="icon"> Private
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="manuscript-actions">
                                <a href="read.php?id=<?php echo $manuscript['id']; ?>" 
                                   class="btn btn-view" 
                                   title="View manuscript">
                                    <img src="assets/view.png" alt="View" class="btn-icon"> View
                                </a>
                                <a href="edit-manuscript.php?id=<?php echo $manuscript['id']; ?>" 
                                   class="btn btn-edit" 
                                   title="Edit manuscript">
                                    <img src="assets/edit.png" alt="Edit" class="btn-icon"> Edit
                                </a>
                                <button onclick="confirmDelete(<?php echo $manuscript['id']; ?>, '<?php echo addslashes($manuscript['title']); ?>')" 
                                        class="btn btn-delete" 
                                        title="Delete manuscript">
                                    <img src="assets/delete.png" alt="Delete" class="btn-icon"> Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <img src="assets/pdf.png" alt="Manuscripts" class="empty-icon">
                    <h2>No manuscripts yet!</h2>
                    <p>Start sharing your creative work with the community</p>
                    <a href="upload.php" class="btn-upload-large">Upload Your First Manuscript</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id, title) {
            if (confirm(`Are you sure you want to delete "${title}"?\n\nThis action cannot be undone and will delete all associated likes and comments.`)) {
                window.location.href = `my-manuscripts.php?delete=${id}`;
            }
        }
    </script>
</body>
</html>