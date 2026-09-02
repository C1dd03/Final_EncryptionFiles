<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';

$currentUsername = $_SESSION['username'] ?? 'Admin';
$currentIdNumber = $_SESSION['user_id'] ?? '';

$userModel = new User();
$canViewUsers = $userModel->hasAdminPrivilege($currentIdNumber, 'view_users');
$canApproveRegistrations = $userModel->hasAdminPrivilege($currentIdNumber, 'approve_registrations');
$stats = $userModel->getAdminDashboardStats();

// Without the View Users privilege, statistics are not exposed (N/A)
$totalDisplay   = $canViewUsers ? number_format($stats['total_users']) : 'N/A';
$activeDisplay  = $canViewUsers ? number_format($stats['active_users']) : 'N/A';
$blockedDisplay = $canViewUsers ? number_format($stats['blocked_users']) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../../css/admin/admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
</head>

<body>
  <div class="admin-app">
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
      <?php include 'includes/admin_header.php'; ?>
      <main class="admin-content">

        <section class="welcome-section">
          <h2>Admin Dashboard</h2>
          <p>Welcome back, <strong><?= htmlspecialchars($currentUsername) ?></strong>.</p>
          <div class="live-indicator">
            <span class="live-dot"></span>
            <span>Live - Updates automatically every 5 seconds</span>
          </div>
        </section>

        <div class="cards-grid">
          <div class="stat-card total" id="card-total">
            <div class="stat-icon">
              <i class="fa-solid fa-users"></i>
            </div>
            <div>
              <h3>Total Users</h3>
              <div class="stat-value" id="stat-total"><?= $totalDisplay ?></div>
              <p>Registered Users</p>
            </div>
          </div>

          <div class="stat-card active" id="card-active">
            <div class="stat-icon">
              <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
              <h3>Active Users</h3>
              <div class="stat-value" id="stat-active"><?= $activeDisplay ?></div>
              <p>Currently active</p>
            </div>
          </div>

          <div class="stat-card blocked" id="card-blocked">
            <div class="stat-icon">
              <i class="fa-solid fa-user-lock"></i>
            </div>
            <div>
              <h3>Blocked Users</h3>
              <div class="stat-value" id="stat-blocked"><?= $blockedDisplay ?></div>
              <p>Restricted access</p>
            </div>
          </div>

          <a href="approvals.php" class="stat-card stat-card-approval" id="adminApprovalCard">
            <div class="stat-icon">
              <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
              <h3>Pending Approvals</h3>
              <div class="stat-value" id="adminPendingCount"><?= $canApproveRegistrations ? '0' : 'N/A' ?></div>
              <p>User registrations</p>
            </div>
          </a>
        </div>

      </main>
    </div>
  </div>

  <script src="../../js/admin/admin.js"></script>
  <script src="../../js/admin/dashboard.js"></script>
  <?php if ($canApproveRegistrations): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      fetch('../../php/auth/index.php?action=getDashboardCounts')
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            const countEl = document.getElementById('adminPendingCount');
            if (countEl) {
              countEl.textContent = data.pending_approvals || 0;
            }
          }
        })
        .catch((err) => console.error('Failed to load dashboard counts:', err));
    });
  </script>
  <?php endif; ?>
</body>

</html>
