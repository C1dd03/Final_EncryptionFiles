<?php if (!isset($pageTitle)) {
  $pageTitle = 'Dashboard';
} ?>
<header class="superadmin-header">
  <div class="header-left">
    <button class="sidebar-toggle" type="button" aria-label="Toggle sidebar">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
  </div>

  <div class="header-right">
    <div class="header-profile">
      <div class="profile-avatar">SA</div>
      <div class="profile-info">
        <strong>Super Admin</strong>
        <span>admin@system.com</span>
      </div>
    </div>
    <div class="profile-dropdown">
      <button class="profile-menu-btn" type="button" aria-haspopup="true" aria-expanded="false"><i class="fa-solid fa-chevron-down"></i></button>
      <div class="profile-menu">
        <a href="#">Profile</a>
        <a href="../../php/auth/logout.php">Logout</a>
      </div>
    </div>
  </div>
</header>