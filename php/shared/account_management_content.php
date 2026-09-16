<?php
$managementScope = $managementScope ?? 'admin';
$isSuperAdminManagement = $managementScope === 'superadmin';
$canCreateAccounts = $canCreateAccounts ?? $isSuperAdminManagement;
?>
<section class="account-management" data-scope="<?= $isSuperAdminManagement ? 'superadmin' : 'admin' ?>">
  <div class="am-heading">
    <div>
      <h2>Account Management</h2>
      <p><?= $isSuperAdminManagement
            ? 'Manage Users, Admins, and queued Super Admins from one page.'
            : 'View User and Admin accounts. Available actions depend on your permissions.' ?></p>
    </div>
    <?php if ($canCreateAccounts): ?>
      <button type="button" class="am-btn primary" id="amOpenCreate"><i class="fa-solid fa-user-plus"></i> Create Account</button>
    <?php endif; ?>
  </div>

  <?php if ($isSuperAdminManagement): ?>
    <div class="am-queue-note"><i class="fa-solid fa-rotate"></i> Only one Super Admin can be Active. Invited Super Admins complete setup first and remain Inactive until selected for activation. Promote an Admin or select Active for an inactive Super Admin to choose your successor. With a selected successor, logout transfers Active status. Without a successor, your account stays Active.</div>
  <?php endif; ?>

  <div class="am-toolbar">
    <input type="search" id="amSearch" placeholder="Search ID Number, name, username, or email" />
      <select id="amRoleFilter" aria-label="Filter by role">
        <option value="all">All roles</option>
        <option value="user">Users</option>
        <option value="admin">Admins</option>
        <?php if ($isSuperAdminManagement): ?><option value="superadmin">Super Admins</option><?php endif; ?>
      </select>
    <select id="amStatusFilter" aria-label="Filter by status">
      <option value="all">All statuses</option>
      <option value="active">Active</option>
      <option value="blocked">Blocked</option>
      <option value="pending_approval">Pending Approval</option>
      <option value="pending_deletion">Pending Deletion</option>
      <option value="inactive">Inactive</option>
    </select>
    <select id="amLimit" aria-label="Rows per page">
      <option>10</option>
      <option>25</option>
      <option>50</option>
    </select>
  </div>

  <div class="am-table-wrap">
    <table class="am-table">
      <thead>
        <tr>
          <th>ID Number</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="amTableBody">
        <tr>
          <td colspan="6" class="am-empty">Loading accounts...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="am-pagination"><span id="amPageInfo"></span>
    <div id="amPageButtons"></div>
  </div>
</section>

<div class="am-modal" id="amCreateModal" role="dialog" aria-modal="true" aria-labelledby="amCreateTitle">
  <div class="am-card">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amCreateTitle">Create Account</h3>
    <p class="am-subtitle">Send an invitation by email. The account stays in Pending Approvals until the recipient completes setup.</p>
    <form id="amCreateForm" novalidate>
      <label>ID Number<input name="id_number" maxlength="20" autocomplete="off" readonly required /><small class="am-field-hint">The next available ID is generated automatically.</small><span class="field-error"></span></label>
      <label>Recipient Email<input type="email" name="email" maxlength="150" required /><span class="field-error"></span></label>
      <label>Username<input name="username" maxlength="50" required /><span class="field-error"></span></label>
      <label>Role
        <select name="role" id="amCreateRole">
          <option value="user">User</option>
          <option value="admin">Admin</option>
          <?php if ($isSuperAdminManagement): ?>
            <option value="superadmin">Super Admin</option><?php endif; ?>
        </select>
        <span class="field-error"></span>
      </label>
      <p class="am-subtitle">A secure temporary password is generated automatically and emailed to the recipient. The invitation expires in 7 days.</p>

      <div class="am-privileges" id="amCreatePrivileges" hidden>
        <strong>Privileges</strong>
        <?php foreach (User::PRIVILEGES as $key => $label): ?>
          <label class="am-check"><input type="checkbox" name="privileges[]" value="<?= htmlspecialchars($key) ?>" /> <?= htmlspecialchars($label) ?></label>
        <?php endforeach; ?>
      </div>
      <p class="am-message" data-message></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button class="am-btn primary" type="submit">Send Invitation</button></div>
    </form>
  </div>
</div>

<div class="am-modal" id="amViewModal" role="dialog" aria-modal="true" aria-labelledby="amViewTitle">
  <div class="am-card">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amViewTitle">Account Details</h3>
    <dl class="am-details">
      <div>
        <dt>ID Number</dt>
        <dd data-view="id_number"></dd>
      </div>
      <div>
        <dt>First Name</dt>
        <dd data-view="first_name"></dd>
      </div>
      <div>
        <dt>Middle Name</dt>
        <dd data-view="middle_name"></dd>
      </div>
      <div>
        <dt>Last Name</dt>
        <dd data-view="last_name"></dd>
      </div>
      <div>
        <dt>Gmail</dt>
        <dd data-view="email"></dd>
      </div>
      <div>
        <dt>Address</dt>
        <dd data-view="address"></dd>
      </div>
      <div>
        <dt>Username</dt>
        <dd data-view="username"></dd>
      </div>
      <div>
        <dt>Role</dt>
        <dd data-view="role"></dd>
      </div>
      <div>
        <dt>Account Status</dt>
        <dd data-view="status"></dd>
      </div>
    </dl>
    <p class="am-password-note"><i class="fa-solid fa-lock"></i> Passwords are never displayed.</p>
    <div class="am-actions"><button type="button" class="am-btn" data-close>Close</button></div>
  </div>
</div>

<div class="am-modal" id="amEditModal" role="dialog" aria-modal="true" aria-labelledby="amEditTitle">
  <div class="am-card wide">
    <button class="am-close" type="button" data-close>&times;</button>
    <h3 id="amEditTitle">Edit Account</h3>
    <p class="am-subtitle">Keep Password blank to retain the existing password. An assigned replacement must be changed at the next login.</p>
    <form id="amEditForm" novalidate>
      <div class="am-grid">
        <label>ID Number<input name="id_number" readonly /></label>
        <label>Name<input name="full_name" readonly /></label>
        <label>Email<input type="email" name="email" readonly /></label>
        <label>Username<input name="username" required /><span class="field-error"></span></label>
        <label>Password <small>(optional)</small><input type="password" name="password" id="amEditPassword" autocomplete="new-password" placeholder="Leave blank to keep existing password" />
          <div class="am-password-meter" data-password-meter="amEditPassword" aria-hidden="true"><span></span></div><small class="am-password-feedback" data-password-feedback="amEditPassword" aria-live="polite"></small><span class="field-error"></span>
        </label>
        <label>Role
          <select name="role" id="amEditRole">
            <option value="user">User</option>
            <option value="admin">Admin</option>
            <?php if ($isSuperAdminManagement): ?><option value="superadmin">Super Admin</option><?php endif; ?>
          </select>
          <span class="field-error"></span>
        </label>
        <label>Account Status
          <select name="status" id="amEditStatus">
            <option value="" disabled hidden>Keep current status</option>
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
            <option value="inactive">Inactive</option>
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
      <h3 id="amSecureTitle">Confirm Action</h3>
      <p id="amConfirmText"></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button type="button" class="am-btn primary" id="amContinueSecure">Yes, continue</button></div>
    </div>
    <div id="amPasswordPanel" hidden>
      <h3>Password Verification</h3>
      <p>Enter your own current password to complete this action.</p>
      <label>Current Password<input type="password" id="amOperatorPassword" autocomplete="current-password" /></label>
      <label id="amReasonGroup" hidden>Reason<textarea id="amActionReason" rows="3"></textarea></label>
      <p class="am-message" id="amSecureMessage"></p>
      <div class="am-actions"><button type="button" class="am-btn" data-close>Cancel</button><button type="button" class="am-btn danger" id="amExecuteSecure">Verify &amp; Complete</button></div>
    </div>
  </div>
</div>

<div class="am-toast" id="amToast" role="status" aria-live="polite"></div>
