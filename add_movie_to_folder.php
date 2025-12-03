<?php
// Require authentication helper and ensure the user is logged in
require_once 'includes/auth.php';
$user_id = requireAuth();

// Database connection
require_once 'dbconnect.php';

// Only handle POST requests (form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle both lowercase and uppercase variations of folder ID from form input
    // Some forms might send "FolderID", others "folder_id"
    $folder_id = $_POST['FolderID'] ?? $_POST['folder_id'] ?? null;

    // Movie ID should be sent as "movie_id"
    $movie_id  = $_POST['movie_id'] ?? null;

    // Proceed only if both folder and movie IDs are present
    if ($folder_id && $movie_id) {
        try {
            // First, check if this movie is already in this folder
            $check = $pdo->prepare("SELECT COUNT(*) FROM folder_movies WHERE FolderID = ? AND movie_id = ?");
            $check->execute([$folder_id, $movie_id]);
            $exists = $check->fetchColumn();

            if (!$exists) {
                // If not already there, insert a new record linking folder and movie
                $insert = $pdo->prepare("INSERT INTO folder_movies (FolderID, movie_id) VALUES (?, ?)");
                $insert->execute([$folder_id, $movie_id]);

                // Store a success message in session to show on the next page load
                $_SESSION['success'] = "Movie added to folder successfully!";
            } else {
                // If it already exists, set an informational message instead
                $_SESSION['info'] = "Movie is already in this folder.";
            }

        } catch (PDOException $e) {
            // On any database error, store an error message in the session
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        // If folder_id or movie_id is missing, set an error message
        $_SESSION['error'] = "Missing folder or movie information.";
    }

    // Redirect back to the folder view page after processing
    // The folder ID is passed in the query string so the page can load that folder
    header("Location: view_folder.php?id=" . urlencode($folder_id));
    exit;
}
?>
