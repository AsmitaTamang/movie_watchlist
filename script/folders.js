console.log("✅ folders.js connected");

// Open popup
function openCreateFolder() {
  const popup = document.getElementById("createFolderPopup");
  if (!popup) {
    console.error("❌ Popup element not found!");
    return;
  }
  popup.style.display = "flex";
  console.log("🟢 Popup opened!");
}

// Close popup
function closeCreateFolder() {
  const popup = document.getElementById("createFolderPopup");
  if (popup) popup.style.display = "none";
  console.log("🔴 Popup closed!");
}

// Optional: wire up button automatically
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("openCreateFolder");
  if (btn) btn.addEventListener("click", openCreateFolder);
});
