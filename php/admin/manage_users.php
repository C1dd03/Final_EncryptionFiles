<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin') {
    header("Location: ../auth/index.php?action=login");
    exit();
}

$currentUsername = $_SESSION['username'] ?? 'Admin';
$currentIdNumber = $_SESSION['user_id'] ?? '';

// Server-side privilege resolution — every action on this page is gated by these
$userModel = new User();
$granted = $userModel->getAdminPrivileges($currentIdNumber);
$canViewUsers   = in_array('view_users', $granted, true);
$canEditUsers   = in_array('edit_users', $granted, true);
$canBlockUsers  = in_array('block_users', $granted, true);
$canDeleteUsers = in_array('delete_users', $granted, true);

$adminController = new AdminController();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$pageRaw = $_GET['page'] ?? 1;
$page = max(1, (int)$pageRaw);
$limitRaw = $_GET['limit'] ?? 10;
$limit = in_array((int)$limitRaw, [10, 25, 50, 100], true) ? (int)$limitRaw : 10;

// Restricted admins must not load any user information
if ($canViewUsers) {
    $viewData = $adminController->getUsersForView($search, $status, $page, $limit);
    $users = $viewData['records'];
    $totalRecords = $viewData['totalRecords'];
    $totalPages = $viewData['totalPages'];
    $page = $viewData['currentPage'];
    $limit = $viewData['limit'];
} else {
    $users = [];
    $totalRecords = 0;
    $totalPages = 1;
}

$startIdx = $totalRecords > 0 ? ($page - 1) * $limit + 1 : 0;
$endIdx = min($page * $limit, $totalRecords);

$pageTitle = 'Manage Users';
$activePage = 'manage_users';

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
    <title>Manage Users - Admin</title>
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

                <div class="privileges-banner">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>
                        <h4>Your Admin Privileges:</h4>
                        <ul class="privilege-list">
                            <?php if ($canViewUsers): ?><li><i class="fa-solid fa-check"></i> View Users</li><?php endif; ?>
                            <?php if ($canBlockUsers): ?><li><i class="fa-solid fa-check"></i> Block/Unblock Users</li><?php endif; ?>
                            <?php if ($canEditUsers): ?><li><i class="fa-solid fa-check"></i> Edit User Info</li><?php endif; ?>
                            <?php if ($canDeleteUsers): ?><li><i class="fa-solid fa-check"></i> Delete Users</li><?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="manage-users-header">
                    <div>
                        <h2>Manage Users</h2>
                        <p><?= $canViewUsers ? 'Review, create, edit, block/unblock, and manage standard user accounts.' : 'View and manage user accounts. Note: You can only manage users with the "user" role.' ?></p>
                    </div>
                    <?php if ($canViewUsers && $canEditUsers): ?>
                    <button type="button" class="btn-primary" id="openAddUserBtn">
                        <i class="fa-solid fa-user-plus"></i> Add User
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($canViewUsers): ?>
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
                            <option value="user" selected>Role: User</option>
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
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $index => $user):
                                        $rowId = $startIdx + $index;
                                        $isBlocked = $user['status'] === 'block' || $user['status'] === 'blocked';
                                        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . ' ' . ($user['extension'] ?? ''));
                                        $dataName = $user['name'] ?? $userName;
                                        $dataEmail = $user['email'] ?? '';
                                    ?>
                                        <tr
                                            data-id_number="<?= e($user['id_number']) ?>"
                                            data-name="<?= e($dataName) ?>"
                                            data-username="<?= e($user['username']) ?>"
                                            data-email="<?= e($dataEmail) ?>"
                                            data-status="<?= e($user['status']) ?>"
                                            data-role="user"
                                            data-created="<?= e($user['created_at'] ?? '') ?>">
                                            <td><strong>#<?= $rowId ?></strong></td>
                                            <td><code><?= e($user['id_number']) ?></code></td>
                                            <td><strong><?= e($dataName) ?></strong></td>
                                            <td>@<?= e($user['username']) ?></td>
                                            <td><?= $dataEmail !== '' ? e($dataEmail) : 'N/A' ?></td>
                                            <td><span class="badge-role-user">User</span></td>
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
                                                        <?php if ($canViewUsers): ?>
                                                        <button type="button" class="action-menu-item view" onclick="viewUser(this)">
                                                            <i class="fa-solid fa-eye"></i>
                                                            <span>View Details</span>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php if ($canEditUsers): ?>
                                                        <button type="button" class="action-menu-item edit" onclick="editUser(this)">
                                                            <i class="fa-solid fa-pen"></i>
                                                            <span>Edit</span>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php if ($canBlockUsers): ?>
                                                        <button type="button" class="action-menu-item <?= $isBlocked ? 'unblock' : 'block' ?>" onclick="confirmToggleBlock(this)">
                                                            <i class="fa-solid <?= $isBlocked ? 'fa-unlock' : 'fa-ban' ?>"></i>
                                                            <span><?= $isBlocked ? 'Unblock' : 'Block' ?></span>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php if ($canDeleteUsers): ?>
                                                        <div class="action-menu-divider"></div>
                                                        <button type="button" class="action-menu-item delete" onclick="confirmDeleteUser(this)">
                                                            <i class="fa-solid fa-trash"></i>
                                                            <span>Delete</span>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="fa-solid fa-user-slash"></i>
                                            <p>No user accounts found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

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
                                        'limit'  => $limit,
                                        'page'   => $p
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
                                    $ellipsisAfter  = $i === $page + 2 && $i < $totalPages - 1;
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
                <?php else: ?>
                <div class="table-card">
                    <div class="restricted-state">
                        <i class="fa-solid fa-user-lock"></i>
                        <h3>Access Restricted</h3>
                        <p>You do not have the required "View Users" privilege to load this list.</p>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <div class="modal-overlay" id="userModal">
        <div class="modal-card modal-card-lg" id="userModalCard">
            <div class="modal-header">
                <h3 id="userModalTitle">Add New User</h3>
                <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
            </div>

            <!-- Edit Mode User Profile Header Banner -->
            <div class="edit-profile-header" id="editUserProfileHeader" style="display: none;">
                <div class="edit-avatar" id="editUserAvatar"><i class="fa-solid fa-user"></i></div>
                <div class="edit-profile-info">
                    <div class="edit-profile-top">
                        <h4 id="editUserDisplayName">User Name</h4>
                        <span class="edit-role-badge user"><i class="fa-solid fa-user"></i> <span id="editUserRoleText">User</span></span>
                        <span class="edit-status-badge active" id="editUserStatusBadge"><i class="fa-solid fa-circle"></i> <span id="editUserStatusText">Active</span></span>
                    </div>
                    <div class="edit-profile-sub">
                        <span class="edit-meta-chip"><i class="fa-solid fa-id-card"></i> <span id="editUserIdNumber">-</span></span>
                        <span class="edit-meta-chip"><i class="fa-solid fa-at"></i> <span id="editUserUsername">-</span></span>
                        <span class="edit-meta-chip"><i class="fa-solid fa-envelope"></i> <span id="editUserEmail">-</span></span>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress Bar (for Add Mode) -->
            <div class="modal-stepper-wrapper" id="userModalStepper">
                <div class="modal-stepper-indicators">
                    <div class="stepper-line2"></div>
                    <div class="modal-step-number active" data-step="1">1</div>
                    <div class="modal-step-line" data-line="1"></div>
                    <div class="modal-step-number" data-step="2">2</div>
                    <div class="modal-step-line" data-line="2"></div>
                    <div class="modal-step-number" data-step="3">3</div>
                    <div class="modal-step-line" data-line="3"></div>
                    <div class="modal-step-number" data-step="4">4</div>
                    <div class="stepper-line2"></div>
                </div>
                <div class="modal-stepper-titles">
                    <div class="modal-step-title active" data-step="1">Personal Information</div>
                    <div class="modal-step-title-spacer"></div>
                    <div class="modal-step-title" data-step="2">Address Information</div>
                    <div class="modal-step-title-spacer"></div>
                    <div class="modal-step-title" data-step="3">Security Questions</div>
                    <div class="modal-step-title-spacer"></div>
                    <div class="modal-step-title" data-step="4">Account Information</div>
                </div>
            </div>

            <form id="userForm" novalidate>
                <input type="hidden" id="formMode" value="add" />

                <div class="modal-body" id="userModalBody">
                    <!-- Step 1: Personal Information -->
                    <div class="modal-step-pane active" id="userStep1" data-step="1">
                        <div class="form-section-title">
                            <i class="fa-solid fa-user"></i> Personal Information
                        </div>
                        <div class="form-grid modal-two-col-grid">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="formIdNumber">ID Number (Optional - Auto-generated)</label>
                                    <input type="text" id="formIdNumber" name="id_number" class="form-control" placeholder="e.g. 2026-0010" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formFirstName">First Name *</label>
                                    <input type="text" id="formFirstName" name="first_name" class="form-control" placeholder="First Name" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formMiddleName">Middle Name (Optional)</label>
                                    <input type="text" id="formMiddleName" name="middle_name" class="form-control" placeholder="Middle Name" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formLastName">Last Name *</label>
                                    <input type="text" id="formLastName" name="last_name" class="form-control" placeholder="Last Name" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>
                            </div>

                            <div class="form-col">
                                <div class="form-group">
                                    <label for="formExtension">Name Extension (Optional)</label>
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

                                <div class="form-group">
                                    <label for="formGender">Gender *</label>
                                    <select id="formGender" name="gender" class="form-control">
                                        <option value="" disabled selected hidden>Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>
                            </div>
                        </div>

                        <div class="step-nav-footer">
                            <span class="step-nav-hint">Click Next to Continue</span>
                            <button type="button" class="btn-step-nav btn-primary" onclick="nextUserStep(1)">Next &gt;</button>
                        </div>
                    </div>

                    <!-- Step 2: Address Information -->
                    <div class="modal-step-pane" id="userStep2" data-step="2">
                        <div class="form-section-title">
                            <i class="fa-solid fa-location-dot"></i> Address Information
                        </div>
                        <div class="form-grid modal-two-col-grid">
                            <div class="form-col">
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
                            </div>

                            <div class="form-col">
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
                        </div>

                        <div class="step-nav-footer">
                            <button type="button" class="btn-step-nav btn-secondary" onclick="prevUserStep(2)">&lt; Prev</button>
                            <button type="button" class="btn-step-nav btn-primary" onclick="nextUserStep(2)">Next &gt;</button>
                        </div>
                    </div>

                    <!-- Step 3: Security Questions -->
                    <div class="modal-step-pane" id="userStep3" data-step="3">
                        <div class="form-section-title" id="securitySectionTitle">
                            <i class="fa-solid fa-shield-halved"></i> Security Questions
                        </div>
                        <div class="form-grid modal-two-col-grid" id="securitySectionGrid">
                            <div class="form-col">
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
                                    <label for="formSecQ3">Question 3 *</label>
                                    <select id="formSecQ3" name="security_question_3" class="form-control">
                                        <option value="" disabled selected hidden>Select Question 3</option>
                                        <option value="7">What is your favorite food?</option>
                                        <option value="8">What was the name of your first school?</option>
                                        <option value="9">What is your father's middle name?</option>
                                    </select>
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>
                            </div>

                            <div class="form-col">
                                <div class="form-group">
                                    <label for="formSecA1">Answer 1 *</label>
                                    <input type="password" id="formSecA1" name="security_q1" class="form-control" placeholder="Answer 1" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formSecA2">Answer 2 *</label>
                                    <input type="password" id="formSecA2" name="security_q2" class="form-control" placeholder="Answer 2" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formSecA3">Answer 3 *</label>
                                    <input type="password" id="formSecA3" name="security_q3" class="form-control" placeholder="Answer 3" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>
                            </div>
                        </div>

                        <div class="step-nav-footer">
                            <button type="button" class="btn-step-nav btn-secondary" onclick="prevUserStep(3)">&lt; Prev</button>
                            <button type="button" class="btn-step-nav btn-primary" onclick="nextUserStep(3)">Next &gt;</button>
                        </div>
                    </div>

                    <!-- Step 4: Account Information -->
                    <div class="modal-step-pane" id="userStep4" data-step="4">
                        <div class="form-section-title">
                            <i class="fa-solid fa-lock"></i> Account Information
                        </div>
                        <div class="form-grid modal-two-col-grid">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="formUsername">Username *</label>
                                    <input type="text" id="formUsername" name="username" class="form-control" placeholder="user.username" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formEmail">Email Address *</label>
                                    <input type="email" id="formEmail" name="email" class="form-control" placeholder="user@example.com" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group">
                                    <label for="formRole">Role</label>
                                    <input type="text" id="formRole" name="role" class="form-control" value="user" readonly />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>
                            </div>

                            <div class="form-col">
                                <div class="form-group" id="passwordGroup">
                                    <label for="formPassword" id="formPasswordLabel">Password *</label>
                                    <input type="password" id="formPassword" name="password" class="form-control" placeholder="Enter password" />
                                    <div class="input-error-container" aria-live="polite"></div>
                                </div>

                                <div class="form-group" id="confirmPasswordGroup">
                                    <label for="formConfirmPassword">Confirm Password *</label>
                                    <input type="password" id="formConfirmPassword" name="confirm_password" class="form-control" placeholder="Confirm password" />
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
                            </div>
                        </div>

                        <div class="step-nav-footer">
                            <button type="button" class="btn-step-nav btn-secondary" onclick="prevUserStep(4)">&lt; Prev</button>
                            <button type="submit" class="btn-step-nav btn-primary" id="saveUserBtn">Add User</button>
                        </div>
                    </div>
                </div>

                <!-- Footer used only in Edit Mode -->
                <div class="modal-footer modal-footer-edit">
                    <div class="edit-footer-hint">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Account updates are cryptographically secured and logged to audit trails.</span>
                    </div>
                    <div class="edit-footer-actions">
                        <button type="button" class="btn-secondary" id="cancelModalBtn">Cancel</button>
                        <button type="submit" class="btn-primary" id="saveChangesBtn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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

    <script src="../../js/admin/admin.js"></script>
    <script src="../../js/admin/manage_users.js?v=<?= time() ?>"></script>
</body>

</html>