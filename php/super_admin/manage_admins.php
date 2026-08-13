<?php
require_once __DIR__ . '/../auth/session_protect.php';

// Only Super Admin can access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$pageTitle = 'Manage Admins';
$activePage = 'manage_admins';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Admins - Super Admin</title>
  <link rel="stylesheet" href="../../css/super_admin/superadmin.css" />
  <link rel="stylesheet" href="../../css/super_admin/manage_admins.css" />
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
        <div class="manage-admins-header">
          <div>
            <h2>Manage Admins</h2>
            <p>Review, create, edit, block/unblock, and manage privileged administrator accounts.</p>
          </div>
          <button type="button" class="btn-primary" id="openAddAdminBtn">
            <i class="fa-solid fa-plus"></i> Add Admin
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
              <option value="admin" selected>Role: Admin</option>
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

        <!-- Responsive Admin Data Table -->
        <div class="table-card">
          <div class="table-responsive">
            <table class="admin-table" id="adminTable">
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
              <tbody id="adminTableBody">
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

  <!-- Add / Edit Admin Modal -->
  <div class="modal-overlay" id="adminModal">
    <div class="modal-card modal-card-lg">
      <div class="modal-header">
        <h3 id="adminModalTitle">Add New Admin</h3>
        <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
      </div>
      <form id="adminForm">
        <div class="modal-body">
          <input type="hidden" id="formMode" value="add" />

          <!-- Personal Information Section -->
          <div class="form-section-title">
            <i class="fa-solid fa-user"></i> Personal Information
          </div>
          <div class="form-grid">
            <div class="form-group full-width">
              <label for="formIdNumber">ID No (Optional - Auto-generated if blank)</label>
              <input type="text" id="formIdNumber" name="id_number" class="form-control" placeholder="e.g. 2026-0002" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formFirstName">First Name *</label>
              <input type="text" id="formFirstName" name="first_name" class="form-control" placeholder="First Name" />
              <div class="input-error-container" aria-live="polite"></div>
              <div class="message" id="message"></div>
            </div>

            <div class="form-group">
              <label for="formMiddleName">Middle Name</label>
              <input type="text" id="formMiddleName" name="middle_name" class="form-control" placeholder="Middle Name (Optional)" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formLastName">Last Name *</label>
              <input type="text" id="formLastName" name="last_name" class="form-control" placeholder="Last Name" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formExtension">Name Extension</label>
              <input type="text" id="formExtension" name="extension" class="form-control" placeholder="Jr., Sr., I, II, etc." pattern="^(Jr\.?|Sr\.?|I|II|III|IV|V|VI|VII|VIII|IX|X)$" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formBirthdate">Birthdate *</label>
              <input type="date" id="formBirthdate" name="birthdate" class="form-control" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formAge">Age</label>
              <input type="text" id="formAge" name="age" class="form-control" readonly placeholder="Auto-calculated" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group full-width">
              <label for="formGender">Gender *</label>
              <select id="formGender" name="gender" class="form-control">
                <option value="" disabled selected hidden>Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <div class="input-error-container" aria-live="polite"></div>
            </div>
          </div>

          <!-- Address Information Section -->
          <div class="form-section-title">
            <i class="fa-solid fa-location-dot"></i> Address Information
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label for="formStreet">Purok / Street *</label>
              <input type="text" id="formStreet" name="street" class="form-control" placeholder="Purok / Street" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formBarangay">Barangay *</label>
              <input type="text" id="formBarangay" name="barangay" class="form-control" placeholder="Barangay" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formCity">Municipal / City *</label>
              <input type="text" id="formCity" name="city" class="form-control" placeholder="Municipal / City" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formProvince">Province *</label>
              <input type="text" id="formProvince" name="province" class="form-control" placeholder="Province" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formCountry">Country *</label>
              <input type="text" id="formCountry" name="country" class="form-control" placeholder="Country" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formZip">Zip Code *</label>
              <input type="number" id="formZip" name="zip" class="form-control" placeholder="Zip Code" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>
          </div>

          <!-- Security Questions Section -->
          <div class="form-section-title" id="securitySectionTitle">
            <i class="fa-solid fa-shield-halved"></i> Security Questions
          </div>
          <div class="form-grid" id="securitySectionGrid">
            <div class="form-group">
              <label for="formSecQ1">Question 1 *</label>
              <select id="formSecQ1" name="security_question_1" class="form-control">
                <option value="" disabled selected hidden>Select Question 1</option>
                <option value="1">Who is your best friend in elementary?</option>
                <option value="2">What is the name of your favorite pet?</option>
                <option value="3">Who is your favorite teacher in high school?</option>
              </select>
              <div class="input-error-container" aria-live="polite"></div>
            </div>
            <div class="form-group">
              <label for="formSecA1">Answer 1 *</label>
              <input type="password" id="formSecA1" name="security_q1" class="form-control" placeholder="Answer 1" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formSecQ2">Question 2 *</label>
              <select id="formSecQ2" name="security_question_2" class="form-control">
                <option value="" disabled selected hidden>Select Question 2</option>
                <option value="4">What is your mother’s maiden name?</option>
                <option value="5">What city were you born in?</option>
                <option value="6">What is your favorite color?</option>
              </select>
              <div class="input-error-container" aria-live="polite"></div>
            </div>
            <div class="form-group">
              <label for="formSecA2">Answer 2 *</label>
              <input type="password" id="formSecA2" name="security_q2" class="form-control" placeholder="Answer 2" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formSecQ3">Question 3 *</label>
              <select id="formSecQ3" name="security_question_3" class="form-control">
                <option value="" disabled selected hidden>Select Question 3</option>
                <option value="7">What is your favorite food?</option>
                <option value="8">What was the name of your first school?</option>
                <option value="9">What is your father’s middle name?</option>
              </select>
              <div class="input-error-container" aria-live="polite"></div>
            </div>
            <div class="form-group">
              <label for="formSecA3">Answer 3 *</label>
              <input type="password" id="formSecA3" name="security_q3" class="form-control" placeholder="Answer 3" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>
          </div>

          <!-- Account Information Section -->
          <div class="form-section-title">
            <i class="fa-solid fa-lock"></i> Account Information
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label for="formUsername">Username *</label>
              <input type="text" id="formUsername" name="username" class="form-control" placeholder="admin.username" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formEmail">Email Address *</label>
              <input type="email" id="formEmail" name="email" class="form-control" placeholder="admin@example.com" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formRole">Role</label>
              <input type="text" id="formRole" name="role" class="form-control" value="admin" readonly />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group">
              <label for="formStatus">Status</label>
              <select id="formStatus" name="status" class="form-control">
                <option value="active" selected>Active</option>
                <option value="block">Blocked</option>
              </select>
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group full-width" id="passwordGroup">
              <label for="formPassword">Password *</label>
              <input type="password" id="formPassword" name="password" class="form-control" placeholder="Enter password" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>

            <div class="form-group full-width" id="confirmPasswordGroup">
              <label for="formConfirmPassword">Confirm Password *</label>
              <input type="password" id="formConfirmPassword" name="confirm_password" class="form-control" placeholder="Confirm password" />
              <div class="input-error-container" aria-live="polite"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelModalBtn">Cancel</button>
          <button type="submit" class="btn-primary" id="saveAdminBtn">Save Admin</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Admin Modal -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Admin Details</h3>
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
        <button type="button" class="btn-secondary" id="closeViewModalBtn">Close</button>
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
  <script src="../../js/super_admin/manage_admins.js?v=<?= time() ?>"></script>
</body>

</html>