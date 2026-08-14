<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$pageTitle = 'Audit Logs';
$activePage = 'audit_logs';

$currentUsername = $_SESSION['username'] ?? 'Admin';
$currentIdNumber = $_SESSION['user_id'] ?? '';

// Audit log access depends on privileges: View User Activity Logs / View Admin Activity Logs
$userModel = new User();
$granted = $userModel->getAdminPrivileges($currentIdNumber);
$canViewUserLogs  = in_array('view_user_logs', $granted, true);
$canViewAdminLogs = in_array('view_admin_logs', $granted, true);
$canViewAnyLogs   = $canViewUserLogs || $canViewAdminLogs;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Audit Logs - Admin</title>
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

        <?php if ($canViewAnyLogs): ?>
        <div class="privileges-banner">
          <i class="fa-solid fa-shield-halved"></i>
          <div>
            <h4>Your Admin Privileges:</h4>
            <ul class="privilege-list">
              <?php if ($canViewUserLogs): ?><li><i class="fa-solid fa-check"></i> View User Activity Logs</li><?php endif; ?>
              <?php if ($canViewAdminLogs): ?><li><i class="fa-solid fa-check"></i> View Admin Activity Logs</li><?php endif; ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($canViewAnyLogs): ?>
        <div class="audit-header">
          <div>
            <h2>Audit Logs</h2>
            <p>Review user management actions, login history, account events, and security audit trails.</p>
          </div>
        </div>

        <div class="control-card">
          <div class="control-row">
            <div class="control-left">
              <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search User ID, Username, Action, Details..." />
              </div>

              <select id="actionFilter" class="filter-select" aria-label="Action Filter">
                <option value="all" selected>All Actions</option>
                <option value="Login">Login</option>
                <option value="Logout">Logout</option>
                <option value="View User">View User</option>
                <option value="Edit User">Edit User</option>
                <option value="Block User">Block User</option>
                <option value="Unblock User">Unblock User</option>
                <option value="Delete User">Delete User</option>
                <option value="Update User">Update User</option>
              </select>

              <select id="roleFilter" class="filter-select" aria-label="Role Filter">
                <option value="all" selected>All Roles</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div class="control-right">
              <div class="entries-select-wrapper">
                <label for="entriesSelect">Show Entries:</label>
                <select id="entriesSelect" class="filter-select" >
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>
          </div>

          <div class="control-row">
            <div class="date-filter-group">
              <label for="startDateInput"><i class="fa-regular fa-calendar-days"></i> From:</label>
              <input type="date" id="startDateInput" class="date-input" />
              <label for="endDateInput">To:</label>
              <input type="date" id="endDateInput" class="date-input" />
              <button type="button" id="btnClearDate" class="btn-clear-date" title="Clear Date Range">
                <i class="fa-solid fa-xmark"></i> Clear Dates
              </button>
            </div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-responsive">
            <table class="audit-table" id="auditTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User ID</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Action</th>
                  <th>Details</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                </tr>
              </thead>
              <tbody id="auditTableBody">
              </tbody>
            </table>
          </div>

          <div class="table-footer">
            <div class="pagination-info" id="paginationInfo">
              Showing 0 to 0 of 0 entries
            </div>
            <div class="pagination-controls" id="paginationControls">
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="table-card">
          <div class="restricted-state">
            <i class="fa-solid fa-file-shield"></i>
            <h3>Access Restricted</h3>
            <p>You do not have the required audit log privileges to view this list.</p>
          </div>
        </div>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="../../js/admin/admin.js"></script>
  <script src="../../js/super_admin/audit_logs.js"></script>
</body>

</html>