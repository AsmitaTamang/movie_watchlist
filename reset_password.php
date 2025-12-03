<?php
session_start();
require_once 'dbconnect.php';

// -------------------------------------------------------------
// Ensure user has passed the security question verification step
// If not verified or missing email data, redirect them back
// -------------------------------------------------------------
if (!isset($_SESSION['security_verified']) || !$_SESSION['security_verified'] || !isset($_SESSION['reset_email'])) {
    header("Location: forgot-password-init.php?message=Please complete security verification first&status=error");
    exit;
}

// -------------------------------------------------------------
// Handle password reset when form is submitted (POST request)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // -------------------------------
    // Validate password requirements
    // -------------------------------
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Ensure both input passwords match
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no validation errors, attempt password update
    if (empty($errors)) {
        try {
            // Hash new password securely
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password using stored email from reset process
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->execute([$hashed_password, $_SESSION['reset_email']]);
            
            // -----------------------------------------------
            // Clear all session data related to password reset
            // -----------------------------------------------
            unset($_SESSION['security_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_questions']);
            unset($_SESSION['reset_user_id']);
            
            // Redirect user to login with success message
            header("Location: login.php?message=Password reset successfully! Please login with your new password&status=success");
            exit;
            
        } catch (PDOException $e) {
            // Database-related error
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Power Rangers Movie Watchlist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h2>Reset Your Password</h2>
            <p class="form-subtitle">Enter your new password</p>
            
            <!-- Display validation or system errors -->
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $error): ?>
                        ❌ <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Password reset form -->
            <form method="POST">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           placeholder="Enter new password (min 6 characters)" 
                           required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your new password" 
                           required>
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
            
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
