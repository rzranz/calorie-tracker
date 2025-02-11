function toggleSidebar() {
  var sidebar = document.getElementById("mySidebar");
  var overlay = document.getElementById("myOverlay");
  var contentWrapper = document.getElementById("contentWrapper");

  // Toggle class open
  sidebar.classList.toggle("open");
  overlay.classList.toggle("open");
  contentWrapper.classList.toggle("sidebar-open"); // Konten bergeser saat sidebar terbuka
}
