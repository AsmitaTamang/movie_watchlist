<?php
// Start session so we can access the logged-in user's ID
session_start();
// Include database connection (provides $pdo)
require_once 'dbconnect.php';

// Set JSON header so the frontend knows this is a JSON response
header('Content-Type: application/json');

// -------------------------------------------------------
// 1. Check if user is logged in
// -------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// -------------------------------------------------------
// 2. Only allow POST requests (editing should not use GET)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// -------------------------------------------------------
// 3. Get and validate input values
// -------------------------------------------------------

// Comment ID – must be a valid integer > 0
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

// New comment text entered by user (trim spaces)
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

// Rating can be optional (null allowed)
$rating = isset($_POST['rating']) ? $_POST['rating'] : null;

// If rating is provided, convert to float and validate its range
if (!empty($rating)) {
    $rating = (float)$rating;
    // Rating must be within 0.5 - 5.0
    if ($rating < 0.5 || $rating > 5.0) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 0.5 and 5.0']);
        exit;
    }
}

// Logged-in user's ID
$user_id = $_SESSION['user_id'];

// -------------------------------------------------------
// 4. Do basic validation on comment data
// -------------------------------------------------------
$errors = [];

// Comment ID must be valid
if ($comment_id <= 0) {
    $errors[] = 'Invalid comment ID';
}

// Comment text is required (can't be empty)
if (empty($comment_text)) {
    $errors[] = 'Comment text is required';
}

// Limit comment length
if (strlen($comment_text) > 1000) {
    $errors[] = 'Comment cannot exceed 1000 characters';
}

// If any validation errors exist, return them and stop
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // -------------------------------------------------------
    // 5. Verify that this comment belongs to the logged-in user
    // -------------------------------------------------------
    $check_stmt = $pdo->prepare("SELECT user_id FROM movie_comments WHERE comment_id = ?");
    $check_stmt->execute([$comment_id]);
    $comment = $check_stmt->fetch();
    
    // If comment does not exist
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    // Prevent users from editing other people’s comments
    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You can only edit your own comments']);
        exit;
    }
    
    // -------------------------------------------------------
    // 6. Update the comment text and rating
    // -------------------------------------------------------
    // updated_at is set to CURRENT_TIMESTAMP in database
    $update_stmt = $pdo->prepare(
        "UPDATE movie_comments 
         SET comment_text = ?, rating = ?, updated_at = CURRENT_TIMESTAMP 
         WHERE comment_id = ?"
    );
    $update_stmt->execute([$comment_text, $rating, $comment_id]);
    
    // -------------------------------------------------------
    // 7. Fetch the updated comment plus username to return to frontend
    // -------------------------------------------------------
    $comment_stmt = $pdo->prepare(
        "SELECT mc.*, u.username 
         FROM movie_comments mc 
         JOIN users u ON mc.user_id = u.user_id 
         WHERE mc.comment_id = ?"
    );
    $comment_stmt->execute([$comment_id]);
    $updated_comment = $comment_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send success response with updated comment data
    echo json_encode([
        'success' => true, 
        'message' => 'Comment updated successfully',
        'comment' => $updated_comment
    ]);
    
} catch (PDOException $e) {
    // Log internal DB error and send generic message back
    error_log("Edit comment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update comment: ' . $e->getMessage()]);
}
?>
