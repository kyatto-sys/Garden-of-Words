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
    die("File not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($manuscript['title']); ?> – Garden of Words</title>
    <style>
        body {
            margin: 0;
            font-family: 'Quicksand', sans-serif;
            background: #f3faf3;
        }

        .reader-header {
            padding: 20px 30px;
            background: white;
            border-bottom: 2px solid #c8e6c9;
        }

        .reader-header h1 {
            margin: 0;
            color: #2e7d32;
            font-size: 1.8em;
            line-height: 1.3;
        }

        .reader-header p {
            margin-top: 5px;
            color: #4e6e5d;
            font-size: 1em;
        }

        .reader-actions {
            margin-top: 10px;
        }

        .reader-actions a {
            color: #2e7d32;
            text-decoration: underline;
            font-weight: 600;
            margin-right: 15px;
            font-size: 0.9em;
        }

        .pdf-container {
            width: 100%;
            height: calc(100vh - 120px);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .reader-header {
                padding: 15px 20px;
            }

            .reader-header h1 {
                font-size: 1.5em;
            }

            .reader-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .reader-actions a {
                font-size: 0.85em;
                margin-right: 0;
            }

            .pdf-container {
                height: calc(100vh - 100px);
            }
        }

        @media (max-width: 480px) {
            .reader-header {
                padding: 12px 15px;
            }

            .reader-header h1 {
                font-size: 1.3em;
            }

            .reader-actions a {
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
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

</body>
</html>
<?php