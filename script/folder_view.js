// ✅ Folder view popup and movie search logic

document.addEventListener("DOMContentLoaded", () => {
  const popup = document.getElementById("addMoviePopup");
  const searchInput = document.getElementById("searchMovie");
  const movieSelect = document.getElementById("movieSelect");

  // ✅ Open popup
  window.openAddMovie = function () {
    if (popup) popup.style.display = "flex";
  };

  // ✅ Close popup
  window.closeAddMovie = function () {
    if (popup) popup.style.display = "none";
  };

  // ✅ Search filter
  if (searchInput && movieSelect) {
    const allOptions = Array.from(movieSelect.options).filter(opt => opt.value !== "");

    searchInput.addEventListener("input", () => {
      const term = searchInput.value.toLowerCase().trim();
      movieSelect.innerHTML = '<option value="">Select Movie</option>';

      allOptions.forEach(opt => {
        const text = opt.text.toLowerCase();
        const genre = opt.dataset.genre?.toLowerCase() || "";
        const year = opt.dataset.year?.toString() || "";

        if (text.includes(term) || genre.includes(term) || year.includes(term)) {
          movieSelect.appendChild(opt);
        }
      });
    });
  }

  // ✅ Close popup on background click
  if (popup) {
    popup.addEventListener("click", (e) => {
      if (e.target === popup) {
        popup.style.display = "none";
      }
    });
  }
});
