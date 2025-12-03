<?php
// Include authentication helper and ensure the user is logged in
// requireAuth() returns the user ID or redirects to login
require_once 'includes/auth.php';
$user_id = requireAuth();

// Connect to the database
require_once 'dbconnect.php';

// Retrieve the logged-in user's ID from session (fallback to 1 if missing)
// NOTE: This variable ($UserID) is actually not used in the delete logic — kept as-is.
$UserID = $_SESSION['UserId'] ?? 1;

// Get the folder ID from the URL (GET request)
// If no ID is provided, $id becomes null
$id = $_GET['id'] ?? null;

// If a valid folder ID was provided, attempt to delete the folder
if ($id) {
    // Prepare a DELETE statement that deletes ONLY folders belonging to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM folders WHERE FolderID = ? AND user_id = ?");
    
    // Execute the deletion (prevents users from deleting someone else’s folder)
    $stmt->execute([$id, $user_id]);
}

// After deletion, redirect user back to the categories/folders page
header("Location: your_categories.php");
exit;
?>
