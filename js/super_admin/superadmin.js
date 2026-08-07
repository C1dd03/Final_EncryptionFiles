document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('.superadmin-sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  const toggle = document.querySelector('.sidebar-toggle');
  const main = document.querySelector('.superadmin-main');
  const profileButton = document.querySelector('.profile-menu-btn');
  const profileMenu = document.querySelector('.profile-menu');

  if (!sidebar) return;

  const closeSidebar = () => {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
    if (main) main.classList.remove('shifted');
  };

  const openSidebar = () => {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('show');
    if (main) main.classList.remove('shifted');
  };

  if (toggle) {
    toggle.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
      } else {
        sidebar.classList.toggle('collapsed');
        main?.classList.toggle('shifted');
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  if (profileButton && profileMenu) {
    profileButton.addEventListener('click', (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle('show');
    });
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.profile-dropdown')) {
      profileMenu?.classList.remove('show');
    }

    if (window.innerWidth <= 1024 && !e.target.closest('.superadmin-sidebar') && !e.target.closest('.sidebar-toggle')) {
      closeSidebar();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('show');
    }
  });
});
