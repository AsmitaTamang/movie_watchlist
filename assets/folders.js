document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ folders.js connected");

  const createModal = document.getElementById("createModal");
  const renameModal = document.getElementById("renameModal");
  const openCreate = document.getElementById("openCreate");
  const closeCreate = document.getElementById("closeCreate");
  const closeRename = document.getElementById("closeRename");
  const renameId = document.getElementById("renameId");
  const renameInput = document.getElementById("renameInput");

  if (!openCreate) {
    console.error("❌ openCreate button not found!");
    return;
  }

  // Create folder modal
  openCreate.addEventListener("click", () => {
    console.log("🟢 Create Folder button clicked");
    createModal.style.display = "flex";
  });

  closeCreate?.addEventListener("click", () => {
    createModal.style.display = "none";
  });

  // Rename modal
  document.querySelectorAll(".rename-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      renameId.value = btn.dataset.id;
      renameInput.value = btn.dataset.name;
      renameModal.style.display = "flex";
    });
  });

  closeRename?.addEventListener("click", () => {
    renameModal.style.display = "none";
  });

  // Close menus on click outside
  document.addEventListener("click", e => {
    if (!e.target.closest(".kebab") && !e.target.closest(".kebab-menu")) {
      document.querySelectorAll(".kebab-menu").forEach(m => m.style.display = "none");
    }
  });

  // Escape key closes modals
  document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
      createModal.style.display = "none";
      renameModal.style.display = "none";
    }
    
  });
  
});
