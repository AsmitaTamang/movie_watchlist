<?php
// Start session to access logged-in user info
session_start();
// Include database connection
require_once 'dbconnect.php';

// Tell the browser that this script returns JSON
header('Content-Type: application/json');

// -------------------------------------------------------
// 1. Check login – only logged-in users can use comments API
// -------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// -------------------------------------------------------
// 2. Handle "get_comments" action (via ?action=get_comments)
// -------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'get_comments') {
    // Movie ID is required to load comments for a specific movie
    $movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
    
    // Validate movie ID
    if ($movie_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
        exit;
    }
    
    try {
        // Optional search text filter for comments content
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        // Optional rating filter (e.g. 5, 4.5, etc.)
        $rating_filter = isset($_GET['filter_rating']) ? $_GET['filter_rating'] : '';
        
        // ---------------------------------------------------
        // Build base query: comments + username for movie
        // ---------------------------------------------------
        $sql = "SELECT mc.*, u.username FROM movie_comments mc 
                JOIN users u ON mc.user_id = u.user_id 
                WHERE mc.movie_id = ?";
        $params = [$movie_id];
        
        // If a search term is provided, filter by comment text
        if (!empty($search)) {
            $sql .= " AND mc.comment_text LIKE ?";
            $params[] = "%$search%";
        }
        
        // If rating filter is set (and not "all"), filter by rating value
        if (!empty($rating_filter) && $rating_filter !== 'all') {
            $sql .= " AND mc.rating = ?";
            $params[] = (float)$rating_filter;
        }
        
        // Sort comments newest first
        $sql .= " ORDER BY mc.created_at DESC";
        
        // Execute the comments query
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ---------------------------------------------------
        // Get rating statistics for this movie:
        //  - avg_rating: average of all rating values
        //  - total_comments: count of all comments
        //  - total_ratings: count of comments that have a rating
        // ---------------------------------------------------
        $rating_stmt = $pdo->prepare("SELECT 
                AVG(rating) as avg_rating, 
                COUNT(*) as total_comments,
                COUNT(rating) as total_ratings
            FROM movie_comments 
            WHERE movie_id = ?");
        $rating_stmt->execute([$movie_id]);
        $rating_info = $rating_stmt->fetch();
        
        // Return comments + rating information as JSON
        echo json_encode([
            'success' => true, 
            'comments' => $comments,
            'rating_info' => $rating_info
        ]);
        
    } catch (PDOException $e) {
        // Log error for debugging, return generic error to client
        error_log("Database error in get_comments: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// If action is missing or not recognized, return error JSON
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
