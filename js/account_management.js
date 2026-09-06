document.addEventListener("DOMContentLoaded", () => {
  const root = document.querySelector(".account-management");
  if (!root) return;
  const scope = root.dataset.scope;
  const isSuperAdmin = scope === "superadmin";
  const body = document.getElementById("amTableBody");
  const search = document.getElementById("amSearch");
  const roleFilter = document.getElementById("amRoleFilter");
  const statusFilter = document.getElementById("amStatusFilter");
  const limit = document.getElementById("amLimit");
  const createModal = document.getElementById("amCreateModal");
  const viewModal = document.getElementById("amViewModal");
  const editModal = document.getElementById("amEditModal");
  const secureModal = document.getElementById("amSecureModal");
  const createForm = document.getElementById("amCreateForm");
  const editForm = document.getElementById("amEditForm");
  let page = 1;
  let records = [];
  let secureAction = null;
  let debounce;

  const escapeHtml = (value) => String(value ?? "").replace(/[&<>'"]/g, (char) => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;",'"':"&quot;"})[char]);
  const open = (modal) => modal.classList.add("active");
  const close = (modal) => modal.classList.remove("active");
  const toast = (message, error = false) => {
    const node = document.getElementById("amToast");
    node.textContent = message;
    node.classList.toggle("error", error);
    node.classList.add("active");
    setTimeout(() => node.classList.remove("active"), 3500);
  };
  const request = async (action, options = {}) => {
    const response = await fetch(`../../php/auth/index.php?action=${action}`, {credentials:"same-origin", ...options});
    const data = await response.json();
    if (data.sessionExpired) window.location.href = "../auth/index.php?action=login";
    if (data.passwordChangeRequired) window.location.href = "../auth/index.php?action=login&force_password_change=1";
    return data;
  };
  const formMessage = (form, message = "") => {
    const node = form.querySelector("[data-message]");
    if (node) node.textContent = message;
  };
  const privilegeLabels = {
    approve_registrations:"Approve/Reject Registrations", view_user_logs:"View User Activity Logs",
    view_admin_logs:"View Admin Activity Logs", view_users:"View All User Accounts",
    block_users:"Block/Unblock Users", delete_users:"Delete User Accounts", edit_users:"Edit User Information"
  };

  async function loadAccounts() {
    const params = new URLSearchParams({
      search: search.value.trim(), status: statusFilter.value,
      role: roleFilter ? roleFilter.value : "user", page, limit: limit.value,
    });
    body.innerHTML = '<tr><td colspan="6" class="am-empty">Loading accounts...</td></tr>';
    try {
      const data = await request(`getManagedAccounts&${params.toString()}`);
      if (!data.success) throw new Error(data.message || "Unable to load accounts.");
      records = data.data || [];
      renderRows();
      renderPagination(data);
    } catch (error) {
      body.innerHTML = `<tr><td colspan="6" class="am-empty">${escapeHtml(error.message)}</td></tr>`;
    }
  }

  function renderRows() {
    if (!records.length) {
      body.innerHTML = '<tr><td colspan="6" class="am-empty">No accounts matched the selected filters.</td></tr>';
      return;
    }
    body.innerHTML = records.map((row) => {
      const blocked = row.status === "blocked";
      return `<tr>
        <td><strong>${escapeHtml(row.id_number)}</strong>${Number(row.must_change_password) ? '<br><small>Must change password</small>' : ''}</td>
        <td>${escapeHtml(row.name)}</td><td>${escapeHtml(row.username)}</td>
        <td><span class="am-badge ${escapeHtml(row.role)}">${escapeHtml(row.role === "superadmin" ? "Super Admin" : row.role)}</span></td>
        <td><span class="am-badge ${escapeHtml(row.status)}">${escapeHtml(row.status.replaceAll("_", " "))}</span></td>
        <td><div class="action-dropdown">
          <button type="button" class="action-dropdown-btn" aria-expanded="false" aria-label="Options for ${escapeHtml(row.id_number)}">
            Options <i class="fa-solid fa-chevron-down dropdown-chevron" aria-hidden="true"></i>
          </button>
          <div class="action-dropdown-menu">
            <button type="button" class="action-menu-item view" data-action="view" data-id="${escapeHtml(row.id_number)}"><i class="fa-solid fa-eye" aria-hidden="true"></i> View Details</button>
            <div class="action-menu-divider"></div>
            <button type="button" class="action-menu-item edit" data-action="edit" data-id="${escapeHtml(row.id_number)}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Account</button>
            ${row.status !== "inactive" ? `<button type="button" class="action-menu-item ${blocked ? "unblock" : "block"}" data-action="${blocked ? "unblock" : "block"}" data-id="${escapeHtml(row.id_number)}"><i class="fa-solid fa-${blocked ? "unlock" : "ban"}" aria-hidden="true"></i> ${blocked ? "Unblock" : "Block"} Account</button>` : ""}
            ${row.status !== "inactive" ? `<button type="button" class="action-menu-item delete" data-action="delete" data-id="${escapeHtml(row.id_number)}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete Account</button>` : ""}
          </div>
        </div></td></tr>`;
    }).join("");
  }

  function renderPagination(data) {
    const start = data.totalRecords ? (data.currentPage - 1) * data.limit + 1 : 0;
    document.getElementById("amPageInfo").textContent = `Showing ${start}-${Math.min(data.currentPage * data.limit, data.totalRecords)} of ${data.totalRecords}`;
    const controls = document.getElementById("amPageButtons");
    controls.innerHTML = "";
    for (let number = 1; number <= data.totalPages; number += 1) {
      if (number !== 1 && number !== data.totalPages && Math.abs(number - data.currentPage) > 1) continue;
      const button = document.createElement("button");
      button.className = `am-btn${number === data.currentPage ? " primary" : ""}`;
      button.textContent = number;
      button.addEventListener("click", () => { page = number; loadAccounts(); });
      controls.appendChild(button);
    }
  }

  async function getDetail(id) {
    const data = await request(`getManagedAccountDetail&id_number=${encodeURIComponent(id)}`);
    if (!data.success) throw new Error(data.message || "Unable to load account details.");
    return data.data;
  }

  function setPrivilegeVisibility(roleSelect, container) {
    const visible = isSuperAdmin && ["admin", "superadmin"].includes(roleSelect.value);
    container.hidden = !visible;
    if (!visible) container.querySelectorAll('input[type="checkbox"]').forEach((input) => { input.checked = false; });
  }

  const createValidator = window.SharedValidator
    ? window.SharedValidator.attachRealtimeValidation(createForm, {
        ajaxCheckUrl: "../auth/index.php",
      })
    : null;

  let currentEditDetail = null;
  const editValidator = window.SharedValidator
    ? window.SharedValidator.attachRealtimeValidation(editForm, {
        getInitialData: () => currentEditDetail || {},
        ajaxCheckUrl: "../auth/index.php",
      })
    : null;

  const togglePassBtn = document.getElementById("amToggleCreatePassword");
  if (togglePassBtn) {
    togglePassBtn.addEventListener("click", () => {
      const input = document.getElementById("amCreatePassword");
      if (!input) return;
      const isPass = input.type === "password";
      input.type = isPass ? "text" : "password";
      togglePassBtn.classList.toggle("fa-eye", isPass);
      togglePassBtn.classList.toggle("fa-eye-slash", !isPass);
    });
  }

  const genPasswordBtn = document.getElementById("amGeneratePasswordBtn");
  if (genPasswordBtn) {
    genPasswordBtn.addEventListener("click", () => {
      const randNums = Math.floor(10000 + Math.random() * 90000);
      const generated = "@Abcde" + randNums;
      const input = document.getElementById("amCreatePassword");
      if (input) {
        input.value = generated;
        input.type = "text";
        if (togglePassBtn) {
          togglePassBtn.classList.remove("fa-eye-slash");
          togglePassBtn.classList.add("fa-eye");
        }
      }
    });
  }

  const genPasscodeBtn = document.getElementById("amGeneratePasscodeBtn");
  if (genPasscodeBtn) {
    genPasscodeBtn.addEventListener("click", () => {
      const code = String(Math.floor(100000 + Math.random() * 900000));
      const input = document.getElementById("amCreatePasscode");
      if (input) input.value = code;
    });
  }

  function updatePasscodeVisibility() {
    const roleSelect = document.getElementById("amCreateRole");
    const group = document.getElementById("amPasscodeGroup");
    if (!group || !roleSelect) return;
    const isSuper = isSuperAdmin && roleSelect.value === "superadmin";
    group.style.display = isSuper ? "block" : "none";
    const passInput = document.getElementById("amCreatePasscode");
    if (isSuper && passInput && !passInput.value) {
      passInput.value = String(Math.floor(100000 + Math.random() * 900000));
    }
  }

  document.getElementById("amOpenCreate").addEventListener("click", () => {
    createForm.reset();
    if (createForm.elements.default_password) {
      createForm.elements.default_password.value = "@Abcde12345";
    }
    const passInput = document.getElementById("amCreatePassword") || createForm.elements.password;
    if (passInput) passInput.value = "@Abcde12345";
    if (!isSuperAdmin) createForm.elements.role.value = "user";
    if (createValidator) createValidator.clearAll();
    formMessage(createForm);
    setPrivilegeVisibility(document.getElementById("amCreateRole"), document.getElementById("amCreatePrivileges"));
    updatePasscodeVisibility();
    open(createModal);
  });
  document.getElementById("amCreateRole").addEventListener("change", (event) => {
    setPrivilegeVisibility(event.target, document.getElementById("amCreatePrivileges"));
    updatePasscodeVisibility();
  });
  document.getElementById("amEditRole").addEventListener("change", (event) => setPrivilegeVisibility(event.target, document.getElementById("amEditPrivileges")));

  createForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    formMessage(createForm);
    if (createValidator && !createValidator.validateAll()) {
      return formMessage(createForm, "Please correct the highlighted fields.");
    }
    const dataForm = new FormData(createForm);
    if (!isSuperAdmin) dataForm.set("role", "user");
    if (dataForm.get("role") === "superadmin") {
      const code = (dataForm.get("passcode") || "").trim();
      if (!/^\d{6}$/.test(code)) {
        return formMessage(createForm, "Please enter or generate a 6-digit One-Time Passcode.");
      }
    }
    const submit = createForm.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const data = await request("createManagedAccount", {method:"POST", body:dataForm});
      if (!data.success) {
        if (data.fieldErrors && typeof data.fieldErrors === "object") {
          Object.entries(data.fieldErrors).forEach(([field, msg]) => {
            if (createValidator) createValidator.setFieldError(field, msg);
          });
        }
        return formMessage(createForm, data.message || "Unable to create account.");
      }
      close(createModal);
      if (data.passcode && data.role === "superadmin") {
        document.getElementById("amCredUsername").textContent = dataForm.get("username") || "-";
        document.getElementById("amCredPassword").textContent = dataForm.get("password") || "@Abcde12345";
        document.getElementById("amCredPasscode").textContent = data.passcode;
        open(document.getElementById("amCredentialsModal"));
      } else {
        toast(data.message);
      }
      loadAccounts();
    } catch (error) { formMessage(createForm, "Unable to connect. Please try again."); }
    finally { submit.disabled = false; }
  });

  body.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-action]");
    if (!button) return;
    const row = records.find((item) => item.id_number === button.dataset.id);
    if (!row) return;
    try {
      if (button.dataset.action === "view") {
        const detail = await getDetail(row.id_number);
        ["id_number", "full_name", "username", "role", "status"].forEach((key) => {
          viewModal.querySelector(`[data-view="${key}"]`).textContent = detail[key] || "-";
        });
        viewModal.querySelector('[data-view="privileges"]').textContent = (detail.privileges || []).map((key) => privilegeLabels[key] || key).join(", ") || "None";
        document.getElementById("amViewPrivilegesRow").hidden = !["admin", "superadmin"].includes(detail.role);
        open(viewModal);
      } else if (button.dataset.action === "edit") {
        const detail = await getDetail(row.id_number);
        currentEditDetail = detail;
        editForm.elements.id_number.value = detail.id_number;
        editForm.elements.full_name.value = detail.full_name || "";
        editForm.elements.username.value = detail.username;
        editForm.elements.password.value = "";
        editForm.elements.role.value = detail.role;
        editForm.elements.status.value = detail.status;
        editForm.querySelectorAll('input[name="privileges[]"]').forEach((input) => { input.checked = (detail.privileges || []).includes(input.value); });
        setPrivilegeVisibility(document.getElementById("amEditRole"), document.getElementById("amEditPrivileges"));
        editForm.dataset.originalRole = detail.role;
        editForm.dataset.originalStatus = detail.status;
        editForm.dataset.originalPrivileges = JSON.stringify([...(detail.privileges || [])].sort());
        if (editValidator) editValidator.clearAll();
        formMessage(editForm); open(editModal);
      } else {
        beginSecureAction(button.dataset.action, row);
      }
    } catch (error) { toast(error.message, true); }
  });

  editForm.addEventListener("submit", (event) => {
    event.preventDefault();
    formMessage(editForm);
    if (editValidator && !editValidator.validateAll()) {
      return formMessage(editForm, "Please correct the highlighted fields.");
    }
    const roleChanged = editForm.elements.role.value !== editForm.dataset.originalRole;
    const statusChanged = editForm.elements.status.value !== editForm.dataset.originalStatus;
    let kind = "edit";
    if (roleChanged) kind = "change this account's role";
    else if (isSuperAdmin && JSON.stringify([...editForm.querySelectorAll('input[name="privileges[]"]:checked')].map((input) => input.value).sort()) !== editForm.dataset.originalPrivileges) kind = "change this account's privileges";
    else if (statusChanged) kind = `change this account's status`;
    secureAction = {type:"edit", form:new FormData(editForm), needsReason:editForm.elements.status.value === "blocked" && editForm.dataset.originalStatus !== "blocked"};
    showSecureConfirmation(`Are you sure you want to ${kind}?`);
  });

  function beginSecureAction(type, row) {
    secureAction = {type, row, needsReason:["block", "delete"].includes(type)};
    const message = type === "block" ? "Are you sure you want to block this account?"
      : type === "unblock" ? "Are you sure you want to unblock this account?"
      : "Are you sure you want to delete this account?";
    showSecureConfirmation(`${message} ${row.username} (${row.id_number})`);
  }
  function showSecureConfirmation(message) {
    document.getElementById("amConfirmText").textContent = message;
    document.getElementById("amConfirmPanel").hidden = false;
    document.getElementById("amPasswordPanel").hidden = true;
    document.getElementById("amOperatorPassword").value = "";
    document.getElementById("amActionReason").value = "";
    document.getElementById("amSecureMessage").textContent = "";
    open(secureModal);
  }
  document.getElementById("amContinueSecure").addEventListener("click", () => {
    document.getElementById("amConfirmPanel").hidden = true;
    document.getElementById("amPasswordPanel").hidden = false;
    document.getElementById("amReasonGroup").hidden = !secureAction.needsReason;
    document.getElementById("amOperatorPassword").focus();
  });
  document.getElementById("amExecuteSecure").addEventListener("click", async () => {
    const password = document.getElementById("amOperatorPassword").value;
    const reason = document.getElementById("amActionReason").value.trim();
    const message = document.getElementById("amSecureMessage");
    if (!password) { message.textContent = "Your current password is required."; return; }
    if (secureAction.needsReason && !reason) { message.textContent = "A reason is required for this action."; return; }
    let action;
    let payload;
    if (secureAction.type === "edit") {
      action = "updateManagedAccount"; payload = secureAction.form;
    } else if (secureAction.type === "delete") {
      action = "deleteManagedAccount"; payload = new FormData(); payload.set("id_number", secureAction.row.id_number);
    } else {
      action = "setManagedAccountStatus"; payload = new FormData();
      payload.set("id_number", secureAction.row.id_number);
      payload.set("status", secureAction.type === "block" ? "blocked" : "active");
    }
    payload.set("operator_password", password);
    if (reason) payload.set("reason", reason);
    const button = document.getElementById("amExecuteSecure"); button.disabled = true;
    try {
      const data = await request(action, {method:"POST", body:payload});
      if (!data.success) {
        if (secureAction.type === "edit" && data.fieldErrors && typeof data.fieldErrors === "object") {
          close(secureModal);
          Object.entries(data.fieldErrors).forEach(([field, msg]) => {
            if (editValidator) editValidator.setFieldError(field, msg);
          });
          formMessage(editForm, data.message || "Please correct the highlighted fields.");
          return;
        }
        message.textContent = data.message || "The action could not be completed.";
        return;
      }
      close(secureModal); close(editModal); toast(data.message); loadAccounts();
    } catch (error) { message.textContent = "Unable to connect. Please try again."; }
    finally { button.disabled = false; }
  });

  document.querySelectorAll("[data-close]").forEach((button) => button.addEventListener("click", () => close(button.closest(".am-modal"))));
  [createModal, viewModal, editModal, secureModal].forEach((modal) => modal.addEventListener("click", (event) => { if (event.target === modal) close(modal); }));
  search.addEventListener("input", () => { clearTimeout(debounce); debounce = setTimeout(() => { page = 1; loadAccounts(); }, 300); });
  statusFilter.addEventListener("change", () => { page = 1; loadAccounts(); });
  if (roleFilter) roleFilter.addEventListener("change", () => { page = 1; loadAccounts(); });
  limit.addEventListener("change", () => { page = 1; loadAccounts(); });
  loadAccounts();
});
