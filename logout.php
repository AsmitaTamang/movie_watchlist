
<?php

// This file securely terminates user sessions and redirects to login page

session_start();

// Clear all session variables - remove user data from current session
$_SESSION = array();

// Delete the session cookie from user's browser
if (ini_get("session.use_cookies")) {
    // Get current session cookie parameters
    $params = session_get_cookie_params();
    
    // Set cookie expiration to past time to delete it
    setcookie(
        session_name(),      // Name of the session cookie (usually 'PHPSESSID')
        '',                 // Empty value
        time() - 42000,     // Expire time in past (forces deletion)
        $params["path"],    // Cookie path (where it's valid)
        $params["domain"],  // Cookie domain (where it's valid)
        $params["secure"],  // HTTPS only if secure
        $params["httponly"] // HTTP only (not accessible via JavaScript)
    );
}

// Completely destroy the session on server side
session_destroy();

// Redirect to login page with success message
header("Location: login.php?message=You have been logged out&status=success");
exit; // Stop script execution after redirect
?>
