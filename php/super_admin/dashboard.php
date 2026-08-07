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
</head>

<body>
  <div class="superadmin-app">
    <?php include 'includes/superadmin_sidebar.php'; ?>
    <div class="superadmin-main">
      <?php include 'includes/superadmin_header.php'; ?>
      <main class="superadmin-content">
        <div class="card">
          <h2>Welcome back, Super Admin</h2>
          <p>Here you can manage admins, users, blocks, and system activity from one place.</p>
        </div>
      </main>
    </div>
  </div>
  <script src="../../js/super_admin/superadmin.js"></script>
</body>

</html>