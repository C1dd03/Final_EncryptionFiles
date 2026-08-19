<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$pageTitle = 'Registration Approvals';
$activePage = 'approvals';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration Approvals - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/super_admin/block_list.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
  <style>
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
    .action-btn-group {
      display: flex;
      gap: 6px;
    }
    .btn-approve {
      background: #10b981;
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
    .btn-approve:hover {
      background: #059669;
    }
    .btn-reject {
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
    .btn-reject:hover {
      background: #dc2626;
    }
    .btn-view-details {
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
    .btn-view-details:hover {
      background: #2563eb;
    }
    .filter-group-flex {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
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
            <h2><i class="fa-solid fa-user-check" style="color: #4f46e5;"></i> Registration Approvals</h2>
            <p>Review, filter, and approve or reject newly submitted user registration requests before activation.</p>
          </div>
        </div>

        <!-- Controls: Search, Month/Date Filter & Show Entries -->
        <div class="control-card">
          <div class="control-left filter-group-flex">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search ID No, Name, Username, Email..." />
            </div>

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
