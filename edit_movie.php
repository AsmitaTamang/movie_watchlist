<?php

// Include authentication functions and force the user to be logged in
require_once 'includes/auth.php';  // ← LOAD FIRST

// This will redirect to login if not authenticated and return the logged-in user's ID
$user_id = requireAuth();          // ← CALL SECOND  

// Connect to the database
require_once 'dbconnect.php';

// Include validation helpers (clean_input, validate_year, validate_poster, save_poster, set_flash, etc.)
require_once 'validation.php'; 


// 🧩 1. Protect the page (login required)
// Extra safety check: make sure session user_id is set.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Use the user_id from session for all DB queries (consistent identity)
$user_id = $_SESSION['user_id'];

// Get movie ID from query string (?id=...)
$movie_id = $_GET['id'] ?? null;

// If no movie ID is provided in the URL, stop and show an error message
if (!$movie_id) {
    die("<p style='color:red;text-align:center;'>❌ Invalid request — no movie selected.</p>");
}

// 🧩 2. Fetch movie from DB
// Select the movie that matches this movie_id AND belongs to this user
$stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ? AND user_id = ?");
$stmt->execute([$movie_id, $user_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

// If no row is found, the movie either doesn't exist or doesn't belong to this user
if (!$movie) {
    die("<p style='color:red;text-align:center;'>⚠️ Movie not found or you don't have permission to edit it.</p>");
}

// 🧩 3. Handle form submission
// When the form is submitted, it comes as a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize user inputs using helper function clean_input
    $title = clean_input($_POST['title'] ?? '');
    $genre = clean_input($_POST['genre'] ?? '');
    $year = clean_input($_POST['release_year'] ?? '');

    // Default poster path is the existing one from the database
    $posterPath = $movie['poster']; // default = old poster

    // ✅ Validation
    // Simple required fields check
    if (empty($title) || empty($genre) || empty($year)) {
        set_flash("All fields are required.");
    }

    // Validate year using your custom function (e.g., 4 digits)
    if (!validate_year($year)) {
        set_flash(" Invalid year format. Use YYYY.");
    }

    // ✅ Prevent duplicate (same title by same user, excluding this movie)
    // Check if another movie (different movie_id) by the same user has the same title
    $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE LOWER(TRIM(title)) = LOWER(TRIM(?)) AND user_id = ? AND movie_id != ?");
    $dupCheck->execute([$title, $user_id, $movie_id]);
    if ($dupCheck->fetchColumn() > 0) {
        set_flash(" Another movie with this title already exists!");
    }

    // ✅ Handle new poster upload (optional)
    // Only process if the user actually selected a file
    if (!empty($_FILES['poster']['name'])) {
        // First validate file type and size
        if (!validate_poster($_FILES['poster'])) {
            set_flash(" Invalid poster file. Only JPG, PNG, WEBP under 3MB allowed.");
        }

        // If valid, save the new poster file and store its path
        $newPoster = save_poster($_FILES['poster']);
        if ($newPoster) {
            $posterPath = $newPoster;
        }
    }

    // ✅ Update DB record
    // Update movie fields in the database (title, genre, year, poster)
    $update = $pdo->prepare("UPDATE movies SET title = ?, genre = ?, release_year = ?, poster = ? WHERE movie_id = ? AND user_id = ?");
    $update->execute([$title, $genre, $year, $posterPath, $movie_id, $user_id]);

    // Set a success message (likely shown somewhere via set_flash)
    set_flash(" Movie updated successfully!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Page title includes movie title for context -->
  <title>Edit Movie - <?= htmlspecialchars($movie['title']); ?></title>
  <!-- Separate CSS file just for edit_movie styling -->
  <link rel="stylesheet" href="edit_movie.css">
</head>
<body>

<!-- Simple navigation bar -->
<nav class="navbar">
  <div class="nav-left">
    <!-- Back to dashboard button -->
    <a href="dashboard.php" class="nav-link"> Home</a>
    <!-- Link to folder/categories page -->
    <a href="your_categories.php" class="nav-link">📂 Your Folders</a>
  </div>
  <div class="nav-right">
    <!-- Logout link -->
    <a href="logout.php" class="nav-link">Logout</a>
  </div>
</nav>

<section class="edit-section">
  <!-- Heading shows which movie is being edited -->
  <h2>Edit Movie: <?= htmlspecialchars($movie['title']); ?></h2>

  <!-- Form for editing movie details -->
  <!-- Submits back to edit_movie.php with the current movie ID in the query string -->
  <form action="edit_movie.php?id=<?= $movie['movie_id']; ?>" method="POST" enctype="multipart/form-data" class="edit-form">
    <label>Title:</label>
    <!-- Prefill current title -->
    <input type="text" name="title" value="<?= htmlspecialchars($movie['title']); ?>" required>

    <label>Genre:</label>
    <!-- Prefill current genre (can be multiple genres in one string) -->
    <input type="text" name="genre" value="<?= htmlspecialchars($movie['genre']); ?>" required>

    <label>Release Year:</label>
    <!-- Prefill current release year -->
    <input type="number" name="release_year" value="<?= htmlspecialchars($movie['release_year']); ?>" required>

    <label>Current Poster:</label><br>
    <!-- Show current poster image -->
    <img src="<?= htmlspecialchars($movie['poster']); ?>" alt="Poster" style="width:120px;border-radius:8px;margin:10px 0;"><br>

    <label>Upload New Poster (optional):</label>
    <!-- File input for optional new poster image -->
    <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">

    <div class="form-buttons">
      <!-- Submit to save changes -->
      <button type="submit" class="save-btn"> Save Changes</button>
      <!-- Cancel and go back to dashboard without saving -->
      <a href="dashboard.php" class="cancel-btn">❌ Cancel</a>
    </div>
  </form>
</section>

</body>
</html>
