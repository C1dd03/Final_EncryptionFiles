<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$pageTitle = 'Delete Requests';
$activePage = 'delete_requests';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Delete Requests - Super Admin</title>
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

    .badge-status-pending {
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

    .badge-status-approved {
      background: #dcfce7;
      color: #166534;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .badge-status-rejected {
      background: #fee2e2;
      color: #991b1b;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .reason-box {
      max-width: 250px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: 13px;
      color: #475569;
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

    .action-menu-item.delete i {
      color: #ef4444;
    }

    .action-menu-item.delete:hover {
      background: #fef2f2;
      color: #b91c1c;
    }

    .action-menu-item.reject-req i {
      color: #64748b;
    }

    .action-menu-item.reject-req:hover {
      background: #f1f5f9;
      color: #334155;
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
            <h2><i class="fa-solid fa-trash-arrow-up" style="color: #ef4444;"></i> Account Deletion Requests</h2>
            <p>Review deletion requests submitted by Administrators. Approval marks an account Inactive; rejection restores Active status.</p>
          </div>
        </div>

        <!-- Controls: Search & Show Entries -->
        <div class="control-card">
          <div class="control-left" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search Target ID, Name, Admin Username, Reason..." />
            </div>

            <select id="statusFilter" class="filter-select" aria-label="Status Filter">
              <option value="pending" selected>Pending Review</option>
              <option value="approved">Approved / Inactive</option>
              <option value="rejected">Rejected</option>
              <option value="all">All Requests</option>
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

        <!-- Delete Requests Table -->
        <div class="table-card">
          <div class="table-responsive">
            <table class="block-table" id="requestsTable">
              <thead>
                <tr>
                  <th>Req ID</th>
                  <th>Target ID No</th>
                  <th>Target User</th>
                  <th>Requested By Admin</th>
                  <th>Reason for Deletion</th>
                  <th>Requested At</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="requestsTableBody">
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

  <!-- Request & Target User Details Modal -->
  <div class="modal" id="detailsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:12px; max-width:650px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); max-height:90vh; overflow-y:auto;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:18px; font-weight:700; color:#1e293b; margin:0;"><i class="fa-solid fa-file-circle-exclamation" style="color:#ef4444;"></i> Deletion Request & Account Information</h3>
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

  <!-- Confirmation Modal (Approve Deletion / Reject Request) -->
  <div class="modal" id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="background:#fff; border-radius:12px; max-width:480px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
      <h3 id="confirmModalTitle" style="font-size:18px; font-weight:700; color:#1e293b; margin:0 0 10px 0;"></h3>
      <p id="confirmModalMsg" style="font-size:14px; color:#475569; margin:0 0 16px 0;"></p>

      <div id="deleteApprovalPasswordGroup" style="display:none;margin-bottom:16px;">
        <label for="deleteApprovalPassword" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px;">Your Current Password:</label>
        <input type="password" id="deleteApprovalPassword" autocomplete="current-password" style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:6px;padding:9px;" />
      </div>

      <div id="rejectNotesGroup" style="display:none; margin-bottom:16px;">
        <label for="rejectNotesInput" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:4px;">Rejection Notes / Reason:</label>
        <textarea id="rejectNotesInput" rows="3" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:13px;" placeholder="Enter note explaining why deletion was rejected..."></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeConfirmModal()" style="padding:8px 16px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; cursor:pointer;">Cancel</button>
        <button type="button" id="confirmModalSubmitBtn" style="padding:8px 18px; border:none; border-radius:6px; font-weight:600; color:#fff; cursor:pointer;"></button>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer" style="position:fixed; bottom:20px; right:20px; z-index:2000;"></div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/delete_requests.js?v=<?= time() ?>"></script>
</body>

</html>
