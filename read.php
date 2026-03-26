<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get manuscript ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$manuscript_id = (int)$_GET['id'];

// Get manuscript details
$query = "SELECT m.*, u.username as author_username 
          FROM manuscripts m 
          JOIN users u ON m.user_id = u.id 
          WHERE m.id = $manuscript_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: home.php");
    exit();
}

$manuscript = mysqli_fetch_assoc($result);

// Check if manuscript is private and user is not the owner
if ($manuscript['is_public'] == 0 && $manuscript['user_id'] != $user_id) {
    header("Location: home.php");
    exit();
}


if ($manuscript['user_id'] != $user_id) {
    // Check if user already viewed this manuscript in this session
    $session_key = 'viewed_manuscript_' . $manuscript_id;
    
    if (!isset($_SESSION[$session_key])) {
        // Increment view count
        $update_views = "UPDATE manuscripts SET views = views + 1 WHERE id = $manuscript_id";
        mysqli_query($conn, $update_views);
        
        // Mark as viewed in this session
        $_SESSION[$session_key] = true;
    }
}

if (!isset($_GET['id'])) {
    die("No manuscript selected.");
}

$manuscript_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

// Fetch manuscript
$query = "
    SELECT m.*, u.username 
    FROM manuscripts m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = $manuscript_id
    LIMIT 1
";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("This manuscript does not exist.");
}

$manuscript = mysqli_fetch_assoc($result);

// Permission check
if (!$manuscript['is_public'] && $manuscript['user_id'] != $user_id) {
    die("This manuscript is private");
}


$pdf_path = $_SERVER['DOCUMENT_ROOT'] . $manuscript['filepath'];
if (!file_exists($pdf_path)) {
    die("File not found.");}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($manuscript['title']); ?> – Garden of Words</title>
    <link rel="stylesheet" href="includes/generalstyles.css">
    <link rel="stylesheet" href="includes/functional.css">
</head>
<body>

<div class="reader-header">
    <h1><?php echo htmlspecialchars($manuscript['title']); ?></h1>
    <p>by <?php echo htmlspecialchars($manuscript['username']); ?></p>

    <div class="reader-actions">
        <a href="home.php">← Back to garden</a>
        <a href="serve_pdf.php?id=<?php echo $manuscript_id; ?>" target="_blank">Open PDF in new tab</a>
    </div>
</div>

<div class="pdf-container">
    <iframe src="serve_pdf.php?id=<?php echo $manuscript_id; ?>"></iframe>
</div>

<?php include 'includes/comments-section.php'; ?>
</body>
</html>
<?php
