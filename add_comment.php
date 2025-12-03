<?php
session_start();
require_once 'dbconnect.php';

// Tell the browser the response is JSON
header('Content-Type: application/json');

// Enable detailed error logging to PHP error log
error_log("=== ADD COMMENT REQUEST ===");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// -------------------------------------------------------
// 1. Basic auth check – user must be logged in
// -------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Only allow POST requests (no GET, etc.)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// -------------------------------------------------------
// 2. Read and validate input values
// -------------------------------------------------------

// Movie ID (force to integer, default 0 if missing)
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;

// Comment text – trim spaces
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

// Rating is optional, can be null
$rating = isset($_POST['rating']) ? $_POST['rating'] : null;

error_log("Received - movie_id: $movie_id, comment_text: '$comment_text', rating: " . ($rating ?? 'null'));

// Convert rating to float if provided, and validate range
if (!empty($rating) && $rating !== 'null') {
    $rating = (float)$rating;

    // Rating must be between 0.5 and 5.0 (inclusive)
    if ($rating < 0.5 || $rating > 5.0) {
        error_log("Invalid rating: $rating");
        echo json_encode(['success' => false, 'message' => 'Rating must be between 0.5 and 5.0']);
        exit;
    }
}

// Current logged in user's ID
$user_id = $_SESSION['user_id'];

// -------------------------------------------------------
// 3. Validation rules
// -------------------------------------------------------
$errors = [];

// movie_id must be a positive integer
if ($movie_id <= 0) {
    $errors[] = 'Invalid movie ID';
    error_log("Invalid movie ID: $movie_id");
}

// Comment text must not be empty
if (empty($comment_text)) {
    $errors[] = 'Comment text is required';
    error_log("Empty comment text received");
}

// Comment length limit – max 1000 characters
if (strlen($comment_text) > 1000) {
    $errors[] = 'Comment cannot exceed 1000 characters';
    error_log("Comment too long: " . strlen($comment_text));
}

// -------------------------------------------------------
// 4. Check that the movie exists and belongs to this user
// -------------------------------------------------------
try {
    $movie_check = $pdo->prepare("SELECT movie_id FROM movies WHERE movie_id = ? AND user_id = ?");
    $movie_check->execute([$movie_id, $user_id]);

    // If no row found → movie doesn't exist or belongs to another user
    if (!$movie_check->fetch()) {
        $errors[] = 'Movie not found or access denied';
        error_log("Movie not found or access denied: movie_id=$movie_id, user_id=$user_id");
    }
} catch (PDOException $e) {
    error_log("Movie check error: " . $e->getMessage());
    $errors[] = 'Database error';
}

// If there are any validation errors → return them and stop
if (!empty($errors)) {
    error_log("Validation errors: " . implode(', ', $errors));
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// -------------------------------------------------------
// 5. Insert the comment into movie_comments table
// -------------------------------------------------------
try {
    error_log("Attempting to insert comment...");
    
    // Insert comment: movie_id, user_id, text, rating
    $stmt = $pdo->prepare("INSERT INTO movie_comments (movie_id, user_id, comment_text, rating) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$movie_id, $user_id, $comment_text, $rating]);
    
    error_log("Insert result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        // Get ID of the new comment
        $new_comment_id = $pdo->lastInsertId();
        error_log("New comment ID: $new_comment_id");
        
        // Fetch the inserted comment along with the username of the author
        $comment_stmt = $pdo->prepare("SELECT mc.*, u.username FROM movie_comments mc JOIN users u ON mc.user_id = u.user_id WHERE mc.comment_id = ?");
        $comment_stmt->execute([$new_comment_id]);
        $new_comment = $comment_stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Comment added successfully: " . print_r($new_comment, true));
        
        // Return JSON with the new comment and associated movie_id
        echo json_encode([
            'success' => true, 
            'message' => 'Comment added successfully',
            'comment' => $new_comment, 
            'movie_id' => $movie_id 
        ]);
    } else {
        // Insert returned false, but no exception thrown
        error_log("Insert failed without exception");
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
    
} catch (PDOException $e) {
    // Any database error while inserting
    error_log("Add comment error: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    echo json_encode(['success' => false, 'message' => 'Failed to add comment: Database error']);
}
?>
