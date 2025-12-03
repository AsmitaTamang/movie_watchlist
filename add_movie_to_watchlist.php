<?php
// Load authentication helper and require logged-in user.
// If not logged in, requireAuth() redirects to login page.
require_once 'includes/auth.php';
$user_id= requireAuth();

// Connect to database
require_once 'dbconnect.php';

// -----------------------------------------------------------
// CHECK IF USER IS LOGGED IN (double protection)
// -----------------------------------------------------------
// Even though requireAuth() already checks,
// this extra check prevents accidental access if session breaks.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Store logged-in user ID
$user_id = $_SESSION['user_id'];

// -----------------------------------------------------------
// CHECK IF WATCHLIST ID IS PROVIDED IN URL
// -----------------------------------------------------------
if (!isset($_GET['id'])) {
    die("No watchlist selected."); // stops page if no watchlist selected
}

// Convert the watchlist ID to integer for safety
$watchlist_id = intval($_GET['id']);

// -----------------------------------------------------------
// FETCH ALL MOVIES BELONGING TO THIS USER
// -----------------------------------------------------------
// All movies: title, genre, release year — shown in selectable list
$movie_stmt = $pdo->prepare("SELECT movie_id, title, genre, release_year FROM movies WHERE user_id = ?");
$movie_stmt->execute([$user_id]);
$movies = $movie_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Movie to Watchlist</title>

    <!-- Main site stylesheet -->
    <link rel="stylesheet" href="style1.css">

    <style>
        /* Page text color */
        body {
            color: white;
        }

        /* Container holding the movie list */
        .movie-container {
            width: 55%;
            margin: 40px auto;
            background: #0f1c2e;
            padding: 25px;
            border-radius: 15px;
        }

        /* Search bar styling */
        .search-bar {
            margin-bottom: 15px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            outline: none;
            margin-bottom: 15px;
        }

        /* Genre filter dropdown */
        .filter-bar select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            outline: none;
            margin-bottom: 15px;
        }

        /* Individual movie row */
        .movie-item {
            background: #112233;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
        }

        /* Hover effect */
        .movie-item:hover {
            background: #1a2f4d;
        }
    </style>
</head>
<body>

<!-- Top navigation bar -->
<nav class="navbar">
    <div class="nav-left">
        <!-- Link back to dashboard -->
        <a href="dashboard.php" class="nav-link">Home</a>
        <!-- Link back to watchlists overview -->
        <a href="watchlists.php" class="nav-link">Watchlists</a>
    </div>
</nav>

<!-- Header section for page title -->
<section class="dashboard-header">
    <h1>Select a Movie</h1>
    <p>Search and filter to add a movie to your watchlist.</p>
</section>

<!-- Main container for movie list -->
<div class="movie-container">

    <!-- SEARCH FIELD: filters by movie title -->
    <div class="search-bar">
        <input type="text" id="searchMovie" placeholder="Search by name...">
    </div>

    <!-- GENRE FILTER DROPDOWN -->
    <div class="filter-bar">
        <select id="genreFilter">
            <option value="">All Genres</option>
            <!-- Hard-coded genre list -->
            <option value="Action">Action</option>
            <option value="Drama">Drama</option>
            <option value="Comedy">Comedy</option>
            <option value="Romance">Romance</option>
            <option value="Horror">Horror</option>
            <option value="Adventure">Adventure</option>
        </select>
    </div>

    <!-- MOVIE LIST DISPLAYED TO USER -->
    <div id="movieList">

        <?php foreach ($movies as $m): ?>
            <div class="movie-item"
                data-title="<?php echo strtolower($m['title']); ?>"   <!-- used for search -->
                data-genre="<?php echo strtolower($m['genre']); ?>"   <!-- used for genre filter -->
                onclick="addMovie(<?php echo $watchlist_id; ?>, <?php echo $m['movie_id']; ?>)"> <!-- triggers addition -->

                <!-- Movie title -->
                <strong><?php echo htmlspecialchars($m['title']); ?></strong>
                <br>

                <!-- Movie genre + year -->
                <small>
                    <?php echo htmlspecialchars($m['genre']); ?> |
                    <?php echo $m['release_year']; ?>
                </small>

            </div>
        <?php endforeach; ?>

    </div>

</div>

<script>
    // -------------------------------------------------------
    // SEARCH MOVIE BY TITLE (real-time filtering)
    // -------------------------------------------------------
    document.getElementById('searchMovie').addEventListener('keyup', function() {
        let keyword = this.value.toLowerCase();
        let movies = document.querySelectorAll('.movie-item');

        movies.forEach(movie => {
            let title = movie.dataset.title;
            // Show only items containing the typed keyword
            movie.style.display = title.includes(keyword) ? 'block' : 'none';
        });
    });

    // -------------------------------------------------------
    // FILTER MOVIES BY GENRE
    // -------------------------------------------------------
    document.getElementById('genreFilter').addEventListener('change', function() {
        let genre = this.value.toLowerCase();
        let movies = document.querySelectorAll('.movie-item');

        movies.forEach(movie => {
            let movieGenre = movie.dataset.genre;
            // Show all if no genre selected, otherwise match genre
            movie.style.display = (genre === "" || genre === movieGenre)
                ? 'block'
                : 'none';
        });
    });

    // -------------------------------------------------------
    // ADD MOVIE TO WATCHLIST (redirects to backend PHP)
    // -------------------------------------------------------
    function addMovie(watchlistId, movieId) {
        // Redirect to PHP script that stores movie → watchlist
        window.location.href =
            "store_watchlist_item.php?watchlist_id=" + watchlistId +
            "&movie_id=" + movieId;
    }
</script>

</body>
</html>
