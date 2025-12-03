<?php

// Require auth helper and make sure user is logged in.
// requireAuth() will redirect to login if not authenticated.
require_once 'includes/auth.php';
$user_id = requireAuth(); 

// Database connection
require_once 'dbconnect.php';

// ------------------------------------------------------------------
// Check if user is logged in using session (extra safety / fallback)
// ------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    // Temporary fallback for testing if session is missing
    // (In production you usually don't want to force user_id = 1)
    $_SESSION['user_id'] = 1; // Temporary fallback for testing
}

// Overwrite $user_id from session to keep it consistent with session data
$user_id = $_SESSION['user_id'];

// Variables to hold error/success messages for the UI
$error = '';
$success = '';

// ------------------------------------------------------------------
// Get folder ID from URL (?id=...)
// If missing, redirect back to folder list with an error
// ------------------------------------------------------------------
$folder_id = $_GET['id'] ?? null;
if (!$folder_id) {
    header("Location: your_categories.php?error=invalid_id");
    exit;
}

// ------------------------------------------------------------------
// Fetch folder details for this user and this folder ID
// If folder not found or doesn't belong to this user -> redirect
// ------------------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE FolderID = ? AND user_id = ?");
    $stmt->execute([$folder_id, $user_id]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$folder) {
        // Folder does not exist or user doesn't own it
        header("Location: your_categories.php?error=folder_not_found");
        exit;
    }
} catch (PDOException $e) {
    // Hard fail if there is a DB error while fetching folder
    die("Database error: " . $e->getMessage());
}

// ------------------------------------------------------------------
// Handle folder UPDATE form submission
// Triggered when POST and "update_folder" button is pressed
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_folder'])) {
    // Read and trim input values
    $folder_name = trim($_POST['folder_name']);
    $description = trim($_POST['description'] ?? '');
    
    // -------------------------------
    // Validate folder name
    // -------------------------------
    if (empty($folder_name)) {
        $error = "Folder name is required.";
    } elseif (strlen($folder_name) > 100) {
        $error = "Folder name must be less than 100 characters.";
    } else {
        try {
            // Check if another folder with the same name already exists for this user,
            // excluding the current folder (FolderID != ?)
            $check_stmt = $pdo->prepare("SELECT FolderID FROM folders WHERE FolderName = ? AND user_id = ? AND FolderID != ?");
            $check_stmt->execute([$folder_name, $user_id, $folder_id]);
            
            if ($check_stmt->fetch()) {
                // Another folder with same name exists
                $error = "A folder with this name already exists.";
            } else {
                // -------------------------------------
                // Update folder name and description
                // -------------------------------------
                $update_stmt = $pdo->prepare("UPDATE folders SET FolderName = ?, Description = ? WHERE FolderID = ? AND user_id = ?");
                $update_stmt->execute([$folder_name, $description, $folder_id, $user_id]);
                
                if ($update_stmt->rowCount() > 0) {
                    // Update succeeded
                    $success = "Folder updated successfully!";
                    
                    // Refresh folder data from database to reflect changes
                    $stmt = $pdo->prepare("SELECT * FROM folders WHERE FolderID = ? AND user_id = ?");
                    $stmt->execute([$folder_id, $user_id]);
                    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    // No rows affected (maybe user submitted same data)
                    $error = "No changes were made.";
                }
            }
        } catch (PDOException $e) {
            // Database error during update
            $error = "Error updating folder: " . $e->getMessage();
        }
    }
}

// ------------------------------------------------------------------
// Handle DELETE folder form submission
// Triggered when POST and "delete_folder" button is pressed
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_folder'])) {
    try {
        // Start a database transaction so both operations succeed or fail together
        $pdo->beginTransaction();
        
        // First, remove links from folder_movies so no orphan records remain
        $remove_stmt = $pdo->prepare("DELETE FROM folder_movies WHERE folder_id = ?");
        $remove_stmt->execute([$folder_id]);
        
        // Then delete the folder itself (only if it belongs to this user)
        $delete_stmt = $pdo->prepare("DELETE FROM folders WHERE FolderID = ? AND user_id = ?");
        $delete_stmt->execute([$folder_id, $user_id]);
        
        // Commit transaction if both deletes succeeded
        $pdo->commit();
        
        if ($delete_stmt->rowCount() > 0) {
            // Store success message in session and redirect back to folder list
            $_SESSION['success'] = "Folder deleted successfully!";
            header("Location: your_categories.php");
            exit();
        } else {
            // Nothing deleted (folder not found or didn't belong to user)
            $error = "Failed to delete folder.";
        }
    } catch (PDOException $e) {
        // Roll back transaction if something went wrong
        $pdo->rollBack();
        $error = "Error deleting folder: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Folder - Movie Watchlist</title>
    <!-- Link to folder.css with cache-busting version parameter -->
    <link rel="stylesheet" href="folder.css?v=<?php echo time(); ?>">
    <style>
        /* Page container for the edit view */
        .edit-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
        }
        /* Card styling for the edit form */
        .edit-card {
            background: #0f1626;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }
        .edit-title {
            color: #ff1a1a;
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #1b2735;
            color: #fff;
            font-size: 15px;
        }
        .form-control:focus {
            outline: 2px solid #e50914;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #e50914;
            color: white;
        }
        .btn-primary:hover {
            background: #b0070f;
            transform: scale(1.02);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: scale(1.02);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.02);
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        /* Modal styles for delete confirmation popup */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #0f1626;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
        }
        .modal h3 {
            color: #ff1a1a;
            margin-bottom: 15px;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav>
        <!-- Navigation links for back and home -->
        <a href="your_categories.php">← Back to Folders</a>
        <a href="dashboard.php">Home</a>
    </nav>

    <div class="edit-container">
        <h1 class="edit-title">Edit Folder</h1>

        <!-- Error message display -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Success message display -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="edit-card">
            <!-- Folder edit form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="folder_name" class="form-label">Folder Name *</label>
                    <input type="text" class="form-control" id="folder_name" name="folder_name" 
                           value="<?php echo htmlspecialchars($folder['FolderName']); ?>" 
                           required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="3" maxlength="255"><?php echo htmlspecialchars($folder['Description'] ?? ''); ?></textarea>
                    <small style="color: #ccc;">Optional description for your folder.</small>
                </div>

                <div class="btn-group">
                    <!-- Submit button to update folder details -->
                    <button type="submit" name="update_folder" class="btn btn-primary">Update Folder</button>
                    <!-- Button to open delete confirmation modal -->
                    <button type="button" class="btn btn-danger" onclick="openDeleteModal()">Delete Folder</button>
                    <!-- Cancel button to go back to folders page -->
                    <a href="your_categories.php" class="btn btn-secondary" style="text-decoration: none; text-align: center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete the folder "<strong><?php echo htmlspecialchars($folder['FolderName']); ?></strong>"?</p>
            <p style="color: #ff6b6b; font-size: 14px; margin-top: 10px;">
                <strong>Warning:</strong> This action cannot be undone. All movies will be removed from this folder.
            </p>
            <div class="modal-buttons">
                <!-- Form that submits delete_folder action -->
                <form method="POST" style="display: inline;">
                    <button type="submit" name="delete_folder" class="btn btn-danger">Delete</button>
                </form>
                <!-- Button to close modal without deleting -->
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Open the delete confirmation modal
        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Close the delete confirmation modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside of modal content
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        // Auto-hide success message after 3 seconds (if it exists)
        const successMsg = document.querySelector('.alert-success');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
