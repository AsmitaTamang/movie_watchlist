<?php
// Require authentication helper — ensures user is logged in
// requireAuth() returns the logged-in user's ID or redirects to login
require_once 'includes/auth.php';
$user_id = requireAuth();

// Database connection
require_once 'dbconnect.php';

// ---------------------------------------------------
// Validate that a movie ID was provided in the URL
// If not, redirect back with an error message
// ---------------------------------------------------
if (!isset($_GET['id'])) {
    header("Location: dashboard.php?error=No+movie+selected");
    exit;
}

// Convert the movie ID to an integer for safety
$movie_id = (int)$_GET['id'];

try {
    // ---------------------------------------------------
    // Delete the movie ONLY if it belongs to this user
    // This prevents users from deleting other users' movies
    // ---------------------------------------------------
    $stmt = $pdo->prepare("DELETE FROM movies WHERE movie_id = ? AND user_id = ?");
    $stmt->execute([$movie_id, $user_id]);

    // ---------------------------------------------------
    // Also remove the deleted movie from folder_movies table
    // This prevents leftover orphan records in the system
    // ---------------------------------------------------
    $cleanup = $pdo->prepare("DELETE FROM folder_movies WHERE movie_id = ?");
    $cleanup->execute([$movie_id]);

    // Redirect back to the dashboard with a success message
    header("Location: dashboard.php?deleted=1");
    exit;

} catch (PDOException $e) {
    // If something goes wrong, log error and redirect with fail message
    error_log("Delete movie error: " . $e->getMessage());
    header("Location: dashboard.php?error=Failed+to+delete+movie");
    exit;
}
