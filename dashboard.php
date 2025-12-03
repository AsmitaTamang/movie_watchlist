<?php
// Require authentication helper and ensure user is logged in
require_once 'includes/auth.php';
$user_id = requireAuth(); // returns the current user's ID or redirects if not logged in

// Connect to database
require_once 'dbconnect.php';

// -------------------------------------------------------------
// FETCH DISTINCT GENRES AND YEARS FOR FILTER DROPDOWNS
// -------------------------------------------------------------

// Get all unique genres for this user to populate the "Genre" filter
$genres_stmt = $pdo->prepare("SELECT DISTINCT genre FROM movies WHERE user_id = ? ORDER BY genre");
$genres_stmt->execute([$user_id]);
$genres = $genres_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all unique release years for this user to populate the "Year" filter
$years_stmt = $pdo->prepare("SELECT DISTINCT release_year FROM movies WHERE user_id = ? ORDER BY release_year DESC");
$years_stmt->execute([$user_id]);
$years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

// -------------------------------------------------------------
// AUTO-CATEGORIZE EXISTING MOVIES (runs silently on dashboard load)
// This checks for movies that are not in any folder_movies record.
// For those movies, it reads genre, splits into separate genres,
// creates folders if needed, and links the movie to those folders.
// -------------------------------------------------------------
try {
    // 1) Find all movies of this user that are not in ANY folder yet
    $autoMoviesStmt = $pdo->prepare("
        SELECT m.movie_id, m.genre
        FROM movies m
        WHERE m.user_id = ?
          AND NOT EXISTS (
              SELECT 1 FROM folder_movies fm
              WHERE fm.movie_id = m.movie_id
          )
    ");
    $autoMoviesStmt->execute([$user_id]);
    $moviesToCategorize = $autoMoviesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($moviesToCategorize as $movieRow) {
        $movieId = $movieRow['movie_id'];
        $genreString = $movieRow['genre'];

        // Skip movies that have no genre stored
        if (empty($genreString)) {
            continue;
        }

        // Split genre string into separate genres
        // Supports: "Drama, Adventure", "Drama/Adventure", "Drama|Adventure"
        $genreParts = preg_split('/[,\/|]+/', $genreString);

        // Trim whitespace, drop empty values, remove duplicates
        $genreParts = array_unique(
            array_filter(
                array_map('trim', $genreParts),
                fn($g) => $g !== ''
            )
        );

        foreach ($genreParts as $singleGenre) {
            if ($singleGenre === '') {
                continue;
            }

            // 2) Find existing folder with this name for this user
            $folderStmt = $pdo->prepare("SELECT FolderID FROM folders WHERE user_id = ? AND FolderName = ?");
            $folderStmt->execute([$user_id, $singleGenre]);
            $folderId = $folderStmt->fetchColumn();

            // 3) If the folder doesn't exist, silently create it
            if (!$folderId) {
                $createFolderStmt = $pdo->prepare("INSERT INTO folders (user_id, FolderName) VALUES (?, ?)");
                $createFolderStmt->execute([$user_id, $singleGenre]);
                $folderId = $pdo->lastInsertId();
            }

            // 4) Link movie to folder (if not already linked)
            if ($folderId) {
                $checkLinkStmt = $pdo->prepare("SELECT COUNT(*) FROM folder_movies WHERE FolderID = ? AND movie_id = ?");
                $checkLinkStmt->execute([$folderId, $movieId]);
                $alreadyLinked = $checkLinkStmt->fetchColumn();

                if (!$alreadyLinked) {
                    $linkStmt = $pdo->prepare("INSERT INTO folder_movies (FolderID, movie_id) VALUES (?, ?)");
                    $linkStmt->execute([$folderId, $movieId]);
                }
            }
        }
    }
} catch (PDOException $e) {
    // If something goes wrong, just log it. Don't break the dashboard for the user.
    error_log("Dashboard auto-categorise error: " . $e->getMessage());
}

// -------------------------------------------------------------
// Fetch recent folders for "Your Recent Folders" section
// (so $folders is always defined even if you don't show them)
// -------------------------------------------------------------
try {
    $folders_stmt = $pdo->prepare("
        SELECT FolderID, FolderName 
        FROM folders 
        WHERE user_id = ? 
        ORDER BY CreatedAt DESC 
        LIMIT 6
    ");
    $folders_stmt->execute([$user_id]);
    $folders = $folders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If query fails, just show no quick folders
    error_log("Dashboard folders fetch error: " . $e->getMessage());
    $folders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Movie Watchlist Dashboard</title>
    <!-- Main stylesheet (versioned with time to avoid cache) -->
    <link rel="stylesheet" href="style1.css?v=<?php echo time(); ?>">
    <!-- Font Awesome for icons (eye, x, etc.) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <!-- Current page is Home (dashboard) -->
        <a href="dashboard.php" class="nav-link active">Home</a>
        <!-- Link to genre folders page -->
        <a href="your_categories.php" class="nav-link">Your Folders</a>
        <!-- Link to watchlists page -->
        <a href="watchlists.php" class="nav-link">Watchlists</a>
    </div>

    <div class="nav-right">
        <div class="profile-wrapper">
            <!-- Profile icon (avatar image generated from username) -->
            <div class="profile-icon" onclick="toggleProfileDropdown()">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=6a1bff&color=fff" style="width:48px;height:48px;border-radius:50%;">
            </div>
            <!-- Dropdown with account actions -->
            <div class="profile-dropdown" id="profileDropdown">
                <strong><?php echo $_SESSION['username']; ?></strong>
                <a href="edit_account.php">✏️ Edit Account</a>
                <a href="logout.php" style="color:#e50914;">Logout</a>
            </div>
        </div>
    </div>
    
</nav>

<!-- MAIN DASHBOARD HEADER (greeting + top buttons + alerts) -->
<section class="dashboard-header">
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>Let's build your next movie night 🍿</p>

    <!-- Show error message if ?error= is present in URL -->
    <?php if (isset($_GET['error'])): ?>
    <div class="error-msg" id="errorBox">
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <script>
    // Small fade-out animation for error box after 3 seconds
    setTimeout(() => {
        const box = document.getElementById("errorBox");
        if (box) {
            box.style.opacity = "0";
            box.style.transform = "translateY(-10px)";
            setTimeout(() => box.remove(), 500);
        }
    }, 3000);
    </script>
    <?php endif; ?>

    <div class="header-buttons">
        <!-- Button opens the "Add Movie" popup -->
        <button class="btn-red" id="btnOpenAddMovie">+ Add Movie</button>
        <!-- Button opens the "Create Watchlist" popup -->
        <button class="btn-outline-red" id="btnOpenCreateWatchlist">+ Create Watchlist</button>
    </div>

    <!-- Success messages based on query parameters -->
    <?php if (isset($_GET['success'])): ?>
        <div class="success-msg">Movie added successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="success-msg">Movie updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="success-msg">Movie deleted successfully!</div>
    <?php endif; ?>
</section>

<!-- SEARCH/FILTER SECTION (top filters for the movie list) -->
<div class="search-filter-container">
    <!-- Text search filter for movie title -->
    <input type="text" id="searchInput" placeholder="Search by title...">
    
    <!-- Genre dropdown populated from $genres -->
    <select id="genreFilter">
        <option value="">All Genres</option>
        <?php foreach ($genres as $genre): ?>
            <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Year dropdown (years from current year back to 1990) -->
    <select id="yearFilter">
        <option value="">All Years</option>
        <?php for ($y = date('Y'); $y >= 1990; $y--): ?>
            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
        <?php endfor; ?>
    </select>

    <!-- Sorting dropdown (handled in dashboard_filter.js) -->
    <select id="sortFilter">
        <option value="default">Sort</option>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="az">A → Z</option>
        <option value="za">Z → A</option>
    </select>

    <!-- Reset button clears all filters and reloads -->
    <button id="resetFilters" class="btn-reset">Reset</button>
</div>

<!-- MOVIES GRID (filled dynamically by fetch_movies.php via JS) -->
<div id="movieContainer" class="movie-grid"></div>

<!-- ADD MOVIE POPUP -->
<div class="popup" id="popupAddMovie">
    <div class="popup-content">
        <!-- Close (X) button for popup -->
        <button class="popup-close" type="button" data-close-popup="popupAddMovie">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2>Add Movie</h2>
        <!-- Form posts to store_movie.php, which will insert the movie -->
        <form action="store_movie.php" method="POST" enctype="multipart/form-data" class="popup-form">
            <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Genre:</label>
                <!-- Multiple genres can be typed (comma, slash, etc.) -->
                <input type="text" name="genre" required>
            </div>
            <div class="form-group">
                <label>Release Year:</label>
                <input type="number" name="release_year" required>
            </div>
            <div class="form-group">
                <label>Poster:</label>
                <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
            <div class="form-group full">
                <label>Comment (optional):</label>
                <textarea name="initial_comment" placeholder="Your thoughts about this movie..."></textarea>
            </div>
            <div class="popup-buttons">
                <button type="submit" class="save-btn">Add</button>
                <button type="button" class="cancel-btn" data-close-popup="popupAddMovie">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE WATCHLIST POPUP -->
<div class="popup" id="popupCreateWatchlist">
    <div class="popup-content">
        <button class="popup-close" type="button" data-close-popup="popupCreateWatchlist">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2>Create Watchlist</h2>
        <!-- Form posts to store_watchlist.php to create a new watchlist -->
        <form action="store_watchlist.php" method="POST">
            <div class="form-group full">
                <label>Watchlist Name:</label>
                <input type="text" name="watchlist_name" required>
            </div>
            <div class="form-group full">
                <label>Planned Watch Date:</label>
                <input type="date" name="planned_date" required>
            </div>
            <div class="popup-buttons">
                <button type="submit" class="save-btn">Create</button>
                <button type="button" class="cancel-btn" data-close-popup="popupCreateWatchlist">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- COMMENTS MODAL (for viewing/adding comments on a specific movie) -->
<div class="modal" id="commentsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Comments for <span id="commentsMovieTitle"></span></h3>
            <button class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <!-- ADD COMMENT FORM -->
            <div class="comment-form-section">
                <form class="comment-form" id="addCommentForm">
                    <textarea id="commentText" placeholder="Share your thoughts about this movie..." maxlength="1000" rows="4"></textarea>
                    <div class="comment-actions">
                        <button type="submit" class="btn">Add Comment</button>
                        <span id="charCount">0/1000</span>
                    </div>
                </form>
            </div>
            
            <!-- COMMENT FILTERS (search & sort) -->
            <div class="comment-controls-section">
                <div class="comment-controls">
                    <div class="filter-group">
                        <input type="text" id="commentSearch" class="comment-search" placeholder="Search comments...">
                    </div>
                    <div class="filter-group">
                        <select id="sortFilter" class="filter-select">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="button" class="btn btn-secondary" id="resetCommentFilters">Reset Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- COMMENTS LIST (filled dynamically via AJAX) -->
            <div class="comments-list-container">
                <div class="comments-list" id="commentsList">
                    Loading comments...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Handles opening/closing popups (Add Movie / Create Watchlist) -->
<script src="script/popup.js"></script>
<!-- Handles filtering, search, and loading movies into movieContainer -->
<script src="script/dashboard_filter.js"></script>

<!-- WATCH / UNWATCH TOGGLE (eye button logic) -->
<script>
document.addEventListener("click", async (event) => {
    // Find the closest button with class .watch-toggle-btn
    const btn = event.target.closest(".watch-toggle-btn");
    if (!btn) return; // Click was not on our eye button

    const movieId = btn.dataset.movieId;
    if (!movieId) return;

    try {
        const formData = new FormData();
        formData.append("movie_id", movieId);

        // Call PHP to flip watched_status
        const response = await fetch("toggle_watched.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            console.error(data.message || "Toggle failed");
            return;
        }

        const isWatched = data.watched_status == 1;

        // Update the icon inside the button
        btn.innerHTML = isWatched
            ? '<i class="fa-solid fa-eye"></i>'
            : '<i class="fa-regular fa-eye-slash"></i>';

        // Optional: visually mark the whole card as watched
        const card = btn.closest(".movie-card");
        if (card) {
            if (isWatched) {
                card.classList.add("watched");
            } else {
                card.classList.remove("watched");
            }
        }

        // Reload movie list from server (keeps filters & sort correct)
        if (window.loadMovies) {
            window.loadMovies();
        }

    } catch (error) {
        console.error("Error toggling watched status:", error);
    }
});
</script>

<script>
// Helper: open a folder (if you navigate to a folder view)
function openFolder(folderId) {
    window.location.href = `view_folder.php?id=${folderId}`;
}

// Toggle the profile dropdown visibility
function toggleProfileDropdown() {
    document.getElementById("profileDropdown").classList.toggle("show");
}

// Simple delete handler for movie delete button
function openDeleteModal(movieId) {
    // Ask user to confirm first
    if (!confirm("Are you sure you want to delete this movie?")) {
        return;
    }

    // Redirect to PHP script that will delete the movie
    window.location.href = "delete_movie.php?id=" + encodeURIComponent(movieId);
}

// Close profile dropdown when clicking outside of it
document.addEventListener("click", (e) => {
    const dropdown = document.getElementById("profileDropdown");
    const wrapper = document.querySelector(".profile-wrapper");
    if (!wrapper.contains(e.target)) {
        dropdown.classList.remove("show");
    }
});

// Comment System
// Handles opening the comments modal, loading comments via AJAX,
// adding new comments, deleting comments, and filtering/sorting them.
class CommentSystem {
    constructor() {
        // ID and title of the movie currently being commented on
        this.currentMovieId = null;
        this.currentMovieTitle = null;
        // Filters used in the comment modal (search text + sort order)
        this.currentFilters = { search: '', sort: 'newest' };
        this.init();
    }

    // Initialize event bindings
    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Global click listener: look for buttons with .comments-toggle
        document.addEventListener('click', (e) => {
            const commentButton = e.target.closest('.comments-toggle');
            if (commentButton) {
                this.handleCommentButtonClick(commentButton);
            }
        });

        // Update character count while typing a comment
        const commentText = document.getElementById('commentText');
        if (commentText) {
            commentText.addEventListener('input', () => {
                const charCount = document.getElementById('charCount');
                if (charCount) {
                    charCount.textContent = `${commentText.value.length}/1000`;
                }
            });
        }

        // Handle add comment form submit
        const addCommentForm = document.getElementById('addCommentForm');
        if (addCommentForm) {
            addCommentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addComment();
            });
        }

        // Comment search box, sort dropdown, and reset button
        const commentSearch = document.getElementById('commentSearch');
        const sortFilter = document.getElementById('sortFilter');
        const resetFilters = document.getElementById('resetCommentFilters');

        if (commentSearch) {
            commentSearch.addEventListener('input', (e) => {
                this.currentFilters.search = e.target.value;
                this.debouncedLoadComments();
            });
        }

        if (sortFilter) {
            sortFilter.addEventListener('change', (e) => {
                this.currentFilters.sort = e.target.value;
                this.loadComments();
            });
        }

        if (resetFilters) {
            resetFilters.addEventListener('click', () => {
                this.resetFilters();
            });
        }

        // Close modal when clicking the X button
        const closeBtn = document.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeCommentsModal();
            });
        }
    }

    // Called when a .comments-toggle button is clicked on a movie card
    handleCommentButtonClick(button) {
        const movieId = button.getAttribute('data-movie-id');
        const movieTitle = button.getAttribute('data-movie-title');
        
        if (movieId && movieTitle) {
            this.showComments(parseInt(movieId), movieTitle);
        }
    }

    // Open comments modal for a specific movie
    showComments(movieId, movieTitle) {
        this.currentMovieId = movieId;
        this.currentMovieTitle = movieTitle;
        
        const titleElement = document.getElementById('commentsMovieTitle');
        if (titleElement) {
            titleElement.textContent = movieTitle;
        }
        
        // Reset filters whenever modal is opened
        this.resetFilters();
        
        const modal = document.getElementById('commentsModal');
        if (modal) {
            modal.style.display = 'block';
        }
        
        // Load comments for this movie
        this.loadComments();
    }

    // Close comments modal and reset current movie info
    closeCommentsModal() {
        const modal = document.getElementById('commentsModal');
        if (modal) {
            modal.style.display = 'none';
        }
        this.currentMovieId = null;
        this.currentMovieTitle = null;
    }

    // Fetch comments from comments.php with filters applied
    async loadComments() {
        if (!this.currentMovieId) return;

        try {
            const commentsList = document.getElementById('commentsList');
            if (commentsList) {
                commentsList.innerHTML = '<div style="text-align:center; padding:20px; color:#ccc;">Loading comments...</div>';
            }

            // Build query parameters for AJAX call
            const params = new URLSearchParams({
                action: 'get_comments',
                movie_id: this.currentMovieId,
                search: this.currentFilters.search,
                sort: this.currentFilters.sort
            });

            const response = await fetch(`comments.php?${params}`);
            const data = await response.json();

            if (data.success && data.comments) {
                this.displayComments(data.comments);
            }
        } catch (error) {
            console.error('Error loading comments:', error);
        }
    }

    // Render comments into the comment list area
    displayComments(comments) {
        const commentsList = document.getElementById('commentsList');
        if (!commentsList) return;
        
        if (comments.length === 0) {
            commentsList.innerHTML = '<div style="color:#bbb; text-align:center; padding:30px;">No comments found.</div>';
            return;
        }

        // Build HTML for each comment
        const commentsHtml = comments.map(comment => `
            <div class="comment-item">
                <div class="comment-header">
                    <span class="comment-author">${comment.username}</span>
                    <div class="comment-meta">
                        <span class="comment-date">${new Date(comment.created_at).toLocaleString()}</span>
                    </div>
                </div>
                <div class="comment-text">${this.escapeHtml(comment.comment_text)}</div>
                ${comment.user_id == <?php echo $_SESSION['user_id']; ?> ? `
                    <div class="comment-actions">
                        <button class="comment-btn delete-comment-btn" onclick="commentSystem.deleteComment(${comment.comment_id})">Delete</button>
                    </div>
                ` : ''}
            </div>
        `).join('');

        commentsList.innerHTML = commentsHtml;
    }

    // Send new comment to add_comment.php via POST
    async addComment() {
        if (!this.currentMovieId) return;

        const commentText = document.getElementById('commentText');
        if (!commentText) return;
        
        const text = commentText.value.trim();

        if (!text) {
            alert('Please enter a comment');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('movie_id', this.currentMovieId);
            formData.append('comment_text', text);

            const response = await fetch('add_comment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();

            if (data.success) {
                // Clear textarea and reset char counter
                commentText.value = '';
                const charCount = document.getElementById('charCount');
                if (charCount) charCount.textContent = '0/1000';
                // Reload comments to show the new one
                await this.loadComments();
                // Optional: refresh movie list if it depends on comment count
                if (typeof window.loadMovies === 'function') {
                    window.loadMovies();
                }
            }
        } catch (error) {
            console.error('Error adding comment:', error);
        }
    }

    // Delete a comment by ID via delete_comment.php
    async deleteComment(commentId) {
        if (!confirm('Delete this comment?')) return;

        try {
            const formData = new FormData();
            formData.append('comment_id', commentId);

            const response = await fetch('delete_comment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();

            if (data.success) {
                // Reload comments to remove deleted one from view
                await this.loadComments();
                // Optional: refresh movie list if it depends on comments
                if (typeof loadMovies === 'function') {
                    loadMovies();
                }
            }
        } catch (error) {
            alert('Error deleting comment.');
        }
    }

    // Reset comment filters to default and reload comments
    resetFilters() {
        this.currentFilters = { search: '', sort: 'newest' };
        const commentSearch = document.getElementById('commentSearch');
        const sortFilter = document.getElementById('sortFilter');
        if (commentSearch) commentSearch.value = '';
        if (sortFilter) sortFilter.value = 'newest';
        this.loadComments();
    }

    // Debounce helper for loading comments while typing in search box
    debouncedLoadComments() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.loadComments();
        }, 300);
    }

    // Escape HTML in comment text to avoid injection
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize comment system when DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    window.commentSystem = new CommentSystem();
});
</script>
</body>
</html>
