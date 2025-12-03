<?php
require_once 'includes/auth.php';
$user_id= requireAuth();

require_once 'dbconnect.php';

// checking if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// reading values from URL (GET)
$watchlist_id = isset($_GET['watchlist_id']) ? intval($_GET['watchlist_id']) : 0;
$movie_id     = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// checking if values are valid
if ($watchlist_id <= 0 || $movie_id <= 0) {
    // simple error if user tries to access file directly
    die("Missing watchlist or movie.");
}

try {

    // first checking if movie already exists in the watchlist
    $check = $pdo->prepare("
        SELECT id FROM watchlist_item 
        WHERE watchlist_id = ? AND movie_id = ?
    ");
    $check->execute([$watchlist_id, $movie_id]);

    if ($check->fetch()) {
        // movie already added
        header("Location: view_watchlist.php?id=$watchlist_id&exists=1");
        exit;
    }

    // inserting movie into this watchlist
    $insert = $pdo->prepare("
        INSERT INTO watchlist_item (watchlist_id, movie_id)
        VALUES (?, ?)
    ");
    $insert->execute([$watchlist_id, $movie_id]);

    // redirecting back with success message
    header("Location: view_watchlist.php?id=$watchlist_id&added=1");
    exit;

} catch (PDOException $e) {
    echo "Error adding movie: " . $e->getMessage();
    exit;
}
