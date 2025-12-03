<?php
require_once 'includes/auth.php';
$user_id= requireAuth();  // Ensure user is logged in

require_once "dbconnect.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];

// Read watchlist ID
$watchlist_id = $_GET['id'] ?? 0;

// Fetch watchlist belonging to this user
$stmt = $pdo->prepare("SELECT * FROM watchlists WHERE watchlist_id = ? AND user_id = ?");
$stmt->execute([$watchlist_id, $user_id]);
$watchlist = $stmt->fetch(PDO::FETCH_ASSOC);

// Stop if not found
if (!$watchlist) {
    die("Watchlist not found or unauthorized.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style1.css">
    <title>Edit Watchlist</title>
</head>
<body>

<section class="dashboard-header">
    <h1>Edit Watchlist</h1>
</section>

<form action="update_watchlist.php" method="POST" class="popup-content" style="max-width:400px; margin:auto;">

    <!-- Hidden: ID of watchlist being edited -->
    <input type="hidden" name="watchlist_id" value="<?php echo $watchlist_id; ?>">

    <label>Name:</label>
    <!-- FIXED: correct variable name `$watchlist` -->
    <input type="text" name="name" value="<?php echo htmlspecialchars($watchlist['name']); ?>" required>

    <label>Planned Date:</label>
    <!-- FIXED: correct variable name `$watchlist` -->
    <input type="date" name="planned_date" value="<?php echo $watchlist['planned_date']; ?>" required>

    <button type="submit" class="btn-red">Save</button>
</form>

</body>
</html>
