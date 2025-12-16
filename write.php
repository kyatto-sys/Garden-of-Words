<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start Writing — Garden of Words</title>
    <link rel="stylesheet" href="includes/write.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">🌿 Garden of Words</div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="my-postcards.php">My Garden</a>
    </div>
</nav>

<div class="container writing-container">

    <h1>Leave something here</h1>
    <p class="subtitle">It doesn’t have to be finished. Or understood.</p>

    <form method="POST" action="save-postcard.php">

        <input type="text" name="title" placeholder="A title (optional)" class="soft-input">

        <textarea name="content" rows="12" placeholder="Write what you hear inside. Even fragments are enough." required></textarea>

        <div class="options">
            <label>
                <input type="radio" name="visibility" value="private" checked>
                Keep this to myself
            </label>
            <label>
                <input type="radio" name="visibility" value="public">
                Share with the garden
            </label>
        </div>

        <div class="actions">
            <button type="submit" class="save-btn">Save quietly</button>
        </div>

    </form>

</div>

</body>
</html>
