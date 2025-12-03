<?php
require_once 'dbconnect.php';
require_once 'includes/auth.php';

// Read the folder_id from the URL (GET request)
// If missing → show error and stop
$folder_id = $_GET['id'] ?? null;
if (!$folder_id) {
    echo "Invalid folder.";
    exit;
}

try {
    // ------------------------------------------------------
    // Fetch folder details from the database
    // FIXED: correctly using $folder_id instead of $FolderID
    // ------------------------------------------------------
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE FolderID = ?");
    $stmt->execute([$folder_id]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no folder found → stop the page
    if (!$folder) {
        echo "Folder not found.";
        exit;
    }

    // ------------------------------------------------------
    // Fetch movies linked to this folder using folder_movies
    // INNER JOIN ensures only movies inside this folder appear
    // ------------------------------------------------------
    $moviesStmt = $pdo->prepare("
        SELECT m.*
        FROM movies m
        INNER JOIN folder_movies fm ON m.movie_id = fm.movie_id
        WHERE fm.FolderID = ?
    ");
    $moviesStmt->execute([$folder_id]);
    $movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------
    // Fetch all movies in the database for the dropdown list
    // (Used when adding a movie to this folder)
    // ------------------------------------------------------
    $allMovies = $pdo->query("SELECT * FROM movies ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If any database error occurs, show message and stop
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($folder['FolderName']); ?> - Folder</title>

    <!-- Folder page stylesheet -->
    <link rel="stylesheet" href="folder.css?v=100">

    <style>
        /* Page styling */
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            font-family: 'Poppins', sans-serif;
            color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        /* Top navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(10, 20, 40, 0.8);
            padding: 12px 30px;
        }

        nav a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        /* Folder title */
        h2 {
            color: #ff1a1a;
            text-align: center;
            font-size: 36px;
            margin: 25px 0;
        }

        /* Movie layout grid */
        .folder-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
            margin: 40px auto 0 auto;
            max-width: 1200px;
        }

        /* Movie card box */
        .folder-card {
            background: #0f1626;
            border-radius: 18px;
            width: 240px;
            padding: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
            text-align: center;
            transition: all 0.3s ease;
        }

        /* Hover enlarges card */
        .folder-card:hover {
            transform: scale(1.03);
            box-shadow: 0 0 25px rgba(229,9,20,0.4);
        }

        /* Poster image style */
        .folder-card img {
            width: 100%;
            height: 330px;
            object-fit: cover;
            border-radius: 10px;
        }

        /* Movie title */
        .folder-card h3 {
            margin-top: 10px;
            color: #fff;
            font-weight: 600;
        }

        /* Genre + year */
        .folder-card p {
            color: #ccc;
            font-size: 14px;
        }

        /* Add Movie button */
        .add-movie-btn {
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 22px;
            font-weight: bold;
            cursor: pointer;
            margin: 35px auto 80px auto;
            display: block;
            transition: all 0.3s ease;
        }

        .add-movie-btn:hover {
            background: #b0070f;
            transform: scale(1.05);
        }

        /* Popup background overlay */
        .popup {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        /* Popup box */
        .popup-content {
            background: #0f1626;
            color: #fff;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            text-align: center;
        }

        /* Form elements inside popup */
        .popup-content input,
        .popup-content select {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: none;
            background: #1e2b37;
            color: #fff;
        }

        .popup-content button {
            background: #e50914;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }

        .popup-content button:hover {
            background: #b0070f;
        }
    </style>
</head>
<body>

    <!-- Navigation (Back + Home) -->
    <nav>
        <a href="your_categories.php">← Back</a>
        <a href="dashboard.php">Home</a>
    </nav>
    
    <!-- Button to open the movie select popup -->
    <button class="add-movie-btn" onclick="openAddMovie()">+ Add Existing Movie</button>

    <!-- Folder name heading -->
    <h2><?= htmlspecialchars($folder['FolderName']); ?></h2>

    <!-- Movies inside this folder -->
    <section class="folder-container">
        <?php if (empty($movies)): ?>
            <p>No movies in this folder yet.</p>
        <?php else: ?>
            <?php foreach ($movies as $movie): ?>
                <div class="folder-card">
                    <?php
                    // Build poster file path safely
                    $posterFile = htmlspecialchars($movie['poster']);
                    if (!str_starts_with($posterFile, 'uploads/')) {
                        $posterFile = 'uploads/' . $posterFile;
                    }
                    echo "<img src='{$posterFile}' alt='Poster'>";
                    ?>
                    <h3><?= htmlspecialchars($movie['title']); ?></h3>
                    <p><?= htmlspecialchars($movie['genre']); ?> | <?= htmlspecialchars($movie['release_year']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Popup: Add existing movie -->
    <div id="addMoviePopup" class="popup">
        <div class="popup-content">
            <h2 style="color:#ff1a1a;">Add Existing Movie</h2>

            <!-- Search filter inside popup -->
            <input type="text" id="searchMovie" placeholder="Search by title, genre, or year...">

            <!-- Form to submit movie choice -->
            <form id="addMovieForm" method="POST" action="add_to_folder.php">

                <!-- Hidden folder ID -->
                <input type="hidden" name="folder_id" value="<?= (int)$folder_id; ?>">

                <!-- Dropdown of all movies -->
                <select name="movie_id" id="movieSelect" required>
                    <option value="">Select Movie</option>

                    <?php foreach ($allMovies as $mv): ?>
                        <option value="<?= $mv['movie_id']; ?>" 
                            data-genre="<?= htmlspecialchars($mv['genre']); ?>" 
                            data-year="<?= htmlspecialchars($mv['release_year']); ?>">
                            <?= htmlspecialchars($mv['title']); ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <br><br>

                <button type="submit">Add Movie</button>
                <button type="button" onclick="closeAddMovie()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- External JS for folder page -->
    <script src="script/folder_view.js?v=2"></script>
</body>
</html>
