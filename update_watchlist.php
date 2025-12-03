<?php
require_once 'includes/auth.php';
$user_id= requireAuth();   // Ensure user is logged in and get their user_id

require_once 'dbconnect.php';  // Database connection


// ---------------------------------------------------------
// Read submitted form values from POST request
// ---------------------------------------------------------
$watchlist_id = $_POST['watchlist_id'];     // ID of the watchlist being edited
$name         = trim($_POST['name']);       // New watchlist name (trim removes extra spaces)
$date         = trim($_POST['planned_date']); // New planned date


// ---------------------------------------------------------
// Update watchlist in the database
// Ensures: only the owner (user_id) can update their watchlist
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    UPDATE watchlists SET name = ?, planned_date = ? 
    WHERE watchlist_id = ? AND user_id = ?
");
$stmt->execute([$name, $date, $watchlist_id, $user_id]);


// ---------------------------------------------------------
// Redirect back with a success flag
// watchlists.php?updated=1 will show a success message
// ---------------------------------------------------------
header("Location: watchlists.php?updated=1");
exit;
?>
