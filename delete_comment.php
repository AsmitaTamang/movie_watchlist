<?php
// Start session so we can access the logged-in user's ID
session_start();

// Include database connection ($pdo)
require_once 'dbconnect.php';

// Tell browser the response will be JSON
header('Content-Type: application/json');

// -------------------------------------------------------
// 1. Ensure the user is logged in
// -------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// -------------------------------------------------------
// 2. Ensure the request method is POST (required for deletion)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read the comment ID coming from AJAX delete request
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

// Logged-in user's ID
$user_id = $_SESSION['user_id'];

// Validate comment_id
if ($comment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
    exit;
}

try {
    // -------------------------------------------------------
    // 3. Check if the comment exists and belongs to this user
    // -------------------------------------------------------
    $check_stmt = $pdo->prepare("SELECT user_id FROM movie_comments WHERE comment_id = ?");
    $check_stmt->execute([$comment_id]);
    $comment = $check_stmt->fetch();
    
    // If comment does not exist in the DB
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    // Prevent users from deleting other users' comments
    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
        exit;
    }
    
    // -------------------------------------------------------
    // 4. Delete the comment from the database
    // -------------------------------------------------------
    $delete_stmt = $pdo->prepare("DELETE FROM movie_comments WHERE comment_id = ?");
    $delete_stmt->execute([$comment_id]);
    
    // Respond with success for JavaScript to update UI
    echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
    
} catch (PDOException $e) {
    // Log internal DB error for debugging
    error_log("Delete comment error: " . $e->getMessage());

    // Send safe, generic error message to the frontend
    echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
}
?>
