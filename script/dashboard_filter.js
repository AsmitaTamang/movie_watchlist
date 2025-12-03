document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const genreFilter = document.getElementById("genreFilter");
  const yearFilter = document.getElementById("yearFilter");
  const resetButton = document.getElementById("resetFilters");
  const movieContainer = document.getElementById("movieContainer");

  let timeout = null;

  // Make loadMovies function globally accessible
  window.loadMovies = async function() {
    const search = searchInput.value.trim();
    const genre = genreFilter.value;
    const year = yearFilter.value;

    // Small fade-out before loading
    movieContainer.style.opacity = "0.4";

    const response = await fetch(
      `fetch_movies.php?search=${encodeURIComponent(search)}&genre=${encodeURIComponent(
        genre
      )}&year=${encodeURIComponent(year)}`
    );

    const html = await response.text();
    movieContainer.innerHTML = html;

    // Fade-in animation
    setTimeout(() => {
      movieContainer.style.opacity = "1";
      movieContainer.style.transition = "opacity 0.3s ease-in-out";
    }, 100);
  }

  // ⏱ Debounce search (wait for typing to pause)
  searchInput.addEventListener("input", () => {
    clearTimeout(timeout);
    timeout = setTimeout(window.loadMovies, 300);
  });

  genreFilter.addEventListener("change", window.loadMovies);
  yearFilter.addEventListener("change", window.loadMovies);

  resetButton.addEventListener("click", () => {
    searchInput.value = "";
    genreFilter.value = "";
    yearFilter.value = "";
    window.loadMovies();
  });

  // Initial load
  window.loadMovies();
});