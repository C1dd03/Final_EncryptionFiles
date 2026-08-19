<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'superadmin') {
  header("Location: ../auth/index.php?action=login");
  exit();
}

$userModel = new User();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$pageRaw = $_GET['page'] ?? 1;
$page = max(1, (int)$pageRaw);
$limitRaw = $_GET['limit'] ?? 10;
$limit = in_array((int)$limitRaw, [10, 25, 50, 100], true) ? (int)$limitRaw : 10;

$totalRecords = $userModel->getAdminsCount($search, $status);
$totalPages = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) {
  $page = $totalPages;
}
$offset = ($page - 1) * $limit;
$admins = $userModel->getAdminsList($search, $status, $offset, $limit);

$startIdx = $totalRecords > 0 ? $offset + 1 : 0;
$endIdx = min($page * $limit, $totalRecords);

$pageTitle = 'Manage Admins';
$activePage = 'manage_admins';

function e($text)
{
  return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
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
        <form method="GET" class="control-card" id="controlForm">
          <div class="control-left">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="searchInput" name="search" placeholder="Search ID No, Name, Username, Email..." value="<?= e($search) ?>" />
            </div>

            <select id="statusFilter" name="status" class="filter-select" aria-label="Status Filter">
              <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Status: All</option>
              <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Blocked</option>
            </select>

            <select id="roleFilter" class="filter-select" disabled aria-label="Role Filter">
              <option value="admin" selected>Role: Admin</option>
            </select>
          </div>

          <div class="control-right">
            <div class="entries-select-wrapper">
              <label for="entriesSelect">Show Entries:</label>
              <select id="entriesSelect" name="limit" class="filter-select">
                <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
              </select>
            </div>
          </div>
        </form>

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
                <?php if (!empty($admins)): ?>
                  <?php foreach ($admins as $index => $admin):
                    $rowId = $startIdx + $index;
                    $isBlocked = $admin['status'] === 'block' || $admin['status'] === 'blocked';
                    $adminName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['middle_name'] ?? '') . ' ' . ($admin['last_name'] ?? '') . ' ' . ($admin['extension'] ?? ''));
                    $dataName = $admin['name'] ?? $adminName;
                    $dataEmail = $admin['email'] ?? '';
                  ?>
                    <tr
                      data-id_number="<?= e($admin['id_number']) ?>"
                      data-name="<?= e($dataName) ?>"
                      data-username="<?= e($admin['username']) ?>"
                      data-email="<?= e($dataEmail) ?>"
                      data-status="<?= e($admin['status']) ?>"
                      data-role="admin"
                      data-created="<?= e($admin['created_at'] ?? '') ?>">
                      <td><strong>#<?= $rowId ?></strong></td>
                      <td><code><?= e($admin['id_number']) ?></code></td>
                      <td><strong><?= e($dataName) ?></strong></td>
                      <td><?= e($admin['username']) ?></td>
                      <td><?= $dataEmail !== '' ? e($dataEmail) : 'N/A' ?></td>
                      <td>
                        <?php
                          $adminRole = $admin['role'] ?? 'admin';
                          $roleIcon  = match($adminRole) {
                            'superadmin' => 'fa-user-astronaut',
                            'admin'      => 'fa-user-shield',
                            default      => 'fa-user',
                          };
                          $roleLabel = match($adminRole) {
                            'superadmin' => 'Super Admin',
                            'admin'      => 'Admin',
                            default      => 'User',
                          };
                        ?>
                        <div class="role-dropdown"
                          data-id_number="<?= e($admin['id_number']) ?>"
                          data-username="<?= e($admin['username']) ?>"
                          data-name="<?= e($dataName) ?>"
                          data-current-role="<?= e($adminRole) ?>">
                          <!-- Hidden native select keeps data attrs; JS fires change on it -->
                          <select class="role-select" aria-hidden="true" tabindex="-1"
                            data-id_number="<?= e($admin['id_number']) ?>"
                            data-username="<?= e($admin['username']) ?>"
                            data-name="<?= e($dataName) ?>">
                            <option value="user"       <?= $adminRole === 'user'       ? 'selected' : '' ?>>User</option>
                            <option value="admin"      <?= $adminRole === 'admin'      ? 'selected' : '' ?>>Admin</option>
                            <option value="superadmin" <?= $adminRole === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                          </select>
                          <!-- Visible trigger button -->
                          <button type="button" class="role-dropdown-btn role-btn-<?= e($adminRole) ?>"
                            aria-haspopup="listbox" aria-expanded="false"
                            aria-label="Change role for <?= e($admin['username']) ?>">
                            <i class="fa-solid <?= e($roleIcon) ?>"></i>
                            <span class="role-btn-label"><?= e($roleLabel) ?></span>
                            <i class="fa-solid fa-chevron-down role-dropdown-chevron"></i>
                          </button>
                          <!-- Dropdown menu -->
                          <div class="role-dropdown-menu" role="listbox">
                            <button type="button" class="role-option role-opt-user <?= $adminRole === 'user' ? 'active' : '' ?>" data-value="user" role="option">
                              <i class="fa-solid fa-user"></i>
                              <div class="role-opt-text">
                                <span class="role-opt-label">User</span>
                                <span class="role-opt-desc">Standard account</span>
                              </div>
                              <?php if ($adminRole === 'user'): ?>
                              <i class="fa-solid fa-check role-opt-check"></i>
                              <?php endif; ?>
                            </button>
                            <button type="button" class="role-option role-opt-admin <?= $adminRole === 'admin' ? 'active' : '' ?>" data-value="admin" role="option">
                              <i class="fa-solid fa-user-shield"></i>
                              <div class="role-opt-text">
                                <span class="role-opt-label">Admin</span>
                                <span class="role-opt-desc">Privileged account</span>
                              </div>
                              <?php if ($adminRole === 'admin'): ?>
                              <i class="fa-solid fa-check role-opt-check"></i>
                              <?php endif; ?>
                            </button>
                            <button type="button" class="role-option role-opt-superadmin <?= $adminRole === 'superadmin' ? 'active' : '' ?>" data-value="superadmin" role="option">
                              <i class="fa-solid fa-user-astronaut"></i>
                              <div class="role-opt-text">
                                <span class="role-opt-label">Super Admin</span>
                                <span class="role-opt-desc">Full system access</span>
                              </div>
                              <?php if ($adminRole === 'superadmin'): ?>
                              <i class="fa-solid fa-check role-opt-check"></i>
                              <?php endif; ?>
                            </button>
                          </div>
                        </div>
                      </td>
                      <td>
                        <?php if ($isBlocked): ?>
                          <span class="badge-status badge-blocked">Blocked</span>
                        <?php else: ?>
                          <span class="badge-status badge-active">Active</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="action-dropdown">
                          <button type="button" class="action-dropdown-btn" onclick="toggleActionDropdown(this)" aria-expanded="false" aria-label="Action options">
                            <span>Options</span>
                            <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
                          </button>
                          <div class="action-dropdown-menu">
                            <button type="button" class="action-menu-item view" onclick="viewAdmin(this)">
                              <i class="fa-solid fa-eye"></i>
                              <span>View Details</span>
                            </button>
                            <button type="button" class="action-menu-item edit" onclick="editAdmin(this)">
                              <i class="fa-solid fa-pen"></i>
                              <span>Edit</span>
                            </button>
                            <button type="button" class="action-menu-item privileges" onclick="openPrivilegesModal(this)">
                              <i class="fa-solid fa-shield-halved"></i>
                              <span>Privileges</span>
                            </button>
                            <button type="button" class="action-menu-item <?= $isBlocked ? 'unblock' : 'block' ?>" onclick="confirmToggleBlock(this)">
                              <i class="fa-solid <?= $isBlocked ? 'fa-unlock' : 'fa-ban' ?>"></i>
                              <span><?= $isBlocked ? 'Unblock' : 'Block' ?></span>
                            </button>
                            <div class="action-menu-divider"></div>
                            <button type="button" class="action-menu-item delete" onclick="confirmDeleteAdmin(this)">
                              <i class="fa-solid fa-trash"></i>
                              <span>Delete</span>
                            </button>
                          </div>
                        </div>
                      </td>
                      

                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="empty-state">
                      <i class="fa-solid fa-user-slash"></i>
                      <p>No administrator accounts found.</p>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
            
          </div>

          <!-- Table Footer / Pagination -->
          <div class="table-footer">
            <div class="pagination-info" id="paginationInfo">
              <?php if ($totalRecords > 0): ?>
                Showing <?= $startIdx ?> to <?= $endIdx ?> of <?= $totalRecords ?> entries
              <?php else: ?>
                Showing 0 entries
              <?php endif; ?>
            </div>
            <div class="pagination-controls" id="paginationControls">
              <?php if ($totalRecords > 0):
                $queryBase = function ($p) use ($search, $status, $limit) {
                  $qs = http_build_query([
                    'search' => $search,
                    'status' => $status,
                    'limit' => $limit,
                    'page' => $p
                  ]);
                  return '?' . $qs;
                };
              ?>
                <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $queryBase($page - 1) ?>">
                  <i class="fa-solid fa-chevron-left"></i>
                </a>
                <?php for ($i = 1; $i <= $totalPages; $i++):
                  $show = $i === 1 || $i === $totalPages || ($i >= $page - 1 && $i <= $page + 1);
                  $ellipsisBefore = $i === $page - 2 && $i > 2;
                  $ellipsisAfter = $i === $page + 2 && $i < $totalPages - 1;
                ?>
                  <?php if ($ellipsisBefore || $ellipsisAfter): ?>
                    <span style="padding: 0 4px; color: var(--farm-muted);">...</span>
                  <?php endif; ?>
                  <?php if ($show): ?>
                    <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= $queryBase($i) ?>"><?= $i ?></a>
                  <?php endif; ?>
                <?php endfor; ?>
                <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : $queryBase($page + 1) ?>">
                  <i class="fa-solid fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
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
                <option value="4">What is your mother's maiden name?</option>
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
                <option value="9">What is your father's middle name?</option>
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

  <!-- Change Role Confirmation Modal -->
  <div class="modal-overlay" id="roleConfirmModal">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3>Change Role</h3>
        <button type="button" class="modal-close" id="closeRoleConfirmModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <p id="roleConfirmMessage" style="margin:0; font-size: 0.95rem; color: var(--farm-text);"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelRoleConfirmModalBtn">Cancel</button>
        <button type="button" class="btn-danger" id="confirmRoleChangeBtn">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Manage Privileges Modal -->
  <div class="modal-overlay" id="privilegesModal">
    <div class="modal-card modal-card-privileges" style="max-width: 600px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-shield-halved" style="margin-right: 8px;"></i> Manage Privileges</h3>
        <button type="button" class="modal-close" id="closePrivilegesModalBtn">&times;</button>
      </div>
      <div class="modal-body">
        <div class="privileges-target-card">
          <div class="priv-target-icon"><i class="fa-solid fa-user-gear"></i></div>
          <div class="priv-target-info">
            <h4 id="privilegesTargetName">Administrator</h4>
            <p id="privilegesTargetMeta">ID: <code id="privilegesTargetId">-</code> • Username: <span id="privilegesTargetUser">@admin</span></p>
          </div>
        </div>

        <div class="privileges-toolbar">
          <span class="priv-toolbar-title">Permissions Checklist</span>
          <div class="priv-toolbar-actions">
            <button type="button" class="btn-priv-tool" id="btnSelectAllPriv">
              <i class="fa-solid fa-check-double"></i> Select All
            </button>
            <button type="button" class="btn-priv-tool" id="btnDeselectAllPriv">
              <i class="fa-solid fa-xmark"></i> Deselect All
            </button>
          </div>
        </div>

        <input type="hidden" id="privilegesIdNumber" value="" />
        <input type="hidden" id="privilegesUsername" value="" />

        <div class="privilege-grid" id="privilegeCheckboxList">
          <label class="privilege-card">
            <input type="checkbox" value="view_user_logs" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-list-check"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">View User Activity Logs</span>
                <span class="priv-card-desc">Access and inspect user activity audit trails</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="view_admin_logs" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-shield-cat"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">View Admin Activity Logs</span>
                <span class="priv-card-desc">Access and inspect admin security audit trails</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="view_users" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-users"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">View All User Accounts</span>
                <span class="priv-card-desc">Search, filter, and view registered user profiles</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="block_users" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-user-slash"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">Block / Unblock Users</span>
                <span class="priv-card-desc">Restrict or reinstate active user accounts</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="delete_users" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-trash-can"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">Delete User Accounts</span>
                <span class="priv-card-desc">Permanently remove user accounts from system</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="approve_registrations" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-user-check"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">Approve / Reject Registrations</span>
                <span class="priv-card-desc">Review, accept, approve, or reject pending registrations</span>
              </div>
            </div>
          </label>

          <label class="privilege-card">
            <input type="checkbox" value="edit_users" class="privilege-checkbox" />
            <div class="priv-card-body">
              <div class="priv-card-icon"><i class="fa-solid fa-user-pen"></i></div>
              <div class="priv-card-text">
                <span class="priv-card-title">Edit User Information</span>
                <span class="priv-card-desc">Update user details, address, and profile data</span>
              </div>
            </div>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelPrivilegesModalBtn">Cancel</button>
        <button type="button" class="btn-primary" id="savePrivilegesBtn">
          <i class="fa-solid fa-floppy-disk"></i> Save Privileges
        </button>
      </div>
    </div>
  </div>

  <script src="../../js/super_admin/superadmin.js"></script>
  <script src="../../js/super_admin/manage_admins.js?v=<?= time() ?>"></script>
  
</body>

</html>