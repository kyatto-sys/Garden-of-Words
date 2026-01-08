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

                $password = $_POST['password'];

                $errors = [];

                if (strlen($password) < 8) {
                    $errors[] = "Password must be at least 8 characters long.";
                }

                if (!preg_match('/[A-Z]/', $password)) {
                    $errors[] = "Password must contain at least one uppercase letter.";
                }

                if (!preg_match('/[a-z]/', $password)) {
                    $errors[] = "Password must contain at least one lowercase letter.";
                }

                if (!preg_match('/[0-9]/', $password)) {
                    $errors[] = "Password must contain at least one number.";
                }

                if (!preg_match('/[\W_]/', $password)) {
                    $errors[] = "Password must contain at least one special character.";
                }

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        echo "<p style='color:red;'>$error</p>";
                    }
                    exit;
                }
                
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
    <link rel="stylesheet" href="includes/generalstyles.css">
    <link rel="stylesheet" href="includes/styles.css">

</head>
<body class="auth-page">
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <div class="auth-container">
        <div class="auth-box">
            <h1>Garden of Words</h1>
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
                           minlength="8"
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                           placeholder="At least 8 chars, uppercase, lowercase, number, special character"
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
                    <input type="checkbox" name="agree" required>
                        <label class="terms-label">
                        I agree to the 
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
