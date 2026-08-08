<?php if (!isset($pageTitle)) {
  $pageTitle = 'Dashboard';
}

$currentUsername = $_SESSION['username'] ?? 'Admin';
$currentEmail = $_SESSION['email'] ?? 'admin@system.com';
$avatarInitials = strtoupper(substr($currentUsername, 0, 2));
?>
<header class="admin-header">
  <div class="header-left">
    <button class="sidebar-toggle" type="button" aria-label="Toggle sidebar">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
  </div>

  <div class="header-right">
    <div class="header-profile">
      <div class="profile-avatar"><?= htmlspecialchars($avatarInitials) ?></div>
      <div class="profile-info">
        <strong><?= htmlspecialchars($currentUsername) ?></strong>
        <span><?= htmlspecialchars($currentEmail) ?></span>
      </div>
    </div>
    <div class="profile-dropdown">
      <button class="profile-menu-btn" type="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="profile-menu">
        <a href="../auth/logout.php">Logout</a>
      </div>
    </div>
  </div>
</header>
