<?php
session_start();
require_once 'dbconnect.php';

// -------------------------------------------------------------
// TEMPORARY: Hard-coded user ID for testing movie retrieval.
// In production, this should come from $_SESSION['user_id'].
// -------------------------------------------------------------
$user_id = 1; // Change to your user ID

// -------------------------------------------------------------
// Prepare and execute a query to get all movies owned by the user
// -------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM movies WHERE user_id = ?");
$stmt->execute([$user_id]);

// Fetch all movie rows returned by the query
$movies = $stmt->fetchAll();

// -------------------------------------------------------------
// Display simple output showing the count and title of each movie
// -------------------------------------------------------------
echo "Found " . count($movies) . " movies";

// Loop through each movie and print its title
foreach($movies as $movie) {
    echo "<br> - " . $movie['title'];
}
?>
