<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';
if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: ../auth/index.php?action=login');
  exit();
}
$pageTitle = 'User Management';
$activePage = 'manage_users';
$managementScope = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management</title>
  <link rel="stylesheet" href="../../css/admin/admin.css" />
  <link rel="stylesheet" href="../../css/account_management.css" />
  <link rel="stylesheet" href="../../css/action_dropdown.css?v=20260906" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
</head>
<body>
  <div class="admin-app">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
    <div class="admin-main">
      <?php include __DIR__ . '/includes/admin_header.php'; ?>
      <main class="admin-content">
        <?php include __DIR__ . '/../shared/account_management_content.php'; ?>
      </main>
    </div>
  </div>
  <script src="../../js/admin/admin.js"></script>
  <script src="../../js/shared_validator.js?v=20260904b"></script>
  <script src="../../js/action_dropdown.js?v=20260906"></script>
  <script src="../../js/account_management.js?v=20260907-otp-btns"></script>
</body>
</html>
