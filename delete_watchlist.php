<?php
// Require authentication and ensure the user is logged in
require_once 'includes/auth.php';
$user_id = requireAuth();

// Start session (needed to check logged-in user)
session_start();

// Connect to database
require_once "dbconnect.php";

// --------------------------------------------------------
// SECURITY CHECK: Make sure a user is logged in
// --------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

// Store logged-in user's ID locally
$user_id = $_SESSION['user_id'];

// Get the watchlist ID from the URL (?id=...)
$watchlist_id = $_GET['id'];

// --------------------------------------------------------
// 1. DELETE ALL MOVIES INSIDE THIS WATCHLIST
// This prevents orphaned items when the watchlist is removed.
// --------------------------------------------------------
$pdo->prepare("DELETE FROM watchlist_item WHERE watchlist_id = ?")
    ->execute([$watchlist_id]);

// --------------------------------------------------------
// 2. DELETE THE WATCHLIST ITSELF
// The extra AND user_id = ? ensures users can delete only their own watchlists.
// --------------------------------------------------------
$pdo->prepare("DELETE FROM watchlists WHERE watchlist_id = ? AND user_id = ?")
    ->execute([$watchlist_id, $user_id]);

// Redirect back with a success message
header("Location: watchlists.php?deleted=1");
exit;
