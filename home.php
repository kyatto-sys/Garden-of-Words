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
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Determine ORDER BY clause based on sort
switch ($sort) {
    case 'most_viewed':
        $order_by = "m.views DESC";
        break;
    case 'most_liked':
        $order_by = "(SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'like') DESC";
        break;
    case 'oldest':
        $order_by = "m.created_at ASC";
        break;
    case 'newest':
    default:
        $order_by = "m.created_at DESC";
        break;
}

// Get manuscripts with sorting
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
    ORDER BY $order_by
    LIMIT 50
";
$manuscripts_result = mysqli_query($conn, $manuscripts_query);

$trending_query = "
    SELECT 
        m.*,
        u.username as author_username,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'like') as like_count,
        (SELECT COUNT(*) FROM manuscript_reactions WHERE manuscript_id = m.id AND reaction = 'dislike') as dislike_count,
        (SELECT COUNT(*) FROM manuscript_comments WHERE manuscript_id = m.id) as comment_count
    FROM manuscripts m
    JOIN users u ON m.user_id = u.id
    WHERE m.is_public = 1 
    AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY m.views DESC
    LIMIT 3
";
$trending_result = mysqli_query($conn, $trending_query);

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
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <nav class="navbar">
        <div class="logo"><img src="assets/garden.png" alt="Garden"> Garden of Words</div>
        <div class="nav-links">
            <a href="home.php" class="active">Discover</a>
            <a href="my-manuscripts.php">My Manuscripts</a>
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
        <div class="header-section">
            <h1>The Literary Garden</h1>
            <p>Discover manuscripts from our creative community</p>
        </div>

<?php if (mysqli_num_rows($trending_result) > 0): ?>
    <div class="trending-section">
        <div class="trending-header">
            <h2><img src="assets/trending.png" alt="Trending" class="icon"> Trending This Week</h2>
            <p>Most viewed manuscripts in the past 7 days</p>
        </div>
        
        <div class="trending-grid">
            <?php while ($trending = mysqli_fetch_assoc($trending_result)): ?>
                <div class="trending-card" onclick="window.location.href='read.php?id=<?php echo $trending['id']; ?>'">
                    <div class="trending-badge">
                        <img src="assets/trending.png" alt="Trending" class="icon">
                        <span class="view-count"><?php echo number_format($trending['views']); ?> views</span>
                    </div>
                    
                    <div class="trending-cover">
                        <?php if (!empty($trending['cover_image'])): ?>
                            <img src="<?php echo htmlspecialchars($trending['cover_image']); ?>" 
                                 alt="Cover" 
                                 class="trending-cover-img">
                        <?php else: ?>
                            <div class="trending-default-cover">
                                <img src="assets/pdf.png" alt="PDF" class="icon">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="trending-info">
                        <h3 class="trending-title"><?php echo htmlspecialchars($trending['title']); ?></h3>
                        <p class="trending-author">by <?php echo htmlspecialchars($trending['author_username']); ?></p>
                        
                        <div class="trending-stats">
                            <span><img src="assets/like.png" alt="Likes" class="icon"> <?php echo $trending['like_count']; ?></span>
                            <span><img src="assets/comments.png" alt="Comments" class="icon"> <?php echo $trending['comment_count']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>

<div class="manuscripts-grid">
    <?php if (mysqli_num_rows($manuscripts_result) > 0): ?>
        <div class="filter-bar">
            <div class="filter-label">
                <img src="assets/filter.png" alt="Filter" class="icon">
                Sort by:
            </div>
            <select id="sortSelect" class="sort-dropdown" onchange="window.location.href='home.php?sort=' + this.value">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="most_viewed" <?php echo $sort == 'most_viewed' ? 'selected' : ''; ?>>Most Viewed</option>
                <option value="most_liked" <?php echo $sort == 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
            </select>
            
            <div class="results-count">
                Showing <?php echo mysqli_num_rows($manuscripts_result); ?> manuscripts
            </div>
            
        </div>
        <?php while ($manuscript = mysqli_fetch_assoc($manuscripts_result)): ?>
            <div class="manuscript-card" data-id="<?php echo $manuscript['id']; ?>">
                <!-- Cover Image Section -->
                <div class="cover-wrapper">
                    <?php if (!empty($manuscript['cover_image'])): ?>
                        <img 
                            src="<?php echo htmlspecialchars($manuscript['cover_image']); ?>" 
                            alt="<?php echo htmlspecialchars($manuscript['title']); ?> cover"
                            class="cover-image"
                        >
                    <?php else: ?>
                        <div class="default-cover">
                            <img src="assets/pdf.png" alt="PDF" class="icon">
                            <div class="no-cover-text">No Cover</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Manuscript Info -->
                <div class="manuscript-content">
                            <div class="manuscript-header">
                                <h3 class="manuscript-title"><?php echo htmlspecialchars($manuscript['title']); ?></h3>
                                <p class="manuscript-author">by <?php echo htmlspecialchars($manuscript['author_username']); ?></p>
                            </div>

                            <div class="manuscript-description">
                                <?php 
                                $desc = htmlspecialchars($manuscript['description']);
                                echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                ?>
                            </div>

                            <div class="manuscript-stats">
                                <span class="stat">
                                    <img src="assets/view.png" alt="View" class="icon"> <?php echo $manuscript['views']; ?>
                                </span>
                                <span class="stat">
                                    <img src="assets/comments.png" alt="Comment" class="icon"> <?php echo $manuscript['comment_count']; ?>
                                </span>
                                <span class="stat">
                                    <img src="assets/like.png" alt="Like" class="icon"> <?php echo $manuscript['like_count']; ?>
                                </span>
                                <span class="stat">
                                    <img src="assets/dislike.png" alt="Dislike" class="icon"> <?php echo $manuscript['dislike_count']; ?>
                                </span>
                                <span class="stat">
                                    <img src="assets/calendar.png" alt="Date" class="icon"> <?php echo date('M j', strtotime($manuscript['created_at'])); ?>
                                </span>
                            </div>

                            <div class="manuscript-actions">
                                <?php if ($manuscript['user_id'] != $user_id): ?>
                                <button class="reaction-btn like-btn <?php echo ($manuscript['user_reaction'] == 'like') ? 'active' : ''; ?>" 
                                        data-manuscript-id="<?php echo $manuscript['id']; ?>" 
                                        data-action="like">
                                    <img src="assets/like.png" alt="Like" class="icon">
                                    <span class="count"><?php echo $manuscript['like_count']; ?></span>
                                </button>
                                
                                <button class="reaction-btn dislike-btn <?php echo ($manuscript['user_reaction'] == 'dislike') ? 'active' : ''; ?>" 
                                        data-manuscript-id="<?php echo $manuscript['id']; ?>" 
                                        data-action="dislike">
                                    <img src="assets/dislike.png" alt="Dislike" class="icon">
                                    <span class="count"><?php echo $manuscript['dislike_count']; ?></span>
                                </button>
                                <?php endif; ?>
                            
                                <a href="read.php?id=<?php echo $manuscript['id']; ?>" class="btn-read">Read Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-manuscripts">
                    <div class="empty-state">
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