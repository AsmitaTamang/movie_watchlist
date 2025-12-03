<?php
session_start();
require_once 'dbconnect.php';

/*
|--------------------------------------------------------------------------
|  VALIDATION + INSERT WATCHLIST
|--------------------------------------------------------------------------
|  This script:
|   - Blocks duplicate watchlist names (case-insensitive)
|   - Blocks empty fields
|   - Blocks invalid dates (optional improvement)
|   - Redirects with error or success
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* --------------------------------------------
   READ + CLEAN INPUT
-------------------------------------------- */
$name = trim($_POST['watchlist_name'] ?? "");
$date = trim($_POST['planned_date'] ?? "");

/* --------------------------------------------
   VALIDATION: Required fields
-------------------------------------------- */
if ($name === "" || $date === "") {
    header("Location: watchlists.php?error=" . urlencode("All fields are required."));
    exit;
}

/* --------------------------------------------
   VALIDATION: Date cannot be in the past
-------------------------------------------- */
$currentDate = date("Y-m-d");

if ($date < $currentDate) {
    header("Location: watchlists.php?error=" . urlencode("Planned date cannot be in the past."));
    exit;
}

/* --------------------------------------------
   VALIDATION: Duplicate name check
   Ensures a user cannot create:
   - Two watchlists named 'Holiday Movies'
   - Case-insensitive check
-------------------------------------------- */
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM watchlists
        WHERE user_id = ? 
        AND LOWER(TRIM(name)) = LOWER(TRIM(?))
    ");
    $stmt->execute([$user_id, $name]);

    if ($stmt->fetchColumn() > 0) {
        header("Location: watchlists.php?error=" . urlencode("A watchlist with this name already exists."));
        exit;
    }
} catch (PDOException $e) {
    header("Location: watchlists.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}

/* --------------------------------------------
   INSERT NEW WATCHLIST
-------------------------------------------- */
try {

    $sql = "INSERT INTO watchlists (user_id, name, planned_date)
            VALUES (?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $name, $date]);

    header("Location: watchlists.php?created=1");
    exit;

} catch (PDOException $e) {
    header("Location: watchlists.php?error=" . urlencode("Error creating watchlist."));
    exit;
}
?>
