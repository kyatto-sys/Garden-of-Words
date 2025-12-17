<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

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
    die("This manuscript is private 🌱");
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
        }

        .reader-header p {
            margin-top: 5px;
            color: #4e6e5d;
        }

        .reader-actions {
            margin-top: 10px;
        }

        .reader-actions a {
            color: #2e7d32;
            text-decoration: underline;
            font-weight: 600;
            margin-right: 15px;
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