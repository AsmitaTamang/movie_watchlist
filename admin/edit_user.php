<?php
// Include database connection file for database operations
require_once '../dbconnect.php';
// Start session to access user session data
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php?error=not_logged_in");
    exit; // Stop script execution
}

// Check if user has admin role
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page if not admin
    header("Location: ../login.php?error=admin_access_required");
    exit; // Stop script execution
}

// Get user ID from URL parameter
$user_id = $_GET['id'] ?? null;
// Check if user ID is provided
if (!$user_id) {
    // Redirect to user management page if no ID provided
    header("Location: admin_users.php");
    exit; // Stop script execution
}

// Fetch user data from database
try {
    // Prepare SQL statement to get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Check if user exists
    if (!$user) {
        // Redirect with error if user not found
        header("Location: admin_users.php?error=user_not_found");
        exit; // Stop script execution
    }
} catch (PDOException $e) {
    // Display database error and stop execution
    die("Database error: " . $e->getMessage());
}

// Handle form submission when updating user data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim form inputs
    $username = filter_var(trim($_POST['username']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    // Check if active checkbox is checked
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Array to store validation errors
    $errors = [];
    
    // Validation - check required fields
    if (empty($username) || empty($email)) {
        $errors[] = "Username and email are required";
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check for duplicate username/email (excluding current user)
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $checkStmt->execute([$username, $email, $user_id]);
    if ($checkStmt->fetch()) {
        $errors[] = "Username or email already exists";
    }
    
    // If no validation errors, update user in database
    if (empty($errors)) {
        try {
            // Prepare update statement
            $updateStmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, is_active = ? WHERE user_id = ?");
            $updateStmt->execute([$username, $email, $is_active, $user_id]);
            
            // Redirect with success message after update
            header("Location: admin_users.php?success=updated");
            exit; // Stop script execution
        } catch (PDOException $e) {
            // Add database error to errors array
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <!-- Main container for edit user form -->
    <div class="container">
        <h2>Edit User</h2>
        
        <!-- Display validation errors if any -->
        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <!-- Loop through each error and display it -->
                <?php foreach ($errors as $error): ?>
                    ❌ <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Edit user form -->
        <form method="POST" class="user-form">
            <!-- Username input field -->
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <!-- Email input field -->
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <!-- Active status checkbox -->
            <div class="form-group checkbox">
                <input type="checkbox" id="is_active" name="is_active" 
                       <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active">Active User</label>
            </div>
            
            <!-- Form action buttons -->
            <button type="submit" class="btn">Update User</button>
            <a href="admin_users.php" class="btn cancel">Cancel</a>
        </form>
    </div>
</body>
</html>