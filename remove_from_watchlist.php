<?php
require_once 'includes/auth.php';   // Ensure authentication system is loaded
$user_id= requireAuth();            // Require user to be logged in

require_once 'dbconnect.php';       // Connect to database

// checking login
if (!isset($_SESSION['user_id'])) {  // If user somehow isn't logged in
    header("Location: login.php");   // Redirect to login page
    exit;
}

$user_id = $_SESSION['user_id'];     // Store logged-in user's ID

// reading URL values
$watchlist_id = isset($_GET['watchlist_id']) ? intval($_GET['watchlist_id']) : 0;  // Watchlist ID from URL
$movie_id     = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;          // Movie ID from URL

// basic validation
if ($watchlist_id <= 0 || $movie_id <= 0) {   // Ensure IDs are valid
    die("Invalid request.");                  // Stop execution if invalid
}

try {
    // delete entry from correct table
    $stmt = $pdo->prepare("
        DELETE FROM watchlist_item
        WHERE watchlist_id = ? AND movie_id = ?
    ");                                       // SQL to remove a movie from the watchlist

    $stmt->execute([$watchlist_id, $movie_id]);   // Execute delete query

    // redirect with success flag
    header("Location: view_watchlist.php?id=$watchlist_id&removed=1"); // Redirect back with success message
    exit;

} catch (PDOException $e) {
    echo "Error removing movie: " . $e->getMessage();   // Show DB error if something goes wrong
    exit;
}
