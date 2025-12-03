<?php

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
    // Redirect to user management page with error if no ID provided
    header("Location: admin_users.php?error=no_user_id");
    exit; // Stop script execution
}

// Prevent self-deletion - admin cannot delete their own account
if ($user_id == $_SESSION['user_id']) {
    // Redirect with error message if trying to delete own account
    header("Location: admin_users.php?error=cannot_delete_self");
    exit; // Stop script execution
}

try {
    // First, delete any related data (watchlists, reviews, etc.) if they exist
    // Example: if you have watchlists table
    // $pdo->prepare("DELETE FROM watchlists WHERE user_id = ?")->execute([$user_id]);
    
    // Then delete the user from users table
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Check if user was successfully deleted
    if ($stmt->rowCount() > 0) {
        // Redirect with success message if deletion successful
        header("Location: admin_users.php?success=deleted");
    } else {
        // Redirect with error if user not found
        header("Location: admin_users.php?error=user_not_found");
    }
    exit; // Stop script execution
} catch (PDOException $e) {
    // Redirect with database error message
    header("Location: admin_users.php?error=database");
    exit; // Stop script execution
}
?>