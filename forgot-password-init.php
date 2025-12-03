<?php
require_once 'dbconnect.php';   // Load database connection

// ----------------------------------------------------------
// 1️⃣ Handle form submission (POST request)
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get email input and remove extra spaces
    $email = trim($_POST['email']);
    
    // Basic validation: email must not be empty
    if (empty($email)) {
        // Redirect back with an error message
        header("Location: forgot-password-init.php?message=Email is required&status=error");
        exit;
    }
    
    try {
        // ----------------------------------------------------------
        // 2️⃣ Check if the email exists in the database
        // ----------------------------------------------------------
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // ----------------------------------------------------------
            // 3️⃣ If user exists → store email in session
            //     This is needed for the next step (security questions)
            // ----------------------------------------------------------
            session_start();
            $_SESSION['reset_email'] = $email;
            
            // Redirect to the security question verification page
            header("Location: security-questions-verify.php");
            exit;
        } else {
            // Email not found in the database → show error
            header("Location: forgot-password-init.php?message=Email not found&status=error");
            exit;
        }
    } catch (PDOException $e) {
        // Database error (unexpected)
        header("Location: forgot-password-init.php?message=System error&status=error");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Power Rangers Movie Watchlist</title>

    <!-- Link to the page stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Main container for the form -->
    <div class="container">
        <div class="form-section">

            <h2>Forgot Your Password?</h2>
            <p class="form-subtitle">Enter your email and answer security questions to access your account</p>
            
            <!-- --------------------------------------------------------
                 4️⃣ Display error or success message (if redirected back)
                 -------------------------------------------------------- -->
            <?php if (isset($_GET['message'])): ?>
                <div class="message <?php echo $_GET['status'] ?? ''; ?>">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Email input form -->
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
                
                <button type="submit" class="btn">Continue to Security Questions</button>
            </form>
            
            <!-- Simple link back to login page -->
            <div class="login-link">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

</body>
</html>
