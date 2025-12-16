<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';


if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password!";
    } else {
        // Check if user exists
        $query = "SELECT id, username, password, is_verified FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if ($user['is_verified'] == 0) {
                    $error = "Please verify your email before logging in. Check your inbox!";
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    header("Location: home.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Garden of Words 🌿</title>
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

        /* Floating Leaves Background */
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

        /* Container */
        .auth-container {
            padding: 20px;
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px;
        }

        /* Auth Box */
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

        /* Input Groups */
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

        /* Button */
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

        /* Switch Text */
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

        /* Alert Messages */
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

        /* Responsive Design */
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
            .auth-container {
                padding: 15px;
            }

            .auth-box {
                padding: 35px 25px;
            }

            .auth-box h1 {
                font-size: 1.9em;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Leaves Background -->
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <div class="auth-container">
        <div class="auth-box">
            <h1>🌿 Garden of Words</h1>
            <p class="subtitle">Share your heart through our postcards</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password"
                           required>
                </div>
                
                <button type="submit" class="btn">Login 🍵</button>
            </form>
            
            <p class="switch-text">
                Don't have an account? 
                <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>