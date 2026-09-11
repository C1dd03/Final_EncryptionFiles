<?php if (!isset($activePage)) {
  $activePage = 'dashboard';
} ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" />

</head>

<body>

  <div class="sidebar-overlay"></div>
  <aside class="superadmin-sidebar" id="superadminSidebar">
    <div class="sidebar-brand">
      <div class="brand-mark">SA</div>
      <div>
        <h3>Super Admin</h3>
        <p>Control Center</p>
      </div>
    </div>

    <nav class="sidebar-nav" aria-label="Super admin navigation">
      <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">
          <i class="fa-regular fa-house"></i>
        </span>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="manage_users.php" class="nav-link <?= $activePage === 'account_management' ? 'active' : '' ?>">
        <span class="nav-icon">
          <i class="fa-solid fa-users"></i>
        </span>
        <span class="nav-label">Account Management</span>
      </a>

      <!-- <a href="personal_details.php" class="nav-link <?= $activePage === 'personal_details' ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-id-card"></i></span>
        <span class="nav-label">Personal Details</span>
      </a> -->

      <a href="approvals.php" class="nav-link <?= $activePage === 'approvals' ? 'active' : '' ?>">
        <span class="nav-icon">
          <i class="fa-solid fa-user-check"></i>
        </span>
        <span class="nav-label">Approvals</span>
      </a>

      <a href="delete_requests.php" class="nav-link <?= $activePage === 'delete_requests' ? 'active' : '' ?>">
        <span class="nav-icon">
          <i class="fa-solid fa-trash-arrow-up"></i>
        </span>
        <span class="nav-label">Delete Requests</span>
      </a>

      <a href="block_list.php" class="nav-link <?= $activePage === 'block_list' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 3H6a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm-1.5 8.5-5.7 5.7a1 1 0 0 1-1.4 0L7.5 14.4a1 1 0 0 1 0-1.4l1.4-1.4a1 1 0 0 1 1.4 0l1.3 1.3 4.3-4.3a1 1 0 0 1 1.4 0l1.4 1.4a1 1 0 0 1 0 1.4Z"></path>
          </svg>
        </span>
        <span class="nav-label">Block List</span>
      </a>

      <a href="audit_logs.php" class="nav-link <?= $activePage === 'audit_logs' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3h10a2 2 0 0 1 2 2v14l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 0 1 2-2Zm2 4v2h6V7Zm0 4v2h6v-2Z"></path>
          </svg>
        </span>
        <span class="nav-label">Audit Logs</span>
      </a>
    </nav>

    <a href="../../php/auth/logout.php" class="sidebar-logout">
      <span class="nav-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10 17v-2H5V9h5V7l4 5-4 5Zm8-11h-2v12h2V6Z"></path>
        </svg>
      </span>
      <span class="nav-label">Logout</span>
    </a>
  </aside>
</body>

</html>