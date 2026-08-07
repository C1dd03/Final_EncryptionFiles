<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
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
              <div class="stat-value">1,248</div>
              <p>Registered users & admins</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div>
              <h3>Active Admins</h3>
              <div class="stat-value">12</div>
              <p>Managing the system</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
            <div>
              <h3>Active Users</h3>
              <div class="stat-value">1,120</div>
              <p>Standard users</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
            <div>
              <h3>Blocked Accounts</h3>
              <div class="stat-value">16</div>
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