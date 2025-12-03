/*
    This file is only for handling popups / modals.
    I moved everything here so dashboard.php stays cleaner.
*/

document.addEventListener('DOMContentLoaded', () => {
    initializePopups();
});

function initializePopups() {
    // I grab all the popup elements I care about
    const addMoviePopup        = document.getElementById('popupAddMovie');
    const createWatchlistPopup = document.getElementById('popupCreateWatchlist');
    const addToWatchlistPopup  = document.getElementById('popupAddToWatchlist');

    // buttons that open the main two popups on the dashboard header
    const btnOpenAddMovie        = document.getElementById('btnOpenAddMovie');
    const btnOpenCreateWatchlist = document.getElementById('btnOpenCreateWatchlist');

    // open "Add Movie" popup
    if (btnOpenAddMovie && addMoviePopup) {
        btnOpenAddMovie.addEventListener('click', () => {
            openPopup(addMoviePopup);
        });
    }

    // open "Create Watchlist" popup
    if (btnOpenCreateWatchlist && createWatchlistPopup) {
        btnOpenCreateWatchlist.addEventListener('click', () => {
            openPopup(createWatchlistPopup);
        });
    }

    /*
        For closing, I don't want to write a separate function for every popup.
        So I just look for any element that has data-close-popup="popupId".
        That way I can reuse the same logic for X button and Cancel button.
    */
    document.querySelectorAll('[data-close-popup]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-close-popup');
            if (!targetId) return;
            const popup = document.getElementById(targetId);
            if (popup) {
                closePopup(popup);
            }
        });
    });

    /*
        If the user clicks on the dark background (overlay), I close that popup too.
        I check e.target === popup so I don't accidentally close it when clicking inside.
    */
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.popup').forEach((popup) => {
            if (e.target === popup) {
                closePopup(popup);
            }
        });
    });
}

/*
    Tiny helper functions so I don't repeat .style.display = ...
*/
function openPopup(popupElement) {
    if (popupElement) {
        popupElement.style.display = 'flex';
    }
}

function closePopup(popupElement) {
    if (popupElement) {
        popupElement.style.display = 'none';
    }
}

/*
    This function is used from outside (for example, from watchlist cards
    where I have something like: onclick="openAddToWatchlist(3)".
    So I attach it to window to keep it global.
*/
window.openAddToWatchlist = function (watchlistId) {
    const popup = document.getElementById('popupAddToWatchlist');
    if (!popup) return;

    const hiddenInput = document.getElementById('watchlistIdInput');
    if (hiddenInput) {
        hiddenInput.value = watchlistId;
    }

    openPopup(popup);
};