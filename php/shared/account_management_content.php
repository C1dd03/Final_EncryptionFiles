<?php
$managementScope = $managementScope ?? 'admin';
$isSuperAdminManagement = $managementScope === 'superadmin';
$canCreateAccounts = $canCreateAccounts ?? $isSuperAdminManagement;
?>
<section class="account-management" data-scope="<?= $isSuperAdminManagement ? 'superadmin' : 'admin' ?>">
  <div class="am-heading">
    <div>
      <h2><?= $isSuperAdminManagement ? 'Account Management' : 'User Management' ?></h2>
      <p><?= $isSuperAdminManagement
        ? 'Manage Users, Admins, and queued Super Admins from one page.'
        : 'Authorized Admins can manage User accounts only.' ?></p>
    </div>
    <?php if ($canCreateAccounts): ?>
      <button type="button" class="am-btn primary" id="amOpenCreate"><i class="fa-solid fa-user-plus"></i> Create Account</button>
    <?php endif; ?>
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
      <label>ID Number<input name="id_number" maxlength="20" autocomplete="off" required /><small class="am-field-hint">Latest available ID is filled automatically. You can edit it.</small><span class="field-error"></span></label>
      <label>Username<input name="username" maxlength="50" required /><span class="field-error"></span></label>
      <label>Role
        <select name="role" id="amCreateRole" <?= $isSuperAdminManagement ? '' : 'disabled' ?>>
          <option value="user">User</option>
          <?php if ($isSuperAdminManagement): ?><option value="admin">Admin</option><option value="superadmin">Super Admin</option><?php endif; ?>
        </select>
        <span class="field-error"></span>
      </label>
      <label>Initial Password
        <input type="hidden" name="default_password" value="@Abcde12345" />
        <div style="display: flex; gap: 8px; align-items: center;">
          <div class="am-password-input-stack">
            <div style="position: relative; display: flex; align-items: center;">
              <input type="password" name="password" id="amCreatePassword" value="@Abcde12345" autocomplete="new-password" style="width: 100%; padding-right: 38px;" />
              <i class="fas fa-eye-slash toggle-password" id="amToggleCreatePassword" style="position: absolute; right: 12px; cursor: pointer; color: #64748b;" title="Toggle Password"></i>
            </div>
            <div class="am-password-meter" data-password-meter="amCreatePassword" aria-hidden="true"><span></span></div>
          </div>
          <button type="button" class="am-btn" id="amGeneratePasswordBtn" title="Generate password" style="white-space: nowrap; font-size: 11px; padding: 7px 10px;">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate
          </button>
        </div>
        <small class="am-password-feedback" data-password-feedback="amCreatePassword" aria-live="polite"></small>
        <small style="color: #64748b; font-size: 11px;">Default is @Abcde12345. You can enter a password or click Generate.</small>
        <span class="field-error"></span>
      </label>

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
      <div><dt>First Name</dt><dd data-view="first_name"></dd></div>
      <div><dt>Middle Name</dt><dd data-view="middle_name"></dd></div>
      <div><dt>Last Name</dt><dd data-view="last_name"></dd></div>
      <div><dt>Gmail</dt><dd data-view="email"></dd></div>
      <div><dt>Address</dt><dd data-view="address"></dd></div>
      <div><dt>Username</dt><dd data-view="username"></dd></div>
      <div><dt>Role</dt><dd data-view="role"></dd></div>
      <div><dt>Account Status</dt><dd data-view="status"></dd></div>
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
        <label>Username<input name="username" required /><span class="field-error"></span></label>
        <label>First Name<input name="first_name" placeholder="First Name" required /><span class="field-error"></span></label>
        <label>Middle Name<input name="middle_name" placeholder="Middle Name (optional)" /><span class="field-error"></span></label>
        <label>Last Name<input name="last_name" placeholder="Last Name" required /><span class="field-error"></span></label>
        <label>Gmail<input type="email" name="email" placeholder="example@gmail.com" /><span class="field-error"></span></label>
        <label style="grid-column: 1 / -1;">Address<input name="address" placeholder="Purok/Street, Barangay, City, Province, Country, Zip Code" /><span class="field-error"></span></label>
        <label>New Password <small>(optional)</small><input type="password" name="password" id="amEditPassword" autocomplete="new-password" placeholder="Leave blank to keep current" /><div class="am-password-meter" data-password-meter="amEditPassword" aria-hidden="true"><span></span></div><small class="am-password-feedback" data-password-feedback="amEditPassword" aria-live="polite"></small><span class="field-error"></span></label>
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

<div class="am-modal" id="amCredentialsModal" role="dialog" aria-modal="true" aria-labelledby="amCredTitle">
  <div class="am-card" style="max-width: 420px; text-align: center;">
    <div style="width: 50px; height: 50px; border-radius: 50%; background: #e8f3e5; color: #477246; display: grid; place-items: center; margin: 0 auto 12px; font-size: 22px;">
      <i class="fa-solid fa-shield-check"></i>
    </div>
    <h3 id="amCredTitle">Super Admin Account Created</h3>
    <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">
      Share these initial credentials with the new Super Admin. They must log in and change their password to activate the account.
    </p>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: left; margin-bottom: 16px; font-size: 13px;">
      <div style="margin-bottom: 6px;"><strong>Username:</strong> <span id="amCredUsername">-</span></div>
      <div><strong>Initial Password:</strong> <span id="amCredPassword">-</span></div>
    </div>
    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; font-size: 12px; color: #991b1b; margin-bottom: 16px;">
      <i class="fa-solid fa-circle-exclamation"></i> Notice: Once you log out, your account will be blocked and control will transfer to this new Super Admin.
    </div>
    <button type="button" class="am-btn primary" style="width: 100%;" data-close>Got it</button>
  </div>
</div>

<div class="am-toast" id="amToast" role="status" aria-live="polite"></div>
