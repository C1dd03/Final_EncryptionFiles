<?php
$managementScope = $managementScope ?? 'admin';
$isSuperAdminManagement = $managementScope === 'superadmin';
?>
<section class="account-management" data-scope="<?= $isSuperAdminManagement ? 'superadmin' : 'admin' ?>">
  <div class="am-heading">
    <div>
      <h2><?= $isSuperAdminManagement ? 'Account Management' : 'User Management' ?></h2>
      <p><?= $isSuperAdminManagement
        ? 'Manage Users, Admins, and queued Super Admins from one page.'
        : 'Authorized Admins can manage User accounts only.' ?></p>
    </div>
    <button type="button" class="am-btn primary" id="amOpenCreate"><i class="fa-solid fa-user-plus"></i> Create Account</button>
  </div>

  <?php if ($isSuperAdminManagement): ?>
    <div class="am-queue-note"><i class="fa-solid fa-rotate"></i> Super Admin handoff policy: only one account is Active. On logout, the oldest eligible blocked Super Admin is activated.</div>
  <?php endif; ?>

  <div class="am-toolbar">
    <input type="search" id="amSearch" placeholder="Search ID Number, name, username, or email" />
    <?php if ($isSuperAdminManagement): ?>
      <select id="amRoleFilter" aria-label="Filter by role">
        <option value="all">All roles</option><option value="user">Users</option>
        <option value="admin">Admins</option><option value="superadmin">Super Admins</option>
      </select>
    <?php endif; ?>
    <select id="amStatusFilter" aria-label="Filter by status">
      <option value="all">All statuses</option><option value="active">Active</option>
      <option value="blocked">Blocked</option><option value="pending_approval">Pending Approval</option>
      <option value="pending_deletion">Pending Deletion</option><option value="inactive">Inactive</option>
    </select>
    <select id="amLimit" aria-label="Rows per page"><option>10</option><option>25</option><option>50</option></select>
  </div>

  <div class="am-table-wrap">
    <table class="am-table">
      <thead><tr><th>ID Number</th><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="amTableBody"><tr><td colspan="6" class="am-empty">Loading accounts...</td></tr></tbody>
    </table>
  </div>
  <div class="am-pagination"><span id="amPageInfo"></span><div id="amPageButtons"></div></div>
</section>

<div class="am-modal" id="amCreateModal" role="dialog" aria-modal="true" aria-labelledby="amCreateTitle">
  <div class="am-card">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amCreateTitle">Create Account</h3>
    <p class="am-subtitle">Only the required account fields are shown. Personal information is completed by the owner in Personal Details.</p>
    <form id="amCreateForm" novalidate>
      <label>ID Number<input name="id_number" maxlength="20" required /><span class="field-error"></span></label>
      <label>Username<input name="username" maxlength="50" required /><span class="field-error"></span></label>
      <label>Role
        <select name="role" id="amCreateRole" <?= $isSuperAdminManagement ? '' : 'disabled' ?>>
          <option value="user">User</option>
          <?php if ($isSuperAdminManagement): ?><option value="admin">Admin</option><option value="superadmin">Super Admin</option><?php endif; ?>
        </select>
        <span class="field-error"></span>
      </label>
      <label>Default Password<input name="default_password" value="@Abcde12345" readonly /></label>
      <div class="am-privileges" id="amCreatePrivileges" hidden>
        <strong>Privileges</strong>
        <?php foreach (User::PRIVILEGES as $key => $label): ?>
          <label class="am-check"><input type="checkbox" name="privileges[]" value="<?= htmlspecialchars($key) ?>" /> <?= htmlspecialchars($label) ?></label>
        <?php endforeach; ?>
      </div>
      <p class="am-message" data-message></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button class="am-btn primary" type="submit">Create Account</button></div>
    </form>
  </div>
</div>

<div class="am-modal" id="amViewModal" role="dialog" aria-modal="true" aria-labelledby="amViewTitle">
  <div class="am-card">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amViewTitle">Account Details</h3>
    <dl class="am-details">
      <div><dt>ID Number</dt><dd data-view="id_number"></dd></div>
      <div><dt>Full Name</dt><dd data-view="full_name"></dd></div>
      <div><dt>Username</dt><dd data-view="username"></dd></div>
      <div><dt>Role</dt><dd data-view="role"></dd></div>
      <div><dt>Account Status</dt><dd data-view="status"></dd></div>
      <div id="amViewPrivilegesRow"><dt>Assigned Privileges</dt><dd data-view="privileges"></dd></div>
    </dl>
    <p class="am-password-note"><i class="fa-solid fa-lock"></i> Passwords are never displayed.</p>
    <div class="am-actions"><button type="button" class="am-btn" data-close>Close</button></div>
  </div>
</div>

<div class="am-modal" id="amEditModal" role="dialog" aria-modal="true" aria-labelledby="amEditTitle">
  <div class="am-card wide">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amEditTitle">Edit Account</h3>
    <form id="amEditForm" novalidate>
      <div class="am-grid">
        <label>ID Number<input name="id_number" readonly /></label>
        <label>Full Name<input name="full_name" placeholder="First Middle Last" /><span class="field-error"></span></label>
        <label>Username<input name="username" required /><span class="field-error"></span></label>
        <label>New Password <small>(optional)</small><input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current" /><span class="field-error"></span></label>
        <label>Role
          <select name="role" id="amEditRole" <?= $isSuperAdminManagement ? '' : 'disabled' ?>>
            <option value="user">User</option><option value="admin">Admin</option><option value="superadmin">Super Admin</option>
          </select>
          <span class="field-error"></span>
        </label>
        <label>Account Status
          <select name="status" id="amEditStatus" <?= $isSuperAdminManagement ? '' : 'disabled' ?>>
            <option value="active">Active</option><option value="blocked">Blocked</option>
            <option value="pending_approval">Pending Approval</option><option value="pending_deletion">Pending Deletion</option><option value="inactive">Inactive</option>
          </select>
          <span class="field-error"></span>
        </label>
      </div>
      <div class="am-privileges" id="amEditPrivileges" hidden>
        <strong>Privileges</strong>
        <?php foreach (User::PRIVILEGES as $key => $label): ?>
          <label class="am-check"><input type="checkbox" name="privileges[]" value="<?= htmlspecialchars($key) ?>" /> <?= htmlspecialchars($label) ?></label>
        <?php endforeach; ?>
      </div>
      <p class="am-message" data-message></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button class="am-btn primary" type="submit">Review Changes</button></div>
    </form>
  </div>
</div>

<div class="am-modal" id="amSecureModal" role="dialog" aria-modal="true" aria-labelledby="amSecureTitle">
  <div class="am-card compact">
    <button class="am-close" type="button" data-close>&times;</button>
    <div id="amConfirmPanel">
      <h3 id="amSecureTitle">Confirm Action</h3><p id="amConfirmText"></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button type="button" class="am-btn primary" id="amContinueSecure">Yes, continue</button></div>
    </div>
    <div id="amPasswordPanel" hidden>
      <h3>Password Verification</h3><p>Enter your own current password to complete this action.</p>
      <label>Current Password<input type="password" id="amOperatorPassword" autocomplete="current-password" /></label>
      <label id="amReasonGroup" hidden>Reason<textarea id="amActionReason" rows="3"></textarea></label>
      <p class="am-message" id="amSecureMessage"></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button type="button" class="am-btn danger" id="amExecuteSecure">Verify &amp; Complete</button></div>
    </div>
  </div>
</div>

<div class="am-toast" id="amToast" role="status" aria-live="polite"></div>
