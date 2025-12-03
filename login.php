<?php
session_start(); 
require_once 'dbconnect.php';

// Checks If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // removes the extra spaces from beginning and the end
    $email = trim($_POST['email']);
    // gets the password from the form
    $password = $_POST['password'];
    // POST is user submitted a form to this page
    
    // Basic validation
    // checking if the email and password is empty
    if (empty($email) || empty($password)) {
        // redirects user to the login page if the status is error
        header("Location: login.php?message=Email and password are required&status=error");
        exit; // exiting the page
    }
    
    try {
        // Check if user exists - UPDATED QUERY TO INCLUDE ROLE
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // verification with the existing database
        if ($user && password_verify($password, $user['password'])) {
            // Login successful - SESSION ALREADY STARTED
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            
            // separates user based on the role
            $_SESSION['role'] = $user['role'] ?? 'user';
            
            // SECURITY: Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
    
            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            header("Location: login.php?message=Invalid email or password&status=error");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: login.php?message=Database error: " . $e->getMessage() . "&status=error");
        exit;
    }
} else {
    // Display the login form (HTML part from above)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Rangers Movie Watchlist - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <div class="logo-container">
                <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="company-logo">
            </div>
            <h1>Power Rangers Movie Watchlist</h1>
            <p class="slogan">Welcome Back!</p>
            <ul class="features">
                <li>Access your personalized watchlists</li>
                <li>Continue tracking your movie journey</li>
                <li>Discover new recommendations</li>
                <li>Share updates with friends</li>
            </ul>
        </div>
        
        <div class="form-section">
            <h2>Login to Your Account</h2>
            <p class="form-subtitle">Welcome back! Please enter your details</p>
            
            <!-- Show success/error messages -->
            <?php if (isset($_GET['message'])): ?>
                <div class="message <?php echo $_GET['status'] ?? ''; ?>">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" required>
                </div>
                
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        <label for="rememberMe">Remember me</label>
                    </div>
                    <a href="forgot-password-init.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div class="login-link">
                Don't have an account? <a href="index.php">Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php } ?>