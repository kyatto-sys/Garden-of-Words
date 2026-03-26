<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

$message = '';
$success = false;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Find user with this token
    $query = "SELECT id, username, is_verified FROM users WHERE verification_token = '$token'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if ($user['is_verified'] == 1) {
            $message = "Your email has already been verified! You can now login.";
            $success = true;
        } else {
            // Update user as verified
            $update_query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = " . $user['id'];
            
            if (mysqli_query($conn, $update_query)) {
                $message = "Email verified successfully! You can now login to your account.";
                $success = true;
            } else {
                $message = "Verification failed. Please try again or contact support.";
            }
        }
    } else {
        $message = "Invalid or expired verification link!";
    }
} else {
    $message = "No verification token provided!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Garden of Words 🌿</title>
    <link rel="stylesheet" href="includes/generalstyles.css">
    <link rel="stylesheet" href="includes/styles.css">
</head>
<body>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <div class="verify-container">
        <div class="verify-box">
            <div class="icon"><?php echo $success ? '✅' : '❌'; ?></div>
            <h1><?php echo $success ? 'Verified!' : 'Oops!'; ?></h1>
            <p class="message <?php echo $success ? 'success-message' : 'error-message'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
            
            <?php if ($success): ?>
                <a href="login.php" class="btn">Go to Login 🍵</a>
            <?php else: ?>
                <a href="register.php" class="btn">Back to Register 🌱</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>