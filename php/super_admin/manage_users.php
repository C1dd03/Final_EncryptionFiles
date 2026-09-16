<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';
if (strtolower($_SESSION['role'] ?? '') !== 'superadmin') {
  header('Location: ../auth/index.php?action=login');
  exit();
}
$pageTitle = 'Account Management';
$activePage = 'account_management';
$managementScope = 'superadmin';
$canCreateAccounts = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Account Management</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/account_management.css?v=20260908b" />
  <link rel="stylesheet" href="../../css/action_dropdown.css?v=20260906" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
</head>
<body>
  <div class="superadmin-app">
    <?php include __DIR__ . '/includes/superadmin_sidebar.php'; ?>
    <div class="superadmin-main">
      <?php include __DIR__ . '/includes/superadmin_header.php'; ?>
      <main class="superadmin-content">
        <?php include __DIR__ . '/../shared/account_management_content.php'; ?>
      </main>
    </div>
  </div>
  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/shared_validator.js?v=20260908i"></script>
  <script src="../../js/action_dropdown.js?v=20260906"></script>
  <script src="../../js/account_management.js?v=20260915-invitations"></script>
</body>
</html>
