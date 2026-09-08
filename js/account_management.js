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
  let nextIds = null;
  let idRequestSequence = 0;

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
    create_accounts:"Create User Accounts", block_users:"Block/Unblock Users",
    delete_users:"Delete User Accounts", edit_users:"Edit User Information"
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
    const totalRecords = data.totalRecords || 0;
    const totalPages = data.totalPages || 1;
    const currentPage = data.currentPage || page || 1;
    const limitVal = data.limit || 10;

    const infoEl = document.getElementById("amPageInfo");
    const controlsEl = document.getElementById("amPageButtons");
    if (!infoEl || !controlsEl) return;

    if (totalRecords === 0) {
      infoEl.textContent = "Showing 0 to 0 of 0 entries";
      controlsEl.innerHTML = "";
      return;
    }

    const start = (currentPage - 1) * limitVal + 1;
    const end = Math.min(currentPage * limitVal, totalRecords);
    infoEl.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

    let controlsHtml = "";

    // Previous Button
    const prevDisabled = currentPage <= 1 ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${prevDisabled}" data-page="${currentPage - 1}">Previous</button>`;

    // Page Numbers
    for (let p = 1; p <= totalPages; p++) {
      if (
        p === 1 ||
        p === totalPages ||
        (p >= currentPage - 2 && p <= currentPage + 2)
      ) {
        const activeClass = p === currentPage ? "active" : "";
        controlsHtml += `<button type="button" class="page-link ${activeClass}" data-page="${p}">${p}</button>`;
      } else if (p === currentPage - 3 || p === currentPage + 3) {
        controlsHtml += `<span class="page-link disabled">...</span>`;
      }
    }

    // Next Button
    const nextDisabled = currentPage >= totalPages ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${nextDisabled}" data-page="${currentPage + 1}">Next</button>`;

    controlsEl.innerHTML = controlsHtml;

    // Attach Click Handlers
    controlsEl.querySelectorAll("button.page-link").forEach((btn) => {
      btn.addEventListener("click", function () {
        if (this.classList.contains("disabled") || this.classList.contains("active")) return;
        const targetPage = parseInt(this.getAttribute("data-page"), 10);
        if (targetPage && targetPage > 0 && targetPage <= totalPages) {
          page = targetPage;
          loadAccounts();
        }
      });
    });
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

  function suggestedIdForRole(role) {
    if (!nextIds) return "";
    return role === "user" ? (nextIds.standard_id || "") : (nextIds.admin_id || "");
  }

  function applySuggestedId(force = false) {
    const input = createForm.elements.id_number;
    const suggestion = suggestedIdForRole(createForm.elements.role.value);
    if (!suggestion) return;
    const previousSuggestion = input.dataset.suggestedId || "";
    if (force || input.value.trim() === "" || input.value === previousSuggestion) {
      input.value = suggestion;
      input.dataset.suggestedId = suggestion;
      input.dispatchEvent(new Event("input", {bubbles:true}));
    }
  }

  async function loadLatestIds() {
    const sequence = ++idRequestSequence;
    const input = createForm.elements.id_number;
    input.placeholder = "Loading latest ID...";
    try {
      const data = await request("getNextIds");
      if (sequence !== idRequestSequence || !data.success) return;
      nextIds = data;
      applySuggestedId(false);
    } catch (error) {
      // The administrator can still enter a custom ID when the suggestion cannot load.
    } finally {
      if (sequence === idRequestSequence) input.placeholder = "Enter an ID Number";
    }
  }

  function passwordState(value, optional = false) {
    if (!value && optional) return {score:0, level:"", message:"Leave blank to keep the current password.", valid:true};
    const checks = [value.length >= 8, /[a-z]/.test(value), /[A-Z]/.test(value), /\d/.test(value), /[^a-zA-Z0-9]/.test(value)];
    const score = checks.filter(Boolean).length;
    if (/\s/.test(value)) return {score, level:"weak", message:"Password cannot contain spaces.", valid:false};
    const missing = ["at least 8 characters", "a lowercase letter", "an uppercase letter", "a number", "a special character"].filter((_, index) => !checks[index]);
    if (score === 5) return {score, level:"strong", message:"Strong password.", valid:true};
    if (!value) return {score, level:"", message:"Enter a password.", valid:false};
    return {score, level:score >= 4 ? "medium" : "weak", message:`Missing: ${missing.join(", ")}.`, valid:false};
  }

  function updatePasswordMeter(input, optional = false) {
    const meter = document.querySelector(`[data-password-meter="${input.id}"]`);
    const feedback = document.querySelector(`[data-password-feedback="${input.id}"]`);
    const state = passwordState(input.value, optional);
    if (meter) {
      meter.className = `am-password-meter ${state.level}`.trim();
      meter.querySelector("span").style.width = `${state.score * 20}%`;
    }
    if (feedback) {
      feedback.className = `am-password-feedback ${state.level}`.trim();
      feedback.textContent = state.message;
    }
    return state.valid;
  }

  const createPassword = document.getElementById("amCreatePassword");
  const editPassword = document.getElementById("amEditPassword");
  createPassword?.addEventListener("input", () => updatePasswordMeter(createPassword));
  editPassword?.addEventListener("input", () => updatePasswordMeter(editPassword, true));

  const createValidator = window.SharedValidator
    ? window.SharedValidator.attachRealtimeValidation(createForm, {
        ajaxCheckUrl: "../auth/index.php",
        ignoreFields: ["password"],
      })
    : null;

  let currentEditDetail = null;
  const editValidator = window.SharedValidator
    ? window.SharedValidator.attachRealtimeValidation(editForm, {
        getInitialData: () => currentEditDetail || {},
        ajaxCheckUrl: "../auth/index.php",
        ignoreFields: ["password"],
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
        input.dispatchEvent(new Event("input", {bubbles:true}));
      }
    });
  }





  document.getElementById("amOpenCreate")?.addEventListener("click", () => {
    createForm.reset();
    createForm.elements.id_number.value = "";
    createForm.elements.id_number.dataset.suggestedId = "";
    if (createForm.elements.default_password) {
      createForm.elements.default_password.value = "@Abcde12345";
    }
    const passInput = document.getElementById("amCreatePassword") || createForm.elements.password;
    if (passInput) passInput.value = "@Abcde12345";
    if (!isSuperAdmin) createForm.elements.role.value = "user";
    if (createValidator) createValidator.clearAll();
    formMessage(createForm);
    setPrivilegeVisibility(document.getElementById("amCreateRole"), document.getElementById("amCreatePrivileges"));
    if (passInput) updatePasswordMeter(passInput);
    open(createModal);
    loadLatestIds();
  });
  document.getElementById("amCreateRole").addEventListener("change", (event) => {
    setPrivilegeVisibility(event.target, document.getElementById("amCreatePrivileges"));
    applySuggestedId(false);
  });
  document.getElementById("amEditRole").addEventListener("change", (event) => setPrivilegeVisibility(event.target, document.getElementById("amEditPrivileges")));

  createForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    formMessage(createForm);
    if (!updatePasswordMeter(createPassword) || (createValidator && !createValidator.validateAll())) {
      return formMessage(createForm, "Please correct the highlighted fields.");
    }
    const dataForm = new FormData(createForm);
    if (!isSuperAdmin) dataForm.set("role", "user");
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
      if (data.role === "superadmin") {
        document.getElementById("amCredUsername").textContent = dataForm.get("username") || "-";
        document.getElementById("amCredPassword").textContent = dataForm.get("password") || "@Abcde12345";
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
        ["id_number", "first_name", "middle_name", "last_name", "email", "address", "username", "role", "status"].forEach((key) => {
          const el = viewModal.querySelector(`[data-view="${key}"]`);
          if (el) el.textContent = detail[key] || "-";
        });
        open(viewModal);
      } else if (button.dataset.action === "edit") {
        const detail = await getDetail(row.id_number);
        currentEditDetail = detail;
        editForm.elements.id_number.value = detail.id_number;
        if (editForm.elements.first_name) editForm.elements.first_name.value = detail.first_name || "";
        if (editForm.elements.middle_name) editForm.elements.middle_name.value = detail.middle_name || "";
        if (editForm.elements.last_name) editForm.elements.last_name.value = detail.last_name || "";
        if (editForm.elements.email) editForm.elements.email.value = detail.email || "";
        if (editForm.elements.address) editForm.elements.address.value = detail.address || "";
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
        updatePasswordMeter(editPassword, true);
        formMessage(editForm); open(editModal);
      } else {
        beginSecureAction(button.dataset.action, row);
      }
    } catch (error) { toast(error.message, true); }
  });

  editForm.addEventListener("submit", (event) => {
    event.preventDefault();
    formMessage(editForm);
    if (!updatePasswordMeter(editPassword, true) || (editValidator && !editValidator.validateAll())) {
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
      : "Are you sure you want to permanently delete this account? This cannot be undone.";
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

  const credentialsModal = document.getElementById("amCredentialsModal");
  document.querySelectorAll("[data-close]").forEach((button) => button.addEventListener("click", () => close(button.closest(".am-modal"))));
  [createModal, viewModal, editModal, secureModal, credentialsModal].forEach((modal) => { if (modal) modal.addEventListener("click", (event) => { if (event.target === modal) close(modal); }); });
  search.addEventListener("input", () => { clearTimeout(debounce); debounce = setTimeout(() => { page = 1; loadAccounts(); }, 300); });
  statusFilter.addEventListener("change", () => { page = 1; loadAccounts(); });
  if (roleFilter) roleFilter.addEventListener("change", () => { page = 1; loadAccounts(); });
  limit.addEventListener("change", () => { page = 1; loadAccounts(); });
  loadAccounts();
});
