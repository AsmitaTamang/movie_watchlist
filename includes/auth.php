<?php
// includes/auth.php
// Authentication and authorization utility functions

/**
 * Requires user authentication for protected pages
 * Checks if user is logged in by verifying session
 * Redirects to login page if user is not authenticated
 * 
 */
function requireAuth() {
    
    session_start();
    
    // Check if user_id exists in session (user is logged in)
    if (!isset($_SESSION['user_id'])) {
        // User is not authenticated - redirect to login page
        header("Location: login.php");
        exit; // Stop script execution immediately after redirect
    }
    
    // Return the authenticated user's ID for use in the calling script
    return $_SESSION['user_id'];
}

