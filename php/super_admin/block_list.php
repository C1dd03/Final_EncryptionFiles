<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$pageTitle = 'Block List';
$activePage = 'block_list';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Block List - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/super_admin/block_list.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
</head>

<body>
  <div class="superadmin-app">
    <?php include 'includes/superadmin_sidebar.php'; ?>
    <div class="superadmin-main">
      <?php include 'includes/superadmin_header.php'; ?>
      <main class="superadmin-content">

        <!-- Page Header -->
        <div class="block-list-header">
          <div>
            <h2>Block List</h2>
            <p>View, search, and unblock restricted administrator and user accounts.</p>
          </div>
        </div>

        <!-- Controls: Search & Show Entries -->
        <div class="control-card">
          <div class="control-left">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search ID No, Name, Username, Email, Blocked By..." />
            </div>
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

        <!-- Blocked Admins Table -->
        <div class="block-section">
          <div class="block-section-header">
            <h3><i class="fa-solid fa-user-shield"></i> Blocked Admins</h3>
          </div>
          <div class="table-card">
            <div class="table-responsive">
              <table class="block-table" id="blockAdminTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>ID No</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Blocked By</th>
                    <th>Blocked At</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="blockAdminTableBody">
                  <!-- Dynamically Populated via JS -->
                </tbody>
              </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="table-footer">
              <div class="pagination-info" id="adminPaginationInfo">
                Showing 0 to 0 of 0 entries
              </div>
              <div class="pagination-controls" id="adminPaginationControls">
                <!-- Dynamically Populated via JS -->
              </div>
            </div>
          </div>
        </div>

        <!-- Blocked Users Table -->
        <div class="block-section">
          <div class="block-section-header">
            <h3><i class="fa-solid fa-user"></i> Blocked Users</h3>
          </div>
          <div class="table-card">
            <div class="table-responsive">
              <table class="block-table" id="blockUserTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>ID No</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Blocked By</th>
                    <th>Blocked At</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="blockUserTableBody">
                  <!-- Dynamically Populated via JS -->
                </tbody>
              </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="table-footer">
              <div class="pagination-info" id="userPaginationInfo">
                Showing 0 to 0 of 0 entries
              </div>
              <div class="pagination-controls" id="userPaginationControls">
                <!-- Dynamically Populated via JS -->
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- Detail View Modal -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Blocked Account Details</h3>
        <button type="button" class="modal-close" id="closeViewModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-list">
          <div class="detail-item">
            <label>Record ID</label>
            <span id="viewRecordId">-</span>
          </div>
          <div class="detail-item">
            <label>ID Number</label>
            <span id="viewIdNumber">-</span>
          </div>
          <div class="detail-item">
            <label>Full Name</label>
            <span id="viewName">-</span>
          </div>
          <div class="detail-item">
            <label>Username</label>
            <span id="viewUsername">-</span>
          </div>
          <div class="detail-item">
            <label>Email</label>
            <span id="viewEmail">-</span>
          </div>
          <div class="detail-item">
            <label>Account Role</label>
            <span id="viewRole">-</span>
          </div>
          <div class="detail-item">
            <label>Blocked By</label>
            <span id="viewBlockedBy">-</span>
          </div>
          <div class="detail-item">
            <label>Reason</label>
            <span id="viewReason">-</span>
          </div>
          <div class="detail-item">
            <label>Blocked At</label>
            <span id="viewBlockedAt">-</span>
          </div>
          <div class="detail-item" style="grid-column: span 2;">
            <label>Current Status</label>
            <span id="viewStatus">-</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="closeViewModalFooterBtn">Close</button>
      </div>
    </div>
  </div>

  <!-- Unblock Confirmation Modal -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 id="confirmModalTitle">Confirm Action</h3>
        <button type="button" class="modal-close" id="closeConfirmModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <p id="confirmModalMessage" style="margin:0; font-size: 0.95rem; color: var(--farm-text);"></p>
        <div id="unblockPasswordGroup" style="display:none;">
          <label for="unblockOperatorPassword" style="display:block;margin-top:14px;font-size:13px;font-weight:600;">Your Current Password</label>
          <input type="password" id="unblockOperatorPassword" autocomplete="current-password" style="width:100%;box-sizing:border-box;margin-top:5px;padding:9px;border:1px solid #cbd5e1;border-radius:6px;" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelConfirmModalBtn">Cancel</button>
        <button type="button" class="btn-success" id="confirmModalBtn">Yes, Continue</button>
      </div>
    </div>
  </div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/block_list.js?v=<?= time() ?>"></script>
</body>

</html>
