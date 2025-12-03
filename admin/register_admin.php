<?php
// Include database connection file for database operations
require_once '../dbconnect.php';
// Start session to access user session data
session_start();

// Check if form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim form inputs
    $username = trim($_POST['userName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $adminKey = trim($_POST['adminKey'] ?? '');
    
    // Array to store validation errors
    $errors = [];
    
    // Validation - check required fields
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    // Check minimum password length
    elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    // Check if passwords match
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    
    // Admin key validation - verify admin access key
    $validAdminKey = "PowerRangers5!"; // Change this to your secret key
    $isAdmin = ($adminKey === $validAdminKey);
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        try {
            // Check if username or email already exists in database
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            
            // If user already exists, add error
            if ($checkStmt->fetch()) {
                $errors[] = "Username or email already registered!";
            } else {
                // Hash password for secure storage
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                // Determine user role based on admin key validation
                $role = $isAdmin ? 'admin' : 'user';
                
                // Insert new user into database
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $role]);
                
                // Auto-login after successful registration
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                
                // Redirect based on user role
                if ($isAdmin) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit; // Stop script execution
            }
        } catch (PDOException $e) {
            // Handle database errors
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
    <title>Admin Registration - Power Rangers Movie Watchlist</title>
    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <!-- Main container for admin registration page -->
    <div class="container">
        <!-- Left section: Welcome message and features -->
        <div class="welcome-section">
            <!-- Company logo -->
            <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="company-logo">
            <h1>Power Rangers Movie Watchlist</h1>
            <p class="slogan">Create Admin Account</p>
            <!-- List of admin features -->
            <ul class="features">
                <li>Full access to user management</li>
                <li>System administration capabilities</li>
                <li>Advanced analytics and reporting</li>
                <li>Complete system control</li>
            </ul>
        </div>
        
        <!-- Right section: Registration form -->
        <div class="form-section">
            <h2>Create Admin Account</h2>
            <p class="form-subtitle">Enter admin key to create administrator account</p>
            
            <!-- Display validation errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <!-- Loop through each error and display it -->
                    <?php foreach ($errors as $error): ?>
                        ❌ <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Admin registration form -->
            <form method="POST">
                <!-- Username input field -->
                <div class="form-group">
                    <label for="userName">Username</label>
                    <input type="text" id="userName" name="userName" value="<?php echo htmlspecialchars($_POST['userName'] ?? ''); ?>" required>
                </div>
               
                <!-- Email input field -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <!-- Password input field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <!-- Password confirmation field -->
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                
                <!-- Admin key input field for authorization -->
                <div class="form-group">
                    <label for="adminKey">Admin Key</label>
                    <input type="password" id="adminKey" name="adminKey" placeholder="Enter admin access key" required>
                </div>
                
                <!-- Submit button -->
                <button type="submit" class="btn">Create Admin Account</button>
            </form>
            
            <!-- Link to regular user registration -->
            <div class="login-link">
                Want regular user account? <a href="index.php">Sign Up Here</a>
            </div>
        </div>
    </div>
</body>
</html>