<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

$message = '';
$success = false;

// Check if token is provided
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Playfair+Display:wght@400;600&family=Quicksand:wght@300;500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #e8f5e3 0%, #c8e6c9 50%, #a5d6a7 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leaf {
            position: fixed;
            font-size: 2em;
            opacity: 0.3;
            animation: float 15s infinite ease-in-out;
            z-index: 1;
            pointer-events: none;
        }

        .leaf:nth-child(1) { left: 10%; animation-delay: 0s; }
        .leaf:nth-child(2) { left: 30%; animation-delay: 3s; }
        .leaf:nth-child(3) { left: 50%; animation-delay: 6s; }
        .leaf:nth-child(4) { left: 70%; animation-delay: 9s; }
        .leaf:nth-child(5) { left: 90%; animation-delay: 12s; }

        @keyframes float {
            0% { 
                transform: translateY(100vh) rotate(0deg); 
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% { 
                transform: translateY(-100px) rotate(360deg); 
                opacity: 0;
            }
        }

        .verify-container {
            padding: 20px;
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 550px;
        }

        .verify-box {
            background: rgba(255, 255, 255, 0.98);
            padding: 60px 50px;
            border-radius: 30px;
            box-shadow: 0 25px 70px rgba(46, 125, 50, 0.2);
            text-align: center;
            border: 3px solid #81c784;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .verify-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(46, 125, 50, 0.25);
        }

        .icon {
            font-size: 5em;
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        h1 {
            color: #2e7d32;
            font-size: 2.5em;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }

        .message {
            color: #66bb6a;
            font-size: 1.2em;
            line-height: 1.6;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .success-message {
            color: #2e7d32;
        }

        .error-message {
            color: #c62828;
        }

        .btn {
            display: inline-block;
            padding: 18px 40px;
            background: linear-gradient(135deg, #66bb6a, #43a047);
            color: white;
            text-decoration: none;
            border-radius: 18px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Quicksand', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(67, 160, 71, 0.5);
            background: linear-gradient(135deg, #43a047, #2e7d32);
        }

        @media (max-width: 768px) {
            .verify-box {
                padding: 50px 35px;
            }
            
            h1 {
                font-size: 2em;
            }

            .message {
                font-size: 1.1em;
            }

            .icon {
                font-size: 4em;
            }
        }

        @media (max-width: 480px) {
            .verify-container {
                padding: 15px;
            }

            .verify-box {
                padding: 40px 30px;
            }

            h1 {
                font-size: 1.8em;
            }
        }
    </style>
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