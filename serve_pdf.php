<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

$q = "
    SELECT * FROM manuscripts
    WHERE id = $id
    LIMIT 1
";
$r = mysqli_query($conn, $q);

if (!$r || mysqli_num_rows($r) === 0) {
    die("Not found.");
}

$m = mysqli_fetch_assoc($r);

if (!$m['is_public'] && $m['user_id'] != $user_id) {
    die("Unauthorized.");
}

$file = $_SERVER['DOCUMENT_ROOT'] . $m['filepath'];

if (!file_exists($file)) {
    die("File missing.");
}

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . basename($file) . "\"");
header("Content-Length: " . filesize($file));

readfile($file);
exit;
