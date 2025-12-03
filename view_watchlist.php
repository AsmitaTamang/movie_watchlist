<?php
require_once 'includes/auth.php';  // Load authentication system
$user_id= requireAuth();           // Make sure user is logged in
require_once 'dbconnect.php';

// Checking if user is logged in (redundant but safe)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Making sure a watchlist ID exists in the URL
if (!isset($_GET['id'])) {
    die("Watchlist not found.");
}

$watchlist_id = intval($_GET['id']);

// Fetch watchlist only if it belongs to the current logged-in user
$wl_stmt = $pdo->prepare("SELECT * FROM watchlists WHERE watchlist_id = ? AND user_id = ?");
$wl_stmt->execute([$watchlist_id, $user_id]);
$watchlist = $wl_stmt->fetch(PDO::FETCH_ASSOC);

// If someone tries to access another user's watchlist → block it
if (!$watchlist) {
    die("Watchlist not found or unauthorized.");
}

// Fetch all movies added to this watchlist
$movie_stmt = $pdo->prepare("
    SELECT m.movie_id, m.title, m.genre, m.release_year, m.poster
    FROM watchlist_item wi
    JOIN movies m ON wi.movie_id = m.movie_id
    WHERE wi.watchlist_id = ?
    ORDER BY wi.added_at DESC   -- Show newest added movies first
");
$movie_stmt->execute([$watchlist_id]);
$movies = $movie_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Watchlist name is used as page title -->
    <title><?php echo htmlspecialchars($watchlist['name']); ?></title>

    <link rel="stylesheet" href="style1.css">

    <style>
        /* Header showing watchlist title */
        .watchlist-header {
            text-align: center;
            margin-top: 40px;
            color: white;
        }

        /* Add movie button styling */
        .btn-add-movie {
            background: #ff4b4b;
            padding: 12px 24px;
            border-radius: 30px;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        /* Movie list container */
        .movie-list {
            width: 60%;
            margin: 40px auto;
            color: white;
        }

        /* Each movie row */
        .movie-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background: #112233;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        /* Three-dot menu button */
        .menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
        }

        /* Hidden options menu */
        .menu-box {
            display: none;
            position: absolute;
            background: #1c1c1c;
            padding: 10px;
            border-radius: 8px;
        }

        /* Styling for "Remove" link */
        .menu-box a {
            display: block;
            padding: 6px 10px;
            color: white;
            text-decoration: none;
        }

        .menu-box a:hover {
            background: #333;
        }
    </style>
</head>
<body>

<!-- Top navigation bar -->
<nav class="navbar">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-link">Home</a>
        <a href="your_categories.php" class="nav-link">Your Folders</a>
        <a href="watchlists.php" class="nav-link active">Watchlists</a>
    </div>
</nav>

<!-- Watchlist title and planned date -->
<div class="watchlist-header">
    <h1><?php echo htmlspecialchars($watchlist['name']); ?></h1>

    <!-- Planned viewing date -->
    <p>Planned Date: <?php echo htmlspecialchars($watchlist['planned_date']); ?></p>

    <!-- Add movie button (opens selection page) -->
    <button class="btn-add-movie"
        onclick="openAddMoviePopup(<?php echo $watchlist_id; ?>)">
        + Add Movie to Watchlist
    </button>
</div>

<!-- List of movies inside this watchlist -->
<div class="movie-list">

    <?php if (empty($movies)): ?>
        <!-- If user has not added any movie yet -->
        <p style="text-align:center; margin-top:20px; font-size:20px;">
            No movies added yet.
        </p>
    <?php else: ?>

        <?php foreach ($movies as $m): ?>
            <div class="movie-item">

                <!-- Movie title, genre and year -->
                <div>
                    <strong><?php echo htmlspecialchars($m['title']); ?></strong><br>
                    <small><?php echo $m['genre'] . " | " . $m['release_year']; ?></small>
                </div>

                <!-- Three-dot menu (remove option) -->
                <div style="position:relative;">

                    <!-- This button toggles the menu -->
                    <button class="menu-btn" onclick="toggleMenu(this)">⋮</button>

                    <!-- Menu showing "Remove" link -->
                    <div class="menu-box">
                        <a href="remove_from_watchlist.php?watchlist_id=<?php echo $watchlist_id; ?>&movie_id=<?php echo $m['movie_id']; ?>">
                            Remove
                        </a>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
    // Show / hide the remove menu for each movie
    function toggleMenu(btn) {
        const menu = btn.nextElementSibling;
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }

    // Redirect to page for selecting a movie to add
    function openAddMoviePopup(id) {
        window.location.href = "add_movie_to_watchlist.php?id=" + id;
    }
</script>

</body>
</html>
