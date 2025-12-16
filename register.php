<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/email_config.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if username already exists
        $check_username = "SELECT id FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $check_username);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username already taken!";
        } else {
            // Check if email already exists
            $check_email = "SELECT id FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($result) > 0) {
                $error = "Email already registered!";
            } else {
                // Generate verification token
                $verification_token = bin2hex(random_bytes(32));
                
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, email, password, verification_token, is_verified, created_at) 
                                VALUES ('$username', '$email', '$hashed_password', '$verification_token', 0, NOW())";
                
                if (mysqli_query($conn, $insert_query)) {
                    // Send verification email using PHPMailer
                    $email_sent = sendVerificationEmail($email, $username, $verification_token);
                    
                    if ($email_sent) {
                        $success = "Registration successful! Please check your email (<strong>$email</strong>) to verify your account. Don't forget to check your spam folder!";
                    } else {
                        $success = "Registration successful! However, we couldn't send the verification email. Please contact support. (Check error logs for details)";
                    }
                } else {
                    $error = "Registration failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Garden of Words 🌿</title>
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
            padding: 40px 0;
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

        .auth-container {
            padding: 20px;
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px;
        }

        .auth-box {
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 45px;
            border-radius: 30px;
            box-shadow: 0 25px 70px rgba(46, 125, 50, 0.2);
            text-align: center;
            border: 3px solid #81c784;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .auth-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(46, 125, 50, 0.25);
        }

        .auth-box h1 {
            color: #2e7d32;
            font-size: 2.8em;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .auth-box .subtitle {
            color: #66bb6a;
            margin-bottom: 35px;
            font-size: 1.15em;
            font-weight: 500;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: #388e3c;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1em;
            letter-spacing: 0.3px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #a5d6a7;
            border-radius: 18px;
            font-size: 1.05em;
            font-family: 'Quicksand', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: #66bb6a;
            box-shadow: 0 0 20px rgba(102, 187, 106, 0.4);
            transform: translateY(-2px);
        }

        .input-group input::placeholder {
            color: #a5d6a7;
            font-weight: 400;
        }

        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #66bb6a, #43a047);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Quicksand', sans-serif;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(67, 160, 71, 0.5);
            background: linear-gradient(135deg, #43a047, #2e7d32);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .switch-text {
            margin-top: 30px;
            color: #66bb6a;
            font-size: 1em;
            font-weight: 500;
        }

        .switch-text a {
            color: #2e7d32;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }

        .switch-text a:hover {
            color: #1b5e20;
            border-bottom-color: #1b5e20;
        }

        .alert {
            padding: 15px 22px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 1em;
            text-align: left;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #c8e6c9;
            color: #1b5e20;
            border: 2px solid #81c784;
        }

        .alert-error {
            background-color: #ffcdd2;
            color: #c62828;
            border: 2px solid #e57373;
        }

        @media (max-width: 768px) {
            .auth-box {
                padding: 40px 30px;
            }
            
            .auth-box h1 {
                font-size: 2.2em;
            }

            .auth-box .subtitle {
                font-size: 1em;
            }
            
            .input-group {
                margin-bottom: 20px;
            }

            .input-group input {
                padding: 14px 18px;
                font-size: 1em;
            }

            .btn {
                padding: 16px;
                font-size: 1.1em;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 0;
            }

            .auth-container {
                padding: 15px;
            }

            .auth-box {
                padding: 35px 25px;
            }

            .auth-box h1 {
                font-size: 1.9em;
            }

            .input-group {
                margin-bottom: 18px;
            }
        }

                .terms-group {
            margin-top: 10px;
        }

        .terms-label {
            font-size: 0.9em;
            color: #4e6e5d;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.4;
        }

        .terms-label input {
            accent-color: #66bb6a;
        }

        .terms-label a {
            color: #2e7d32;
            font-weight: 600;
            text-decoration: underline;
        }

        .terms-label a:hover {
            color: #1b5e20;
        }

    </style>
</head>
<body>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <div class="auth-container">
        <div class="auth-box">
            <h1>🌿 Garden of Words</h1>
            <p class="subtitle">Join our garden and share your stories</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Choose a username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Create a password (min. 6 characters)"
                           required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Confirm your password"
                           required>
                </div>

                <div class="input-group terms-group">
                    <label class="terms-label">
                        <input type="checkbox" name="agree" required> I agree to the 
                        <a href="terms.php" target="_blank">Garden Rules</a> and
                        <a href="privacy.php" target="_blank">Privacy Promise</a>
                    </label>
                </div>

                
                <button type="submit" class="btn">Register 🌱</button>
            </form>
            
            <p class="switch-text">
                Already have an account? 
                <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>