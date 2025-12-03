<?php

require_once 'includes/auth.php';  // Load authentication helper
$user_id = requireAuth();          // Ensure the user is logged in
require_once 'dbconnect.php';      // Connect to database

// Fallback in case session user_id is missing
$user_id = $_SESSION['user_id'] ?? 1;

// Get movie and folder IDs from the URL
$movie_id = $_GET['movie_id'] ?? null;
$folder_id = $_GET['folder_id'] ?? null;

// Only proceed if both IDs exist
if ($movie_id && $folder_id) {
    try {
        // Check if the folder belongs to the logged-in user
        $check_stmt = $pdo->prepare("SELECT * FROM folders WHERE FolderID = ? AND user_id = ?");
        $check_stmt->execute([$folder_id, $user_id]);
        $folder = $check_stmt->fetch();
        
        // If folder exists and belongs to user → allow deletion
        if ($folder) {
            $delete_stmt = $pdo->prepare("DELETE FROM folder_movies WHERE folder_id = ? AND movie_id = ?");
            $delete_stmt->execute([$folder_id, $movie_id]);
        }
    } catch (PDOException $e) {
        // Error is silently ignored (optional error logging could go here)
    }
}

// Redirect back to the folder view page
header("Location: view_folder.php?id=" . urlencode($folder_id));
exit;
?>
