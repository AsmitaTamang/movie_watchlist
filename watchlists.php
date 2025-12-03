<?php
require_once 'includes/auth.php';
$user_id = requireAuth();

require_once 'dbconnect.php';

/*
|--------------------------------------------------------------------------
| Fetch Watchlists for Current User
|--------------------------------------------------------------------------
| We load all watchlists here so both PHP and JS can work with them.
| JS will filter/sort the DOM directly — no need for AJAX.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("SELECT * FROM watchlists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Watchlists</title>
    <link rel="stylesheet" href="style1.css?v=<?php echo time(); ?>">

    <style>
        /* -- PAGE CONTAINER -- */
        .watchlist-page-container {
            width: 90%;
            margin: 0 auto;
            margin-top: 30px;
        }

        /* -- SEARCH + FILTER BAR -- */
        .filter-bar {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px 15px;
            border-radius: 10px;
            border: none;
            outline: none;
            font-size: 16px;
        }

        /* -- WATCHLIST GRID -- */
        .watchlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* -- WATCHLIST CARD -- */
        .watchlist-card-click {
            text-decoration: none;
            color: inherit;
        }

        .watchlist-card {
            background: #0f1c2e;
            padding: 20px;
            border-radius: 16px;
            color: white;
            transition: .3s;
            cursor: pointer;
        }

        .watchlist-card:hover {
            transform: translateY(-4px);
        }

        /* -- BUTTONS (edit/delete) -- */
        .watchlist-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }

        .edit-btn { background: #1a73e8; }
        .delete-btn { background: #e63946; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-link">Home</a>
        <a href="your_categories.php" class="nav-link">Your Folders</a>
        <a href="watchlists.php" class="nav-link active">Watchlists</a>
    </div>
</nav>

<!-- HEADER -->
<section class="dashboard-header">
    <h1>Your Watchlists</h1>
    <p>Search, filter, sort, edit, and manage all your watchlists.</p>

    <!-- Show error only if ?error= exists -->
    <?php if (!empty($_GET['error'])): ?>
        <div class="error-msg" id="errorBox">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>

        <script>
        // Auto-hide after 3 seconds
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
<script>
// Remove ?error= from URL
if (window.history.replaceState) {
    const url = new URL(window.location.href);
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url.pathname);
}
</script>

</section>

<!-- 🔎 SEARCH + FILTER + SORT BAR -->
<div class="filter-bar">
    
    <!-- SEARCH by Name -->
    <input type="text" id="searchWatchlist" placeholder="Search watchlists...">

    <!-- FILTER by Planned Date -->
    <input type="date" id="filterDate">

    <!-- SORT SELECT -->
    <select id="sortWatchlists">
        <option value="">Sort</option>
        <option value="az">Name (A → Z)</option>
        <option value="za">Name (Z → A)</option>
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
    </select>

</div>

<div class="watchlist-page-container">

    <?php if (empty($lists)): ?> <!-- If the user has not created any watchlist -->
        <p style="color:white; text-align:center; margin-top:20px; font-size:18px;">
            No watchlists yet.
        </p>
    <?php else: ?>

        <!-- WATCHLIST GRID -->
        <div class="watchlist-grid" id="watchlistGrid">
<!-- * Loop through all watchlists fetched for the currently authenticated user.
 * Each iteration exposes a single watchlist record in $wl.-->
            <?php foreach ($lists as $wl): ?>
                 <!-- Encode special characters to prevent XSS and convert the name to lowercase.
This value is stored in a data attribute for client-side filtering/search -->
            <div class="watchlist-card watchlist-item" 
            
                 data-name="<?php echo strtolower(htmlspecialchars($wl['name'])); ?>"
                 data-date="<?php echo htmlspecialchars($wl['planned_date']); ?>"
                 data-created="<?php echo htmlspecialchars($wl['created_at']); ?>">

                <!-- Entire card is clickable -->
                <a href="view_watchlist.php?id=<?php echo $wl['watchlist_id']; ?>"
                   class="watchlist-card-click"> <!-- click watchlist card(whole section of particular watchlist) to open respective watchlist -->

                    <h3><?php echo htmlspecialchars($wl['name']); ?></h3> <!-- Put watchlist name -->
                    <!-- planned date to input by user to watch selected movies -->
                    <p>Planned: <?php echo htmlspecialchars($wl['planned_date']); ?></p>

                </a>

                <!-- Action Buttons -->
                <div class="watchlist-actions">
                    <!-- edit watchlist Button -->
                    <a href="edit_watchlist.php?id=<?php echo $wl['watchlist_id']; ?>" class="edit-btn">Edit</a>
                     <!-- delete watchlist Button -->
                    <a href="delete_watchlist.php?id=<?php echo $wl['watchlist_id']; ?>"
                       class="delete-btn"
                       
                       onclick="return confirm('Delete this watchlist?');"> <!-- Confirmation message -->
                        Delete
                    </a>
                </div>

            </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
<!-- Script to opreate search bars in watchlist section -->
<script src="script/watchlist_filters.js"></script>
<!-- this script or js opens modal to crete watchlist -->
<script src="script/popup.js"></script>

</body>
</html>
