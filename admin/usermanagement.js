// User Management JavaScript
// This file handles client-side functionality for user management pages

// Wait for DOM to be fully loaded before executing scripts
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter forms on change (optional feature)
    const filterSelects = document.querySelectorAll('.filter-select, .sort-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit(); // Submit form when selection changes
        });
    });

    // Real-time search with debounce to prevent excessive requests
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let timeout = null; // Timer reference for debouncing
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout); // Clear previous timer
            timeout = setTimeout(() => {
                // Auto-search after 500ms of inactivity
                // Only search if query is 2+ characters or empty (to show all)
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit(); // Submit search form
                }
            }, 500); // 500ms delay
        });
    }

    // Export functionality (bonus feature)
    const exportBtn = document.getElementById('exportUsers');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Export current filtered results by passing URL parameters
            const searchParams = new URLSearchParams(window.location.search);
            window.open('export_users.php?' + searchParams.toString(), '_blank');
        });
    }

    // Bulk actions for multiple user operations
    const bulkAction = document.getElementById('bulkAction');
    const selectedUsers = document.querySelectorAll('.user-checkbox');
    
    if (bulkAction && selectedUsers.length > 0) {
        bulkAction.addEventListener('change', function() {
            if (this.value) {
                // Confirm action before proceeding
                if (confirm(`Are you sure you want to ${this.value} selected users?`)) {
                    // Implement bulk action
                    performBulkAction(this.value);
                }
            }
        });
    }
});

/**
 * Performs bulk actions on selected users
 * @param {string} action The action to perform (delete, activate, deactivate, etc.)
 */
function performBulkAction(action) {
    // Collect IDs of selected users
    const selectedIds = [];
    document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });

    // Validate that at least one user is selected
    if (selectedIds.length === 0) {
        alert('Please select at least one user.');
        return;
    }

    // Prepare form data for AJAX request
    const formData = new FormData();
    formData.append('action', action);
    formData.append('user_ids', JSON.stringify(selectedIds));

    // Send bulk action request to server
    fetch('bulk_user_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Refresh page to show changes
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while performing bulk action.');
    });
}