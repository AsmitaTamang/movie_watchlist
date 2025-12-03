<?php
// toggle_watched.php
// Flips watched_status between 0 (unwatched) and 1 (watched).

require_once 'includes/auth.php';
$user_id = requireAuth();                // Ensure user is logged in, get user_id
require_once 'dbconnect.php';

// Tell browser the response is JSON
header('Content-Type: application/json');

// Only accept POST requests for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$movie_id = $_POST['movie_id'] ?? null;

// Validate movie_id
if (!$movie_id) {
    echo json_encode(['success' => false, 'message' => 'Missing movie_id']);
    exit;
}

try {
    // ---------------------------------------------------------
    // Fetch current watched_status from DB
    // Ensures the movie belongs to the logged-in user
    // ---------------------------------------------------------
    $stmt = $pdo->prepare("SELECT watched_status FROM movies WHERE movie_id = ? AND user_id = ?");
    $stmt->execute([$movie_id, $user_id]);
    $current = $stmt->fetchColumn();

    // If no movie is found → invalid request
    if ($current === false) {
        echo json_encode(['success' => false, 'message' => 'Movie not found']);
        exit;
    }

    // ---------------------------------------------------------
    // Flip status:
    // - If current is 1 (watched) → set to 0 (unwatched)
    // - If current is 0 (unwatched) → set to 1 (watched)
    // ---------------------------------------------------------
    $newStatus = $current ? 0 : 1;

    // Save flipped status to DB
    $update = $pdo->prepare("UPDATE movies SET watched_status = ? WHERE movie_id = ? AND user_id = ?");
    $update->execute([$newStatus, $movie_id, $user_id]);

    // ---------------------------------------------------------
    // Return JSON response with new watched_status
    // ---------------------------------------------------------
    echo json_encode([
        'success'        => true,
        'watched_status' => (int)$newStatus
    ]);
    exit;

} catch (PDOException $e) {
    // Log internal error for debugging
    error_log("toggle_watched error: " . $e->getMessage());

    // Return safe error message to UI
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
?>
