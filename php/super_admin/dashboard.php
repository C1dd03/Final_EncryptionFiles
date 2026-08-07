<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';

// Check role authorization
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin' && $role !== 'admin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$userModel = new User();
$stats = $userModel->getDashboardStats();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Dashboard</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" />
</head>

<body>
  <div class="superadmin-app">
    <?php include 'includes/superadmin_sidebar.php'; ?>
    <div class="superadmin-main">
      <?php include 'includes/superadmin_header.php'; ?>
      <main class="superadmin-content">
        <div class="cards-grid">
          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
              <h3>Total Accounts</h3>
              <div class="stat-value"><?= number_format($stats['total_accounts']) ?></div>
              <p>Registered users & admins</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div>
              <h3>Active Admins</h3>
              <div class="stat-value"><?= number_format($stats['active_admins']) ?></div>
              <p>Managing the system</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
            <div>
              <h3>Active Users</h3>
              <div class="stat-value"><?= number_format($stats['active_users']) ?></div>
              <p>Standard users</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
            <div>
              <h3>Blocked Accounts</h3>
              <div class="stat-value"><?= number_format($stats['blocked_accounts']) ?></div>
              <p>Restricted access</p>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
  <script src="../../js/super_admin/superadmin.js"></script>
</body>

</html>