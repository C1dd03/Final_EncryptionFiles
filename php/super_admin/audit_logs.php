<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$pageTitle = 'Audit Logs';
$activePage = 'audit_logs';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Audit Logs - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css?v=20260911-details" />
  <link rel="stylesheet" href="../../css/super_admin/audit_logs.css?v=20260911-details" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
  <link rel="stylesheet" href="../../css/audit_details.css?v=20260913-full-details" />
</head>

<body>
  <div class="superadmin-app">
    <?php include 'includes/superadmin_sidebar.php'; ?>
    <div class="superadmin-main">
      <?php include 'includes/superadmin_header.php'; ?>
      <main class="superadmin-content">

        <!-- Page Header -->
        <div class="audit-header">
          <div>
            <h2>Audit Logs</h2>
            <p>Review system actions, login history, account management events, and security audit trails.</p>
          </div>
        </div>

        <!-- Controls: Search, Action, Role, Date Filters & Show Entries -->
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
                <option value="Create Admin">Create Admin</option>
                <option value="Update Admin">Update Admin</option>
                <option value="Delete Admin">Delete Admin</option>
                <option value="Deactivate Admin">Deactivate Admin</option>
                <option value="Deactivate Super Admin">Deactivate Super Admin</option>
                <option value="Block User">Block User</option>
                <option value="Unblock User">Unblock User</option>
                <option value="Update User">Update User</option>
                <option value="Deactivate User">Deactivate User</option>
                <option value="Delete Request Submitted">Delete Request Submitted</option>
                <option value="Approve Delete Request">Approve Delete Request</option>
                <option value="Reject Delete Request">Reject Delete Request</option>
              </select>

              <select id="roleFilter" class="filter-select" aria-label="Role Filter">
                <option value="all" selected>All Roles</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="superadmin">Super Admin</option>
              </select>
            </div>

            <div class="control-right">
              <div class="entries-select-wrapper">
                <label for="entriesSelect">Show Entries:</label>
                <select id="entriesSelect" class="filter-select">
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>
          </div>

          <div class="control-row">
            <div class="date-filter-group" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
              <label for="monthFilter"><i class="fa-regular fa-calendar"></i> Month:</label>
              <select id="monthFilter" class="filter-select" aria-label="Month Filter">
                <option value="all" selected>All Months</option>
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>

              <label for="yearFilter">Year:</label>
              <select id="yearFilter" class="filter-select" aria-label="Year Filter">
                <option value="all" selected>All Years</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
                <option value="2027">2027</option>
              </select>

              <label for="startDateInput"><i class="fa-regular fa-calendar-days"></i> From:</label>
              <input type="date" id="startDateInput" class="date-input" />
              <label for="endDateInput">To:</label>
              <input type="date" id="endDateInput" class="date-input" />
              <button type="button" id="btnClearDate" class="btn-clear-date" title="Clear Filters">
                <i class="fa-solid fa-xmark"></i> Clear Filters
              </button>
            </div>
          </div>
        </div>

        <!-- Responsive Audit Logs Table (Read-Only) -->
        <div class="table-card">
          <div class="table-responsive">
            <table class="audit-table" id="auditTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>ID No</th>
                  <th>Full Name</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Action</th>
                  <th>Details</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="auditTableBody">
                <!-- Dynamically Populated via JS -->
              </tbody>
            </table>
          </div>

          <!-- Table Footer / Pagination -->
          <div class="table-footer">
            <div class="pagination-info" id="paginationInfo">
              Showing 0 to 0 of 0 entries
            </div>
            <div class="pagination-controls" id="paginationControls">
              <!-- Dynamically Populated via JS -->
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/audit_logs.js?v=20260913-full-details"></script>
  <?php include __DIR__ . '/../shared/audit_detail_modal.php'; ?>
</body>

</html>
