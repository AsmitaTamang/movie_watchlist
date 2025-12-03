<?php
require_once 'includes/auth.php';
$user_id = requireAuth();
require_once 'dbconnect.php';
require_once 'validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // -----------------------------
    // 1. Get and clean form inputs
    // -----------------------------
    $title        = clean_input($_POST['title'] ?? '');
    $genre        = clean_input($_POST['genre'] ?? '');
    $release_year = clean_input($_POST['release_year'] ?? '');

    $errors = [];

    // -----------------------------
    // 2. Basic validation
    // -----------------------------
    if (empty($title)) {
        $errors[] = "Title is required";
    }

    if (empty($genre)) {
        $errors[] = "Genre is required";
    }

    if (!validate_year($release_year)) {
        $errors[] = "Invalid release year";
    }

    // -----------------------------
    // 3. Poster upload (optional)
    // -----------------------------
    $posterPath = null;

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        if (validate_poster($_FILES['poster'])) {
            $posterPath = save_poster($_FILES['poster']);
            if (!$posterPath) {
                $errors[] = "Failed to upload poster";
            }
        } else {
            $errors[] = "Invalid poster file (max 3MB, JPG/PNG/WEBP only)";
        }
    }

    // -----------------------------
    // 4. If errors → send back
    // -----------------------------
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: dashboard.php");
        exit;
    }

    // -----------------------------
    // 5. Insert movie in database
    // -----------------------------
    try {
        $stmt = $pdo->prepare("
            INSERT INTO movies (user_id, title, genre, release_year, poster)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title, $genre, $release_year, $posterPath]);

        // Get the ID of the new movie we just inserted
        $movieId = $pdo->lastInsertId();

        // ------------------------------------------------------
        // 6. AUTO-CATEGORIZE THIS NEW MOVIE INTO GENRE FOLDERS
        //    - Supports multiple genres
        //    - Example: "Drama, Adventure" → Drama folder + Adventure folder
        //    - No combined "Drama/Adventure" folder is ever created
        // ------------------------------------------------------
        if ($movieId && !empty($genre)) {

            // Split the genre string into individual genres.
            // Accepts: commas, slashes, or | as separators.
            $genreParts = preg_split('/[,\/|]+/', $genre);

            // Trim spaces and remove any empty values and duplicates
            $cleanGenres = [];
            foreach ($genreParts as $g) {
                $trimmed = trim($g);
                if ($trimmed !== '' && !in_array($trimmed, $cleanGenres, true)) {
                    $cleanGenres[] = $trimmed;
                }
            }

            foreach ($cleanGenres as $singleGenre) {
                // 6a. Find folder with this genre name for this user
                $folderStmt = $pdo->prepare("
                    SELECT FolderID
                    FROM folders
                    WHERE user_id = ? AND FolderName = ?
                    LIMIT 1
                ");
                $folderStmt->execute([$user_id, $singleGenre]);
                $folderId = $folderStmt->fetchColumn();

                // 6b. If no folder yet, silently create one
                if (!$folderId) {
                    $createFolderStmt = $pdo->prepare("
                        INSERT INTO folders (user_id, FolderName)
                        VALUES (?, ?)
                    ");
                    $createFolderStmt->execute([$user_id, $singleGenre]);
                    $folderId = $pdo->lastInsertId();
                }

                // 6c. Link movie to folder (avoid duplicates)
                if ($folderId) {
                    $checkLinkStmt = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM folder_movies
                        WHERE FolderID = ? AND movie_id = ?
                    ");
                    $checkLinkStmt->execute([$folderId, $movieId]);
                    $exists = $checkLinkStmt->fetchColumn();

                    if (!$exists) {
                        $linkStmt = $pdo->prepare("
                            INSERT INTO folder_movies (FolderID, movie_id)
                            VALUES (?, ?)
                        ");
                        $linkStmt->execute([$folderId, $movieId]);
                    }
                }
            }
        }

        // -----------------------------
        // 7. Go back to dashboard
        // -----------------------------
        header("Location: dashboard.php?success=1");
        exit;

    } catch (PDOException $e) {
        // Log error but don't show details to user
        error_log("Database error in store_movie.php: " . $e->getMessage());
        header("Location: dashboard.php?error=database");
        exit;
    }
}
?>
