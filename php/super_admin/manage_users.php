<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$pageTitle = 'Manage Users';
$activePage = 'manage_users';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Users - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/super_admin/manage_users.css" />
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
        <div class="manage-users-header">
          <div>
            <h2>Manage Users</h2>
            <p>Review, create, edit, block/unblock, and manage standard user accounts.</p>
          </div>
          <button type="button" class="btn-primary" id="openAddUserBtn">
            <i class="fa-solid fa-user-plus"></i> Add User
          </button>
        </div>

        <!-- Controls: Search, Filters & Entries -->
        <div class="control-card">
          <div class="control-left">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search ID No, Name, Username, Email..." />
            </div>

            <select id="statusFilter" class="filter-select" aria-label="Status Filter">
              <option value="all">Status: All</option>
              <option value="active">Active</option>
              <option value="blocked">Blocked</option>
            </select>

            <select id="roleFilter" class="filter-select" disabled aria-label="Role Filter">
              <option value="user" selected>Role: User</option>
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

        <!-- Responsive Users Data Table -->
        <div class="table-card">
          <div class="table-responsive">
            <table class="user-table" id="userTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>ID No</th>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="userTableBody">
                <!-- Dynamically Populated -->
              </tbody>
            </table>
          </div>

          <!-- Table Footer / Pagination -->
          <div class="table-footer">
            <div class="pagination-info" id="paginationInfo">Showing 0 entries</div>
            <div class="pagination-controls" id="paginationControls"></div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- Add / Edit User Modal -->
  <div class="modal-overlay" id="userModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="userModalTitle">Add New User</h3>
        <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
      </div>
      <form id="userForm">
        <div class="modal-body">
          <input type="hidden" id="formMode" value="add" />
          <div class="form-grid">
            <div class="form-group full-width">
              <label for="formIdNumber">ID No (Optional - Auto-generated if blank)</label>
              <input type="text" id="formIdNumber" name="id_number" class="form-control" placeholder="e.g. 2026-0010" />
            </div>

            <div class="form-group full-width">
              <label for="formName">Full Name *</label>
              <input type="text" id="formName" name="name" class="form-control" required placeholder="e.g. Jane Smith" />
            </div>

            <div class="form-group">
              <label for="formUsername">Username *</label>
              <input type="text" id="formUsername" name="username" class="form-control" required placeholder="user.username" />
            </div>

            <div class="form-group">
              <label for="formEmail">Email Address *</label>
              <input type="email" id="formEmail" name="email" class="form-control" required placeholder="user@example.com" />
            </div>

            <div class="form-group">
              <label for="formRole">Role</label>
              <input type="text" id="formRole" name="role" class="form-control" value="user" readonly />
            </div>

            <div class="form-group">
              <label for="formStatus">Status</label>
              <select id="formStatus" name="status" class="form-control">
                <option value="active" selected>Active</option>
                <option value="block">Blocked</option>
              </select>
            </div>

            <div class="form-group full-width" id="passwordGroup">
              <label for="formPassword">Password *</label>
              <input type="password" id="formPassword" name="password" class="form-control" placeholder="Enter password" />
            </div>

            <div class="form-group full-width" id="confirmPasswordGroup">
              <label for="formConfirmPassword">Confirm Password *</label>
              <input type="password" id="formConfirmPassword" name="confirm_password" class="form-control" placeholder="Confirm password" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelModalBtn">Cancel</button>
          <button type="submit" class="btn-primary" id="saveUserBtn">Save User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View User Modal -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3>User Details</h3>
        <button type="button" class="modal-close" id="closeViewModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-list">
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
            <label>Role</label>
            <span id="viewRole">-</span>
          </div>
          <div class="detail-item">
            <label>Status</label>
            <span id="viewStatus">-</span>
          </div>
          <div class="detail-item" style="grid-column: span 2;">
            <label>Date Registered</label>
            <span id="viewCreated">-</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="closeViewModalFooterBtn">Close</button>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal (Block / Unblock / Delete) -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 id="confirmModalTitle">Confirm Action</h3>
        <button type="button" class="modal-close" id="closeConfirmModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <p id="confirmModalMessage" style="margin:0; font-size: 0.95rem; color: var(--farm-text);"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelConfirmModalBtn">Cancel</button>
        <button type="button" class="btn-danger" id="confirmModalBtn">Confirm</button>
      </div>
    </div>
  </div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/manage_users.js"></script>
</body>

</html>
