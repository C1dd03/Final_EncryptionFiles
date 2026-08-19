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
    .action-btn-group {
      display: flex;
      gap: 6px;
    }
    .btn-approve-del {
      background: #ef4444;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: background 0.2s;
    }
    .btn-approve-del:hover {
      background: #dc2626;
    }
    .btn-reject-req {
      background: #64748b;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: background 0.2s;
    }
    .btn-reject-req:hover {
      background: #475569;
    }
    .btn-view-req {
      background: #3b82f6;
      color: #fff;
      border: none;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-view-req:hover {
      background: #2563eb;
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
            <h2><i class="fa-solid fa-trash-arrow-up" style="color: #ef4444;"></i> Admin Delete Requests</h2>
            <p>Review deletion requests submitted by Administrators. Inspect complete user details, stated reasons, and execute or reject deletions.</p>
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
              <option value="approved">Approved & Deleted</option>
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
        <h3 style="font-size:18px; font-weight:700; color:#1e293b; margin:0;"><i class="fa-solid fa-file-circle-exclamation" style="color:#ef4444;"></i> Delete Request & Complete User Info</h3>
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
