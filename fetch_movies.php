<?php
session_start();
require_once 'dbconnect.php';

// ------------------------------------------------------
// 1. Security: Only logged-in users can fetch movies
// ------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:#bbb;text-align:center;'>Please log in to view movies.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

// ------------------------------------------------------
// 2. Read filter values from GET request
//    These come from dashboard_filter.js (search, genre, year)
// ------------------------------------------------------
$search = $_GET['search'] ?? '';
$genre  = $_GET['genre'] ?? '';
$year   = $_GET['year'] ?? '';

// ------------------------------------------------------
// 3. Build WHERE conditions dynamically based on filters
//    Each filter adds extra SQL conditions
// ------------------------------------------------------
$where  = ["m.user_id = ?"];  // Always restrict by user ID
$params = [$user_id];         // First SQL parameter

if ($search) {
    $where[]  = "m.title LIKE ?";
    $params[] = "%$search%";
}

if ($genre) {
    $where[]  = "m.genre = ?";
    $params[] = $genre;
}

if ($year) {
    $where[]  = "m.release_year = ?";
    $params[] = $year;
}

// ------------------------------------------------------
// 4. Query movies + count how many comments each movie has
//    - Subquery calculates comments_count per movie
//    - ORDER BY newest first
// ------------------------------------------------------
$sql = "SELECT m.*, 
               (SELECT COUNT(*) FROM movie_comments mc WHERE mc.movie_id = m.movie_id) AS comments_count
        FROM movies m
        WHERE " . implode(" AND ", $where) . "
        ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------
// If no movies found → show user-friendly message
// ------------------------------------------------------
if (!$movies) {
    echo "<p style='color:#bbb;text-align:center;'>No movies found matching your criteria.</p>";
    exit;
}

// ------------------------------------------------------
// 5. Render each movie card
//    This creates the card layout shown on the dashboard
// ------------------------------------------------------
foreach ($movies as $m) {

    // Default fallback poster
    $posterPath = 'assets/no-poster.png';
    $poster = $m['poster'] ?? '';
    
    // If the movie has an uploaded poster, try to load it
    if (!empty($poster)) {
        // Ensure correct folder path (uploads/)
        if (!str_starts_with($poster, 'uploads/')) {
            $poster = 'uploads/' . $poster;
        }
        // Use uploaded file if it exists
        if (file_exists($poster)) {
            $posterPath = $poster;
        }
    }

    // ------------------------------------------------------
    // Comment badge (only show number if > 0)
    // ------------------------------------------------------
    $commentCount = $m['comments_count'] > 0 
        ? "<span class='comments-count'>{$m['comments_count']}</span>" 
        : "";

    // ------------------------------------------------------
    // Watched / unwatched icon logic
    // watched_status comes from DB (0 or 1)
    // ------------------------------------------------------
    $watchedStatus = isset($m['watched_status']) ? (int)$m['watched_status'] : 0;

    // Pick correct icon
    $watchedIcon   = $watchedStatus === 1
        ? "<i class='fa-solid fa-eye'></i>"           // watched
        : "<i class='fa-regular fa-eye-slash'></i>"; // unwatched

    // ------------------------------------------------------
    // Movie card output (HTML)
    // ------------------------------------------------------
    echo "
    <div class='movie-card'>
        <!-- Comments button (opens modal) -->
        <button class='comments-toggle' 
                data-movie-id='{$m['movie_id']}' 
                data-movie-title='" . htmlspecialchars($m['title'], ENT_QUOTES) . "'>
            💬
            {$commentCount}
        </button>

        <!-- Poster -->
        <img src='{$posterPath}' alt='" . htmlspecialchars($m['title']) . " Poster'>

        <!-- Movie title -->
        <h3>" . htmlspecialchars($m['title']) . "</h3>

        <!-- Genre + year -->
        <p>" . htmlspecialchars($m['genre']) . " | " . htmlspecialchars($m['release_year']) . "</p>

        <!-- Action buttons -->
        <div class='movie-actions'>

            <!-- Watch / Unwatch toggle button -->
            <!-- JS uses data-movie-id and data-watched to flip the state -->
            <button 
                type='button'
                class='watch-toggle-btn'
                data-movie-id='{$m['movie_id']}'
                data-watched='{$watchedStatus}'
                title='" . ($watchedStatus ? "Mark as unwatched" : "Mark as watched") . "'>
                {$watchedIcon}
            </button>

            <!-- Edit movie -->
            <a href='edit_movie.php?id=" . $m['movie_id'] . "' class='edit-btn'>Edit</a>

            <!-- Delete movie (opens modal from dashboard) -->
            <a href='#' class='delete-btn' onclick='openDeleteModal(" . $m['movie_id'] . ")'>Delete</a>
        </div>
    </div>";
}
?>
