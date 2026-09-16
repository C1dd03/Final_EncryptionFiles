<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$pageTitle = 'Pending Approvals';
$activePage = 'approvals';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pending Approvals - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/super_admin/block_list.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
  <style>
    :root {
      --farm-bg: #dae5cf;
      --farm-card-1: #588157;
      --farm-card-2: #6c8a5d;
      --farm-card-3: #7e9b65;
      --farm-card-4: #a3b18a;
      --farm-card-5: #dad7cd;
      --farm-text: #2f3f33;
      --farm-border: #9aa68e;
      --farm-muted: #6c7a63;
      --farm-white: #f6f3e8;
    }

    .badge-pending {
      background: #fef3c7;
      color: #92400e;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    /* Responsive layout & eliminate horizontal scrolling */
    .superadmin-main,
    .superadmin-content {
      min-width: 0 !important;
    }
    .superadmin-content {
      overflow-x: hidden !important;
    }
    .control-card {
      min-width: 0 !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .filter-group-flex {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
      flex: 1 1 520px;
      min-width: 0;
    }
    .search-box {
      flex: 1 1 200px;
      min-width: 180px;
      max-width: 320px;
    }
    .table-card {
      min-width: 0 !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }
    .table-responsive {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
    }
    .block-table {
      width: 100% !important;
      min-width: 0 !important;
    }
    .block-table th,
    .block-table td {
      padding: 12px 10px !important;
      vertical-align: middle;
    }
    .block-table td:nth-child(2) {
      white-space: normal !important;
      min-width: 120px;
    }
    .block-table td:nth-child(4) {
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      min-width: 140px;
    }
    .block-table td:nth-child(6) {
      white-space: nowrap !important;
      font-size: 0.82rem;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-top: 10px;
    }

    .detail-item {
      background: #f8fafc;
      padding: 10px 14px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    }

    .detail-label {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 600;
    }

    .detail-val {
      font-size: 13px;
      font-weight: 500;
      color: #1e293b;
      margin-top: 2px;
    }

    /* ── Action Dropdown (Options Button) ── */
    .action-dropdown {
      position: relative;
      display: inline-block;
    }

    .action-dropdown-btn {
      background: linear-gradient(135deg, var(--farm-card-1), var(--farm-card-3));
      color: #ffffff;
      border: none;
      padding: 7px 14px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.86rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      box-shadow: 0 2px 6px rgba(88, 129, 87, 0.25);
      transition: all 0.2s ease;
      min-width: 100px;
      font-family: inherit;
    }

    .action-dropdown-btn:hover,
    .action-dropdown-btn.active {
      background: linear-gradient(135deg, var(--farm-card-1), var(--farm-card-3));
      box-shadow: 0 4px 10px rgba(88, 129, 87, 0.35);
    }

    .action-dropdown-btn .dropdown-chevron {
      font-size: 11px;
      transition: transform 0.2s ease;
    }

    .action-dropdown-btn.active .dropdown-chevron {
      transform: rotate(180deg);
    }

    .action-dropdown-menu {
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
      min-width: 180px;
      z-index: 1050;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-6px) scale(0.97);
      transform-origin: top right;
      transition: all 0.18s ease;
      padding: 6px;
    }

    .action-dropdown-menu.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0) scale(1);
    }

    .action-menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      background: transparent;
      font-size: 0.86rem;
      font-weight: 500;
      color: #334155;
      cursor: pointer;
      text-align: left;
      transition: background 0.15s ease, color 0.15s ease;
      font-family: inherit;
    }

    .action-menu-item i {
      width: 16px;
      text-align: center;
      font-size: 13px;
      color: #64748b;
    }

    .action-menu-item:hover {
      background: #f1f5f9;
      color: #0f172a;
    }

    .action-menu-item.view i {
      color: #0284c7;
    }

    .action-menu-item.view:hover {
      background: #f0f9ff;
      color: #0369a1;
    }

    .action-menu-item.approve i {
      color: #10b981;
    }

    .action-menu-item.approve:hover {
      background: #ecfdf5;
      color: #047857;
    }

    .action-menu-item.reject i {
      color: #ef4444;
    }

    .action-menu-item.reject:hover {
      background: #fef2f2;
      color: #b91c1c;
    }

    .action-menu-divider {
      height: 1px;
      background: #f1f5f9;
      margin: 4px 0;
    }
  </style>
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
            <h2><i class="fa-solid fa-user-check" style="color: #4f46e5;"></i> Pending Approvals</h2>
            <p>Review registrations and account invitations. Invited recipients must complete setup before their accounts are created.</p>
          </div>
        </div>

        <!-- Controls: Search, Month/Date Filter & Show Entries -->
        <div class="control-card">
          <div class="control-left filter-group-flex">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search ID No, Name, Username, Email..." />
            </div>

            <select id="statusFilter" class="filter-select" aria-label="Status Filter">
              <option value="all" selected>All Statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>

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

            <select id="yearFilter" class="filter-select" aria-label="Year Filter">
              <option value="all" selected>All Years</option>
              <option value="2026">2026</option>
              <option value="2027">2027</option>
            </select>

            <div class="date-filter-group" style="display:inline-flex; align-items:center; gap:4px;">
              <input type="date" id="startDateInput" class="date-input" title="From Date" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;" />
              <span style="color:#64748b; font-size:12px;">to</span>
              <input type="date" id="endDateInput" class="date-input" title="To Date" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;" />
              <button type="button" id="btnClearDate" class="btn-clear-date" title="Clear Filters" style="padding:6px 10px; border:1px solid #cbd5e1; background:#fff; border-radius:6px; font-size:12px; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i> Clear
              </button>
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

        <!-- Pending Registrations Table -->
        <div class="table-card">
          <div class="table-responsive">
            <table class="block-table" id="approvalsTable">
              <thead>
                <tr>
                  <th>ID No</th>
                  <th>Full Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Gender / Age</th>
                  <th>Registration Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="approvalsTableBody">
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

  <!-- User Details Modal -->
  <div class="modal" id="detailsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:12px; max-width:600px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:18px; font-weight:700; color:#1e293b; margin:0;"><i class="fa-solid fa-id-card"></i> Registration Details</h3>
        <button type="button" onclick="closeDetailsModal()" style="border:none; background:none; font-size:18px; cursor:pointer; color:#64748b;">&times;</button>
      </div>
      <div id="detailsModalBody">
        <!-- Injected via JS -->
      </div>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
        <button type="button" class="btn-secondary" onclick="closeDetailsModal()" style="padding:8px 16px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; cursor:pointer;">Close</button>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal (Approve / Reject) -->
  <div class="modal" id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:12px; max-width:480px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
      <h3 id="confirmModalTitle" style="font-size:18px; font-weight:700; color:#1e293b; margin:0 0 10px 0;"></h3>
      <p id="confirmModalMsg" style="font-size:14px; color:#475569; margin:0 0 16px 0;"></p>

      <div id="approvalPasswordGroup" style="margin-bottom:16px;">
        <label for="approvalOperatorPassword" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px;">Your Current Password:</label>
        <input type="password" id="approvalOperatorPassword" autocomplete="current-password" style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:6px;padding:9px;" />
      </div>

      <div id="rejectReasonGroup" style="display:none; margin-bottom:16px;">
        <label for="rejectReasonInput" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:4px;">Reason for Rejection:</label>
        <textarea id="rejectReasonInput" rows="3" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:13px;" placeholder="Enter reason for rejecting this registration..."></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeConfirmModal()" style="padding:8px 16px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; cursor:pointer;">Cancel</button>
        <button type="button" id="confirmModalSubmitBtn" style="padding:8px 18px; border:none; border-radius:6px; font-weight:600; color:#fff; cursor:pointer;"></button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" style="position:fixed; bottom:20px; right:20px; z-index:2000;"></div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/approvals.js?v=<?= time() ?>"></script>
</body>

</html>
