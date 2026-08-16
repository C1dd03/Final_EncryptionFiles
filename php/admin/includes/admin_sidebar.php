<?php if (!isset($activePage)) {
  $activePage = 'dashboard';
}
if (!isset($pageTitle)) {
  $pageTitle = 'Admin Dashboard';
}

$currentUsername = $_SESSION['username'] ?? 'Admin';
$currentEmail = $_SESSION['email'] ?? 'admin@system.com';
$currentIdNumber = $_SESSION['user_id'] ?? '';
$avatarInitials = strtoupper(substr($currentUsername, 0, 2));

// Privilege-aware navigation: only show pages this admin can actually use.
// (User model is already loaded by session_protect.php on every admin page.)
$sidebarUserModel = new User();
$sidebarGranted = $sidebarUserModel->getAdminPrivileges($currentIdNumber);
$canNavViewUsers = in_array('view_users', $sidebarGranted, true);
$canNavViewLogs  = in_array('view_user_logs', $sidebarGranted, true)
    || in_array('view_admin_logs', $sidebarGranted, true);
?>
<div class="sidebar-overlay"></div>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">A</div>
    <div>
      <h3>Admin Panel</h3>
      <p>Management Console</p>
    </div>
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">
        <i class="fa-solid fa-house"></i>
      </span>
      <span class="nav-label">Dashboard</span>
    </a>

    <a href="manage_users.php" class="nav-link <?= $activePage === 'manage_users' ? 'active' : '' ?>">
      <span class="nav-icon">
        <i class="fa-solid fa-users"></i>
      </span>
      <span class="nav-label">Manage Users</span>
    </a>

    <?php if ($canNavViewLogs): ?>
    <a href="audit_logs.php" class="nav-link <?= $activePage === 'audit_logs' ? 'active' : '' ?>">
      <span class="nav-icon">
        <i class="fa-solid fa-file-lines"></i>
      </span>
      <span class="nav-label">Audit Logs</span>
    </a>
    <?php endif; ?>
  </nav>

  <a href="../auth/logout.php" class="sidebar-logout">
    <span class="nav-icon">
      <i class="fa-solid fa-right-from-bracket"></i>
    </span>
    <span class="nav-label">Logout</span>
  </a>
</aside>
