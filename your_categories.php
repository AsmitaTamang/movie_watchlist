<?php
require_once 'includes/auth.php';   // Load authentication helper
$user_id = requireAuth();           // Ensure only logged-in users access this page
require_once 'dbconnect.php';       // Load database connection

// Add cache-control headers to force fresh loading instead of cached pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Show success message if set
if (isset($_SESSION['success'])) {
    echo '<div class="success-msg">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);  // Clear message after display
}

// Show folder creation/edit errors
if (isset($_SESSION['folder_errors'])) {
    foreach ($_SESSION['folder_errors'] as $error) {
        echo '<div class="error-msg">' . htmlspecialchars($error) . '</div>';
    }
    unset($_SESSION['folder_errors']);
}

// Show general error messages
if (isset($_SESSION['error'])) {
    echo '<div class="error-msg">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Keep previously entered form data to avoid retyping after error
$folderFormData = $_SESSION['folder_form_data'] ?? [];
unset($_SESSION['folder_form_data']);

// Read search and sorting parameters (GET)
$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';

// Base SQL query: fetch all folders for logged-in user
$sql = "SELECT * FROM folders WHERE user_id = ?";
$params = [$user_id];

// If searching by folder name or description
if (!empty($search)) {
    $sql .= " AND (FolderName LIKE ? OR Description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Sorting logic based on selected value
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY CreatedAt ASC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY FolderName ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY FolderName DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY CreatedAt DESC";
        break;
}

try {
    // Execute folder query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);  // List of folders
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Helper function to remove a filter individually
function removeFilter($param) {
    $params = $_GET;
    unset($params[$param]);
    return 'your_categories.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Your Folders - Movie Watchlist</title>

  <!-- Force fresh loading of CSS (cache busting with timestamp) -->
  <link rel="stylesheet" href="folder.css?v=<?= time() ?>" />

  <!-- Additional cache prevention -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
</head>
<body>

  <!-- Navigation bar -->
  <nav class="navbar">
    <div class="nav-left">
      <a href="dashboard.php" class="nav-link">Home</a>
      <a href="your_categories.php" class="nav-link active">Your Folders</a>
    </div>
    <div class="nav-right">
      <a href="logout.php" class="nav-link">Logout</a>
    </div>
  </nav>

  <!-- Page header -->
  <section class="dashboard-header">
    <h1>Your Folders</h1>
    <p>Organize your movie collections</p>

    <!-- Button to open Create Folder popup -->
    <button id="openCreateFolder" class="add-btn">+ Create Folder</button>
  </section>

  <!-- Search and filter form -->
  <div class="filter-section">
    <!-- Search by name/description -->
    <div class="filter-group">
      <label>Search Folders</label>
      <input type="text" id="searchInput"
             placeholder="Search by name or description..."
             value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <!-- Sorting dropdown -->
    <div class="filter-group">
      <label>Sort By</label>
      <select id="sortFilter">
        <option value="newest"   <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
        <option value="oldest"   <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
        <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name A-Z</option>
        <option value="name_desc"<?= $sort==='name_desc'?'selected':'' ?>>Name Z-A</option>
      </select>
    </div>

    <!-- Apply filters -->
    <button id="applyFilters" class="save-btn">Apply</button>

    <!-- Reset button (only shows if filters active) -->
    <?php if (!empty($search) || $sort !== 'newest'): ?>
      <button id="resetFilters" class="reset-btn">Reset All</button>
    <?php endif; ?>
  </div>

  <!-- Display active filters as chips -->
  <?php if (!empty($search) || $sort !== 'newest'): ?>
    <div class="filter-section" style="margin-top: -10px;">
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">

        <!-- Search filter chip -->
        <?php if (!empty($search)): ?>
          <div class="chip">
            Search: "<?= htmlspecialchars($search) ?>"
            <a href="<?= removeFilter('search') ?>" class="chip-close">×</a>
          </div>
        <?php endif; ?>

        <!-- Sort filter chip -->
        <?php if ($sort !== 'newest'): ?>
          <div class="chip">
            Sort:
            <?= ['oldest'=>'Oldest','name_asc'=>'Name A-Z','name_desc'=>'Name Z-A'][$sort] ?>
            <a href="<?= removeFilter('sort') ?>" class="chip-close">×</a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  <?php endif; ?>

  <!-- Folder grid display -->
  <section class="movie-grid" id="folderContainer">

    <!-- If user has zero folders -->
    <?php if (empty($folders)): ?>
      <div class="no-folders-message">
        <p>
          <?php if (!empty($search)): ?>
            No folders found matching "<?= htmlspecialchars($search) ?>"
          <?php else: ?>
            You have no folders yet. Create your first folder above!
          <?php endif; ?>
        </p>
      </div>

    <?php else: ?>
      <!-- Loop through user's folders -->
      <?php foreach ($folders as $folder): ?>
        <div class="movie-card" onclick="openFolder(<?= $folder['FolderID'] ?>)">

          <!-- Folder icon -->
          <div class="folder-icon-container">
            <img src="assets/movies_folder.png" class="folder-icon">
          </div>

          <!-- Folder name -->
          <h3><?= htmlspecialchars($folder['FolderName']) ?></h3>

          <!-- Optional description -->
          <?php if (!empty($folder['Description'])): ?>
            <p><?= htmlspecialchars($folder['Description']) ?></p>
          <?php endif; ?>

          <!-- Options menu (edit/delete) -->
          <div class="folder-menu" onclick="event.stopPropagation()">
            <button class="menu-btn">⋮</button>

            <div class="menu-dropdown">
              <a href="edit_folder.php?id=<?= $folder['FolderID'] ?>" class="menu-item">Edit</a>

              <!-- Delete requires confirmation modal -->
              <a href="#" class="menu-item delete-item"
                 onclick="event.preventDefault(); 
                 deleteFolder(<?= $folder['FolderID'] ?>, '<?= htmlspecialchars($folder['FolderName']) ?>')">
                Delete
              </a>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Create folder popup -->
  <div id="createFolderPopup" class="popup">
    <div class="popup-content">
      <h2>Create New Folder</h2>

      <!-- Folder creation form -->
      <form action="store_folder.php" method="POST">

        <label for="folderName">Folder Name</label>
        <input type="text" id="folderName" name="folder_name"
               required
               value="<?= htmlspecialchars($folderFormData['folder_name'] ?? '') ?>">

        <label for="folderDescription">Description (Optional)</label>
        <textarea id="folderDescription" name="description"><?= htmlspecialchars($folderFormData['description'] ?? '') ?></textarea>

        <div class="popup-buttons">
          <button type="button" class="cancel-btn" onclick="closeCreateFolder()">Cancel</button>
          <button type="submit" class="save-btn">Create</button>
        </div>

      </form>
    </div>
  </div>

  <!-- Delete confirmation modal -->
  <div id="deleteFolderModal" class="delete-modal">
    <div class="delete-modal-content">
      <h3>Delete Folder</h3>

      <!-- Dynamic name inserted by JS -->
      <p id="deleteFolderText">Are you sure you want to delete this folder?</p>

      <div class="delete-buttons">
        <button type="button" class="cancel-btn" onclick="closeDeleteFolderModal()">Cancel</button>

        <!-- Confirm deletion -->
        <a id="confirmDeleteFolder" class="delete-confirm" href="#">Delete</a>
      </div>
    </div>
  </div>

  <script>
    // Open a specific folder page
    function openFolder(folderId) {
      window.location.href = `view_folder.php?id=${folderId}`;
    }

    // Show delete confirmation modal
    function deleteFolder(folderId, folderName) {
      document.getElementById('deleteFolderText').textContent =
        `Are you sure you want to delete "${folderName}"? This action cannot be undone.`;

      document.getElementById('confirmDeleteFolder').href =
        `delete_folder.php?id=${folderId}`;

      document.getElementById('deleteFolderModal').classList.add('show');
    }

    // Close delete modal
    function closeDeleteFolderModal() {
      document.getElementById('deleteFolderModal').classList.remove('show');
    }

    // Close create folder popup
    function closeCreateFolder() {
      document.getElementById('createFolderPopup').classList.remove('show');
    }

    // Apply search/sort filters
    function applyFilters() {
      const search = document.getElementById('searchInput').value;
      const sort   = document.getElementById('sortFilter').value;

      const params = new URLSearchParams();
      if (search) params.append('search', search);
      if (sort !== 'newest') params.append('sort', sort);

      window.location.href = 'your_categories.php?' + params.toString();
    }

    // Reset all filters
    function resetFilters() {
      window.location.href = 'your_categories.php';
    }

    // Page initialization
    document.addEventListener("DOMContentLoaded", () => {

      // Open create folder popup
      const openBtn = document.getElementById("openCreateFolder");
      if (openBtn) openBtn.addEventListener("click", () => {
        document.getElementById("createFolderPopup").classList.add('show');
      });

      // Apply filter button
      const applyBtn = document.getElementById("applyFilters");
      if (applyBtn) applyBtn.addEventListener("click", applyFilters);

      // Reset filters button
      const resetBtn = document.getElementById("resetFilters");
      if (resetBtn) resetBtn.addEventListener("click", resetFilters);

      // Press Enter to apply search
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
          if (e.key === 'Enter') applyFilters();
        });
      }

      // Clicking outside popups closes them
      window.addEventListener("click", (e) => {
        const createPopup = document.getElementById("createFolderPopup");
        const deleteModal = document.getElementById("deleteFolderModal");

        if (e.target === createPopup) createPopup.classList.remove('show');
        if (e.target === deleteModal) deleteModal.classList.remove('show');
      });

      // Dropdown menu for each folder ("⋮")
      document.querySelectorAll('.menu-btn').forEach(button => {
        button.addEventListener('click', function(e) {
          e.stopPropagation();
          
          const dropdown = this.nextElementSibling;

          // Close all other dropdowns first
          document.querySelectorAll('.menu-dropdown')
            .forEach(d => { if (d !== dropdown) d.classList.remove('show'); });

          // Toggle this one
          dropdown.classList.toggle('show');
        });
      });

      // Close dropdowns when clicking anywhere else
      document.addEventListener('click', function() {
        document.querySelectorAll('.menu-dropdown')
          .forEach(dropdown => dropdown.classList.remove('show'));
      });
    });
  </script>
</body>
</html>
