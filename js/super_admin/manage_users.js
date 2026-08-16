document.addEventListener("DOMContentLoaded", function () {
  let searchTimeout = null;

  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");
  const controlForm = document.getElementById("controlForm");

  const userModal = document.getElementById("userModal");
  const userModalTitle = document.getElementById("userModalTitle");
  const userForm = document.getElementById("userForm");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const cancelModalBtn = document.getElementById("cancelModalBtn");
  const openAddUserBtn = document.getElementById("openAddUserBtn");

  const viewModal = document.getElementById("viewModal");
  const closeViewModalBtn = document.getElementById("closeViewModalBtn");
  const closeViewModalFooterBtn = document.getElementById("closeViewModalFooterBtn");

  const confirmModal = document.getElementById("confirmModal");
  const confirmModalTitle = document.getElementById("confirmModalTitle");
  const confirmModalMessage = document.getElementById("confirmModalMessage");
  const confirmModalBtn = document.getElementById("confirmModalBtn");
  const cancelConfirmModalBtn = document.getElementById("cancelConfirmModalBtn");
  const closeConfirmModalBtn = document.getElementById("closeConfirmModalBtn");

  let activeConfirmCallback = null;

  function submitControlForm() {
    if (controlForm) controlForm.submit();
  }

  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        submitControlForm();
      }, 350);
    });
    searchInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        submitControlForm();
      }
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", submitControlForm);
  }

  if (entriesSelect) {
    entriesSelect.addEventListener("change", submitControlForm);
  }

  if (openAddUserBtn) {
    openAddUserBtn.addEventListener("click", openAddModal);
  }

  if (closeModalBtn) closeModalBtn.addEventListener("click", closeUserModal);
  if (cancelModalBtn) cancelModalBtn.addEventListener("click", closeUserModal);

  if (closeViewModalBtn) closeViewModalBtn.addEventListener("click", closeViewModal);
  if (closeViewModalFooterBtn) closeViewModalFooterBtn.addEventListener("click", closeViewModal);
  if (closeConfirmModalBtn) closeConfirmModalBtn.addEventListener("click", closeConfirmModal);
  if (cancelConfirmModalBtn) cancelConfirmModalBtn.addEventListener("click", closeConfirmModal);

  if (confirmModalBtn) {
    confirmModalBtn.addEventListener("click", function () {
      if (activeConfirmCallback) {
        activeConfirmCallback();
      }
      closeConfirmModal();
    });
  }

  if (userForm) {
    userForm.addEventListener("submit", handleUserFormSubmit);
  }

  function setFieldError(input, message) {
    if (!input) return;
    const group = input.closest(".form-group");
    if (!group) return;
    const container = group.querySelector(".input-error-container");
    if (container) {
      container.textContent = message;
      container.style.visibility = "visible";
    }
    input.classList.add("invalid");
  }

  function clearFieldError(input) {
    if (!input) return;
    const group = input.closest(".form-group");
    if (!group) return;
    const container = group.querySelector(".input-error-container");
    if (container) {
      container.textContent = "";
      container.style.visibility = "hidden";
    }
    input.classList.remove("invalid");
  }

  function clearAllModalErrors() {
    if (!userForm) return;
    userForm.querySelectorAll(".input-error-container").forEach((c) => {
      c.textContent = "";
      c.style.visibility = "hidden";
    });
    userForm.querySelectorAll(".form-control.invalid").forEach((el) => {
      el.classList.remove("invalid");
    });
  }

  function validateField(input) {
    const name = input.name;
    const value = input.value.trim();
    const nameRegex = /^[A-Z][a-zA-Z\s'-]*$/;
    const addrRegex = /^[A-Za-z0-9][A-Za-z0-9\s'.,#\-/&()]*$/;

    if (name === "first_name" || name === "last_name") {
      if (!value) {
        const label = name === "first_name" ? "First Name" : "Last Name";
        setFieldError(input, `${label} is required.`);
      } else if (!nameRegex.test(value)) {
        const label = name === "first_name" ? "First Name" : "Last Name";
        setFieldError(input, `${label} must start with a capital letter and contain only valid characters.`);
      } else {
        clearFieldError(input);
      }
      return;
    }

    if (name === "middle_name" && value) {
      if (!nameRegex.test(value)) {
        setFieldError(input, "Middle Name must start with a capital letter.");
      } else {
        clearFieldError(input);
      }
      return;
    }

    if (name === "extension" && value) {
      const validExts = ["Jr.", "Jr", "Sr.", "Sr", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
      if (!validExts.includes(value)) {
        setFieldError(input, "Extension must be Jr., Sr., or Roman numerals I to X.");
      } else {
        clearFieldError(input);
      }
      return;
    }

    if (name === "birthdate") {
      if (!value) { setFieldError(input, "Birthdate is required."); return; }
      const dob = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      if (age < 18) { setFieldError(input, "User must be at least 18 years old."); }
      else { clearFieldError(input); }
      return;
    }

    if (name === "gender") {
      if (!value) { setFieldError(input, "Please select a gender."); }
      else { clearFieldError(input); }
      return;
    }

    const addrFields = ["street", "barangay", "city", "province", "country"];
    if (addrFields.includes(name)) {
      const labels = { street: "Purok/Street", barangay: "Barangay", city: "Municipal/City", province: "Province", country: "Country" };
      if (!value) {
        setFieldError(input, `${labels[name]} is required.`);
      } else if (!addrRegex.test(value)) {
        setFieldError(input, `${labels[name]} contains invalid characters.`);
      } else {
        clearFieldError(input);
      }
      return;
    }

    if (name === "zip") {
      if (!value) { setFieldError(input, "Zip Code is required."); }
      else if (!/^\d{4,6}$/.test(value)) { setFieldError(input, "Zip Code must be 4–6 digits."); }
      else { clearFieldError(input); }
      return;
    }

    if (name === "username") {
      if (!value) { setFieldError(input, "Username is required."); }
      else if (/\s/.test(value)) { setFieldError(input, "Username cannot contain spaces."); }
      else if (/([a-zA-Z])\1\1/i.test(value)) { setFieldError(input, "Username cannot contain 3 identical letters in a row."); }
      else { clearFieldError(input); }
      return;
    }

    if (name === "email") {
      if (!value) { setFieldError(input, "Email is required."); }
      else if (/\s/.test(value)) { setFieldError(input, "Email cannot contain spaces."); }
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { setFieldError(input, "Invalid email format."); }
      else { clearFieldError(input); }
      return;
    }

    if (name === "password" && value) {
      const missing = [];
      if (!/[a-z]/.test(value)) missing.push("lowercase letter");
      if (!/[A-Z]/.test(value)) missing.push("uppercase letter");
      if (!/[0-9]/.test(value)) missing.push("number");
      if (!/[^a-zA-Z0-9]/.test(value)) missing.push("special character");
      if (value.length < 8) missing.push("8+ characters");
      if (missing.length > 0) {
        setFieldError(input, "Password too weak. Missing: " + missing.join(", "));
      } else if (/([a-zA-Z])\1\1/i.test(value)) {
        setFieldError(input, "Password cannot contain 3 identical letters in a row.");
      } else {
        clearFieldError(input);
      }
      return;
    }

    if (name === "confirm_password") {
      const passVal = (document.getElementById("formPassword") || {}).value || "";
      if (value && value !== passVal) { setFieldError(input, "Passwords do not match."); }
      else { clearFieldError(input); }
      return;
    }

    clearFieldError(input);
  }

  if (userForm) {
    userForm.querySelectorAll(".form-control").forEach((input) => {
      const tag = input.tagName.toLowerCase();
      if (tag === "select") {
        input.addEventListener("change", () => validateField(input));
      } else {
        input.addEventListener("input", () => validateField(input));
        input.addEventListener("blur", () => validateField(input));
      }
    });
  }

  function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) {
      el.value = (val !== null && val !== undefined) ? val : "";
    }
  }

  function getRowData(btnEl) {
    const tr = btnEl.closest("tr");
    if (!tr) return null;
    return {
      id_number: tr.dataset.id_number || "",
      name: tr.dataset.name || "",
      username: tr.dataset.username || "",
      email: tr.dataset.email || "",
      status: tr.dataset.status || "",
      role: tr.dataset.role || "",
      created: tr.dataset.created || ""
    };
  }

  const birthdateInput = document.getElementById("formBirthdate");
  if (birthdateInput) {
    birthdateInput.addEventListener("change", function () {
      if (!this.value) return;
      const dob = new Date(this.value);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
      }
      setVal("formAge", age >= 0 ? age : 0);
    });
  }

  function openAddModal() {
    if (userForm) userForm.reset();
    clearAllModalErrors();
    setVal("formMode", "add");

    const idEl = document.getElementById("formIdNumber");
    if (idEl) {
      idEl.value = "";
      idEl.removeAttribute("readonly");
    }

    const secTitle = document.getElementById("securitySectionTitle");
    const secGrid = document.getElementById("securitySectionGrid");
    if (secTitle) secTitle.style.display = "flex";
    if (secGrid) secGrid.style.display = "grid";
    ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.required = true;
    });

    const passGrp = document.getElementById("passwordGroup");
    const confGrp = document.getElementById("confirmPasswordGroup");
    const passInp = document.getElementById("formPassword");
    const confInp = document.getElementById("formConfirmPassword");

    if (passGrp) passGrp.style.display = "block";
    if (confGrp) confGrp.style.display = "block";
    if (passInp) passInp.required = true;
    if (confInp) confInp.required = true;

    if (userModalTitle) userModalTitle.textContent = "Add New User";
    if (userModal) userModal.classList.add("show");
  }

  window.editUser = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    fetch(`../../php/auth/index.php?action=getUserDetail&id_number=${encodeURIComponent(row.id_number)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Could not fetch user details.");
          return;
        }

        const user = res.data;
        if (userForm) userForm.reset();
        clearAllModalErrors();
        setVal("formMode", "edit");

        const idEl = document.getElementById("formIdNumber");
        if (idEl) {
          idEl.value = user.id_number || "";
          idEl.setAttribute("readonly", true);
        }

        setVal("formFirstName", user.first_name);
        setVal("formMiddleName", user.middle_name);
        setVal("formLastName", user.last_name);
        setVal("formExtension", user.extension);
        setVal("formBirthdate", user.birthdate);
        setVal("formAge", user.age);
        setVal("formGender", user.gender);

        setVal("formStreet", user.street);
        setVal("formBarangay", user.barangay);
        setVal("formCity", user.city);
        setVal("formProvince", user.province);
        setVal("formCountry", user.country);
        setVal("formZip", user.zip);

        const secTitle = document.getElementById("securitySectionTitle");
        const secGrid = document.getElementById("securitySectionGrid");
        if (secTitle) secTitle.style.display = "none";
        if (secGrid) secGrid.style.display = "none";
        ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });

        setVal("formUsername", user.username);
        setVal("formEmail", user.email);
        setVal("formStatus", user.status === "block" ? "block" : "active");

        const passGrp = document.getElementById("passwordGroup");
        const confGrp = document.getElementById("confirmPasswordGroup");
        const passInp = document.getElementById("formPassword");
        const confInp = document.getElementById("formConfirmPassword");

        if (passGrp) passGrp.style.display = "block";
        if (confGrp) confGrp.style.display = "none";
        if (passInp) passInp.required = false;
        if (confInp) confInp.required = false;

        if (userModalTitle) userModalTitle.textContent = "Edit User Account";
        if (userModal) userModal.classList.add("show");
      })
      .catch((err) => {
        console.error(err);
        alert("An error occurred while opening the edit form.");
      });
  };

  function closeUserModal() {
    userModal.classList.remove("show");
    clearAllModalErrors();
  }

  function handleUserFormSubmit(e) {
    e.preventDefault();

    const mode = document.getElementById("formMode").value;
    const formData = new FormData(userForm);
    const isEdit = mode !== "add";
    const userName = (formData.get("first_name") || "") + " " + (formData.get("last_name") || "");

    // Show confirmation modal before saving changes / adding user
    confirmModalTitle.textContent = isEdit ? "Save Changes?" : "Add User?";
    confirmModalMessage.innerHTML = isEdit
      ? `Are you sure you want to save changes to user account <strong>${escapeHtml(userName.trim())}</strong>?`
      : `Are you sure you want to create a new user account for <strong>${escapeHtml(userName.trim())}</strong>?`;

    confirmModalBtn.className = "btn-primary";
    confirmModalBtn.textContent = isEdit ? "Confirm & Save" : "Confirm & Create";

    activeConfirmCallback = function () {
      const action = mode === "add" ? "addStandardUser" : "updateStandardUser";

      fetch(`../../php/auth/index.php?action=${action}`, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            setFlashToast(isEdit ? "User successfully updated." : "User successfully created.", "success");
            closeUserModal();
            window.location.reload();
          } else if (res.fieldErrors) {
            const fieldMap = {
              first_name:         "formFirstName",
              middle_name:        "formMiddleName",
              last_name:          "formLastName",
              extension:          "formExtension",
              birthdate:          "formBirthdate",
              gender:             "formGender",
              street:             "formStreet",
              barangay:           "formBarangay",
              city:               "formCity",
              province:           "formProvince",
              country:            "formCountry",
              zip:                "formZip",
              username:           "formUsername",
              email:              "formEmail",
              password:           "formPassword",
              confirm_password:   "formConfirmPassword",
            };
            Object.entries(res.fieldErrors).forEach(([field, msg]) => {
              const elId = fieldMap[field];
              if (elId) setFieldError(document.getElementById(elId), msg);
            });
          } else {
            showToast(res.message || "Operation failed.", "error");
          }
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred during form submission.", "error");
        });
    };

    confirmModal.classList.add("show");
  }

  window.viewUser = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    document.getElementById("viewIdNumber").textContent = row.id_number;
    document.getElementById("viewName").textContent = row.name;
    document.getElementById("viewUsername").textContent = "@" + row.username;
    document.getElementById("viewEmail").textContent = row.email || "N/A";
    document.getElementById("viewRole").textContent = (row.role || "user").toUpperCase();
    document.getElementById("viewStatus").textContent = (row.status || "active").toUpperCase();
    document.getElementById("viewCreated").textContent = row.created || "N/A";

    viewModal.classList.add("show");
  };

  function closeViewModal() {
    viewModal.classList.remove("show");
  }

  window.confirmToggleBlock = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    const isBlocking = row.status !== "block" && row.status !== "blocked";
    const actionName = isBlocking ? "Block" : "Unblock";
    const nextStatus = isBlocking ? "block" : "active";

    confirmModalTitle.textContent = `${actionName} User Account`;
    confirmModalMessage.innerHTML = `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> user account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?${
      isBlocking
        ? `<br><br><label for="blockReasonInput" style="font-size: 0.85rem; display: block; margin-bottom: 6px;">Reason:</label><input type="text" id="blockReasonInput" class="form-control" style="width: 100%;" placeholder="e.g. Violation of system policy" />`
        : ""
    }`;

    confirmModalBtn.className = isBlocking ? "btn-danger" : "btn-primary";
    confirmModalBtn.textContent = `${actionName} User`;

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", row.id_number);
      body.append("status", nextStatus);
      const reasonInput = document.getElementById("blockReasonInput");
      if (reasonInput && reasonInput.value.trim() !== "") {
        body.append("reason", reasonInput.value.trim());
      }

      fetch("../../php/auth/index.php?action=toggleBlockUser", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            setFlashToast(isBlocking ? "User successfully blocked." : "User successfully unblocked.", "success");
            window.location.reload();
          } else {
            showToast(res.message || "Failed to update status.", "error");
          }
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred while updating status.", "error");
        });
    };

    confirmModal.classList.add("show");
  };

  window.confirmDeleteUser = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    confirmModalTitle.textContent = "Delete User Account";
    confirmModalMessage.innerHTML = `Are you sure you want to delete user account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?<br><br><span style="color:#dc2626; font-size: 0.85rem;"><i class="fa-solid fa-triangle-exclamation"></i> Warning: This action cannot be undone.</span>`;

    confirmModalBtn.className = "btn-danger";
    confirmModalBtn.textContent = "Confirm & Delete";

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", row.id_number);

      fetch("../../php/auth/index.php?action=deleteStandardUser", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            setFlashToast("User successfully deleted.", "success");
            window.location.reload();
          } else {
            showToast(res.message || "Failed to delete user.", "error");
          }
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred while deleting user.", "error");
        });
    };

    confirmModal.classList.add("show");
  };

  function closeConfirmModal() {
    confirmModal.classList.remove("show");
    activeConfirmCallback = null;
  }

  function escapeHtml(text) {
    if (!text) return "";
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  /* ── Custom role-dropdown interaction ──────────────────────────────── */

  // ── Shared state & helpers (hoisted before any forEach usage) ────────
  const roleConfirmModal         = document.getElementById("roleConfirmModal");
  const roleConfirmMessage       = document.getElementById("roleConfirmMessage");
  const confirmRoleChangeBtn     = document.getElementById("confirmRoleChangeBtn");
  const cancelRoleConfirmModalBtn = document.getElementById("cancelRoleConfirmModalBtn");
  const closeRoleConfirmModalBtn  = document.getElementById("closeRoleConfirmModalBtn");

  const roleLabels = { user: "User", admin: "Admin", superadmin: "Super Admin" };
  let pendingRoleChange = null;

  function setRoleSelectCurrent(select) {
    const selected = select.querySelector("option:checked");
    select.dataset.currentRole = selected ? selected.value : "user";
  }

  function openRoleConfirm() {
    if (roleConfirmModal) roleConfirmModal.classList.add("show");
  }

  function closeRoleConfirm() {
    if (roleConfirmModal) roleConfirmModal.classList.remove("show");
    pendingRoleChange = null;
  }

  if (cancelRoleConfirmModalBtn) cancelRoleConfirmModalBtn.addEventListener("click", closeRoleConfirm);
  if (closeRoleConfirmModalBtn)  closeRoleConfirmModalBtn.addEventListener("click",  closeRoleConfirm);

  function closeAllRoleDropdowns(except) {
    document.querySelectorAll(".role-dropdown.open").forEach((d) => {
      if (d !== except) d.classList.remove("open");
    });
  }

  // ── Bind custom dropdown UI ──────────────────────────────────────────
  document.querySelectorAll(".role-dropdown").forEach((dropdown) => {
    const btn    = dropdown.querySelector(".role-dropdown-btn");
    const menu   = dropdown.querySelector(".role-dropdown-menu");
    const select = dropdown.querySelector(".role-select");

    if (select) setRoleSelectCurrent(select);

    if (btn) {
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains("open");
        closeAllRoleDropdowns(null);
        if (!isOpen) {
          dropdown.classList.add("open");
          btn.setAttribute("aria-expanded", "true");
        } else {
          btn.setAttribute("aria-expanded", "false");
        }
      });
    }

    if (menu) {
      menu.querySelectorAll(".role-option").forEach((opt) => {
        opt.addEventListener("click", function (e) {
          e.stopPropagation();
          const newRole = this.dataset.value;
          if (select) {
            select.value = newRole;
            select.dispatchEvent(new Event("change", { bubbles: true }));
          }
          dropdown.classList.remove("open");
          if (btn) btn.setAttribute("aria-expanded", "false");
        });
      });
    }
  });

  // ── Bind change logic to hidden native selects ───────────────────────
  document.querySelectorAll(".role-select").forEach((select) => {
    setRoleSelectCurrent(select);

    select.addEventListener("change", function () {
      const newRole      = this.value;
      const previousRole = this.dataset.currentRole || "user";

      this.value = previousRole; // revert until confirmed

      if (newRole === previousRole) return;

      pendingRoleChange = {
        id_number:    this.dataset.id_number || "",
        username:     this.dataset.username  || "",
        name:         this.dataset.name      || "",
        newRole:      newRole,
        previousRole: previousRole,
        confirmSelf:  false,
      };

      const fromText = roleLabels[previousRole] || previousRole;
      const toText   = roleLabels[newRole]       || newRole;

      roleConfirmMessage.innerHTML = `Are you sure you want to change this user from <strong>${escapeHtml(fromText)} → ${escapeHtml(toText)}</strong>?`;
      confirmRoleChangeBtn.textContent = "Confirm";
      openRoleConfirm();
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", function () {
    closeAllRoleDropdowns(null);
    document.querySelectorAll(".role-dropdown-btn").forEach((b) =>
      b.setAttribute("aria-expanded", "false")
    );
  });


  if (confirmRoleChangeBtn) {
    confirmRoleChangeBtn.addEventListener("click", function () {
      if (!pendingRoleChange) return;

      const body = new FormData();
      body.append("id_number", pendingRoleChange.id_number);
      body.append("new_role", pendingRoleChange.newRole);
      if (pendingRoleChange.confirmSelf) body.append("confirm_self", "true");

      fetch("../../php/auth/index.php?action=changeRole", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            setFlashToast("Role successfully changed.", "success");
            window.location.reload();
            return;
          }
          if (res.confirmation) {
            // Deliberate security confirmation required (self-demotion)
            pendingRoleChange.confirmSelf = true;
            roleConfirmMessage.innerHTML = escapeHtml(
              res.message || "This change removes your own Super Admin access. Confirm to continue."
            );
            confirmRoleChangeBtn.textContent = "Confirm Anyway";
            return;
          }
          showToast(res.message || "Failed to change role.", "error");
          closeRoleConfirm();
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred while changing the role.", "error");
          closeRoleConfirm();
        });
    });
  }

  /* ===================== ACCESSIBLE ACTION DROPDOWN ===================== */

  window.closeAllDropdowns = function () {
    document.querySelectorAll(".action-dropdown-menu.open").forEach((m) => m.classList.remove("open"));
    document.querySelectorAll(".action-dropdown-btn.active").forEach((b) => {
      b.classList.remove("active");
      b.setAttribute("aria-expanded", "false");
    });
  };

  window.toggleActionDropdown = function (btn) {
    const menu = btn.nextElementSibling;
    const isOpen = menu && menu.classList.contains("open");
    closeAllDropdowns();
    if (!isOpen && menu) {
      menu.classList.add("open");
      btn.classList.add("active");
      btn.setAttribute("aria-expanded", "true");
    }
  };

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".action-dropdown")) {
      closeAllDropdowns();
    }
  });

  /* ===================== TOAST NOTIFICATION SYSTEM ===================== */

  function showToast(message, type = "success") {
    let container = document.querySelector(".toast-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "toast-container";
      document.body.appendChild(container);
    }
    const toast = document.createElement("div");
    toast.className = `toast-item toast-${type}`;
    const iconClass = type === "success" ? "fa-solid fa-circle-check" : "fa-solid fa-circle-exclamation";
    toast.innerHTML = `
      <i class="${iconClass} toast-icon"></i>
      <div class="toast-msg">${escapeHtml(message)}</div>
      <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateX(40px) scale(0.95)";
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function setFlashToast(message, type = "success") {
    sessionStorage.setItem("flash_toast", JSON.stringify({ message, type }));
  }

  function checkFlashToast() {
    const flash = sessionStorage.getItem("flash_toast");
    if (flash) {
      try {
        const data = JSON.parse(flash);
        if (data && data.message) {
          showToast(data.message, data.type || "success");
        }
      } catch (e) {}
      sessionStorage.removeItem("flash_toast");
    }
  }

  checkFlashToast();
});
