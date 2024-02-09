// SIDEBAR TOGGLE

let sidebarOpen = false;
const sidebar = document.getElementById('sidebar');

function openSidebar() {
  if (!sidebarOpen) {
    sidebar.classList.add('sidebar-responsive');
    sidebarOpen = true;
  }
}

function closeSidebar() {
  if (sidebarOpen) {
    sidebar.classList.remove('sidebar-responsive');
    sidebarOpen = false;
  }
}
document.getElementById('toggle-products').addEventListener('click', function(event) {
    event.preventDefault(); // Empêche le comportement par défaut du lien
    var submenu = this.nextElementSibling; // Cible le sous-menu suivant ce lien
    if (submenu.style.display === 'block') {
      submenu.style.display = 'none';
    } else {
      submenu.style.display = 'block';
    }
  });
  
// ---------- CHARTS ----------

