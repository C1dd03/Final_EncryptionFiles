document.addEventListener("DOMContentLoaded", function () {
  let searchTimeout = null;

  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");
  const controlForm = document.getElementById("controlForm");

  const adminModal = document.getElementById("adminModal");
  const adminModalTitle = document.getElementById("adminModalTitle");
  const adminForm = document.getElementById("adminForm");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const cancelModalBtn = document.getElementById("cancelModalBtn");
  const openAddAdminBtn = document.getElementById("openAddAdminBtn");

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

  if (openAddAdminBtn) {
    openAddAdminBtn.addEventListener("click", openAddModal);
  }

  if (closeModalBtn) closeModalBtn.addEventListener("click", closeAdminModal);
  if (cancelModalBtn) cancelModalBtn.addEventListener("click", closeAdminModal);

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

  if (adminForm) {
    adminForm.addEventListener("submit", handleAdminFormSubmit);
  }

  function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) {
      el.value = (val !== null && val !== undefined) ? val : "";
    }
  }

  function setFieldError(input, message) {
    if (!input) return;
    const formGroup = input.closest(".form-group");
    if (!formGroup) return;

    let errorContainer = formGroup.querySelector(".input-error-container");
    if (!errorContainer) {
      errorContainer = document.createElement("div");
      errorContainer.className = "input-error-container";
      formGroup.appendChild(errorContainer);
    }

    if (message) {
      errorContainer.textContent = message;
      errorContainer.style.visibility = "visible";
      input.classList.add("invalid");
    } else {
      errorContainer.textContent = "";
      errorContainer.style.visibility = "hidden";
      input.classList.remove("invalid");
    }
  }

  function clearFieldError(input) {
    setFieldError(input, "");
  }

  function clearAllModalErrors() {
    if (!adminForm) return;
    adminForm.querySelectorAll("input, select").forEach((input) => {
      clearFieldError(input);
    });
  }

  const normalizeExtension = (rawValue) => {
    const value = rawValue.trim();
    if (value === "") return "";
    const upperValue = value.toUpperCase();
    if (["JR", "JR."].includes(upperValue)) return "Jr.";
    if (["SR", "SR."].includes(upperValue)) return "Sr.";
    const romanValue = upperValue.replace(/\./g, "");
    const validRomans = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
    if (validRomans.includes(romanValue)) return romanValue;
    return value;
  };

  function validateField(input) {
    if (!input || input.disabled || input.readOnly || input.offsetParent === null) {
      return null;
    }

    const name = input.name;
    const rawValue = input.value;
    const value = rawValue.trim();
    const mode = document.getElementById("formMode")?.value || "add";

    if (input.required && value === "") {
      const labels = {
        first_name: "First Name",
        last_name: "Last Name",
        birthdate: "Birthdate",
        gender: "Gender",
        street: "Purok / Street",
        barangay: "Barangay",
        city: "Municipal / City",
        province: "Province",
        country: "Country",
        zip: "Zip Code",
        username: "Username",
        email: "Email Address",
        password: "Password",
        confirm_password: "Confirm Password",
        security_question_1: "Question 1",
        security_question_2: "Question 2",
        security_question_3: "Question 3",
        security_q1: "Answer 1",
        security_q2: "Answer 2",
        security_q3: "Answer 3"
      };
      const label = labels[name] || name.replace(/_/g, " ");
      return `${label} is required.`;
    }

    if (["first_name", "middle_name", "last_name"].includes(name)) {
      if (value === "") return null;

      const labels = { first_name: "First Name", middle_name: "Middle Name", last_name: "Last Name" };
      const label = labels[name];

      if (rawValue.length > 0 && rawValue.charAt(0) === " ") {
        return `${label} cannot start with a space.`;
      }
      if (rawValue.length > 0 && !/^[A-Za-z]/.test(rawValue.charAt(0))) {
        return `${label} must start with a letter only.`;
      }
      if (/\s{2,}/.test(rawValue)) {
        return `${label} cannot contain double spaces.`;
      }
      if (/([a-zA-Z])\1\1/i.test(value)) {
        return `${label}: No 3 same letters in a row.`;
      }
      if (value.length > 1 && value === value.toUpperCase()) {
        return `${label} should avoid all caps.`;
      }

      const words = value.split(/\s+/);
      for (const w of words) {
        if (w.length > 0 && w[0] !== w[0].toUpperCase()) {
          return `${label} requires each word to start with a capital letter.`;
        }
        for (let i = 1; i < w.length; i++) {
          if (/[A-Za-z]/.test(w[i]) && w[i] === w[i].toUpperCase()) {
            return `${label} cannot contain capital letters after the first letter of each name.`;
          }
        }
      }

      if (!/^[A-Za-z\s]+$/.test(value)) {
        return `${label} can only contain letters and spaces.`;
      }
    }

    if (name === "extension" && value !== "") {
      const normalized = normalizeExtension(value);
      const validExts = ["Jr.", "Sr.", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
      if (!validExts.includes(normalized)) {
        return "Extension must be Jr., Sr., or Roman numerals I–X.";
      }
    }

    if (name === "birthdate" && value !== "") {
      const dob = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      if (age < 18) {
        return "Admin must be at least 18 years old.";
      }
    }

    if (name === "gender" && value !== "") {
      if (!["male", "female"].includes(value.toLowerCase())) {
        return "Please select a valid gender.";
      }
    }

    const addressLabels = {
      street: "Purok / Street",
      barangay: "Barangay",
      city: "Municipal / City",
      province: "Province",
      country: "Country"
    };

    if (addressLabels[name]) {
      const label = addressLabels[name];
      if (rawValue.length > 0 && rawValue.charAt(0) === " ") {
        return `${label} cannot start with a space.`;
      }
      if (name !== "street" && rawValue.length > 0 && !/^[A-Za-z]/.test(rawValue.charAt(0))) {
        return `${label} must start with a letter only.`;
      }
      if (/\s{2,}/.test(rawValue)) {
        return `${label} cannot contain double spaces.`;
      }

      if (name === "street") {
        if (value.length < 3) return `${label} must be at least 3 characters long.`;
        if (/^[\d\s]+$/.test(value)) return `${label} must contain letters.`;
      } else {
        if (/\d/.test(value)) return `${label} cannot include numbers.`;
        if (/[^A-Za-z\s.-]/.test(value)) return `${label} cannot contain special characters.`;
        if (value.length > 1 && value === value.toUpperCase()) {
          return `${label} should avoid all caps.`;
        }
      }

      if (/([a-zA-Z])\1\1/i.test(value)) {
        return `${label}: No 3 same letters in a row.`;
      }
    }

    if (name === "zip" && value !== "") {
      if (!/^\d+$/.test(value)) return "Zip Code must contain numbers only.";
      if (value.length < 4 || value.length > 6) return "Zip Code must be 4 to 6 digits.";
    }

    if (name.startsWith("security_q") && value !== "") {
      if (/^\s+$/.test(rawValue)) return "Answer cannot contain only spaces.";
      if (/\s/.test(rawValue)) return "Answer cannot contain spaces.";
    }

    if (name === "username" && value !== "") {
      if (/\s/.test(rawValue)) return "Username cannot contain spaces.";
      if (/\s{2,}/.test(rawValue)) return "Username cannot contain double spaces.";
      if (/([a-zA-Z])\1\1/i.test(value)) return "Username cannot contain 3 identical letters in a row.";
    }

    if (name === "email" && value !== "") {
      if (/\s/.test(rawValue)) return "Email cannot contain spaces.";
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return "Invalid email address format.";
    }

    if (name === "password") {
      if (mode === "edit" && value === "") return null;

      if (value !== "") {
        const hasLower = /[a-z]/.test(value);
        const hasUpper = /[A-Z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[^a-zA-Z0-9]/.test(value);
        const hasLength = value.length >= 8;

        if (!hasLower || !hasUpper || !hasNumber || !hasSpecial || !hasLength) {
          const missing = [];
          if (!hasLower) missing.push("lowercase letter");
          if (!hasUpper) missing.push("uppercase letter");
          if (!hasNumber) missing.push("number");
          if (!hasSpecial) missing.push("special character");
          if (!hasLength) missing.push("8+ characters");
          return `Password is too weak. Missing: ${missing.join(", ")}.`;
        }

        if (/([a-zA-Z])\1\1/i.test(value)) {
          return "Password cannot contain 3 identical letters in a row.";
        }
      }
    }

    if (name === "confirm_password") {
      const passwordVal = document.getElementById("formPassword")?.value || "";
      if (mode === "add" || passwordVal !== "") {
        if (value !== passwordVal) {
          return "Passwords do not match.";
        }
      }
    }

    return null;
  }

  if (adminForm) {
    adminForm.querySelectorAll("input, select").forEach((input) => {
      ["input", "blur", "change"].forEach((evtType) => {
        input.addEventListener(evtType, function () {
          const err = validateField(this);
          setFieldError(this, err);

          if (this.name === "password") {
            const confInp = document.getElementById("formConfirmPassword");
            if (confInp && (confInp.value || mode === "add")) {
              setFieldError(confInp, validateField(confInp));
            }
          }
        });
      });
    });

    const extInput = document.getElementById("formExtension");
    if (extInput) {
      extInput.addEventListener("blur", function () {
        this.value = normalizeExtension(this.value);
        const err = validateField(this);
        setFieldError(this, err);
      });
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
    if (adminForm) adminForm.reset();
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

    if (adminModalTitle) adminModalTitle.textContent = "Add New Admin";
    if (adminModal) adminModal.classList.add("show");
  }

  window.editAdmin = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    fetch(`../../php/auth/index.php?action=getAdminDetail&id_number=${encodeURIComponent(row.id_number)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Could not fetch admin details.");
          return;
        }

        const admin = res.data;
        if (adminForm) adminForm.reset();
        clearAllModalErrors();
        setVal("formMode", "edit");

        const idEl = document.getElementById("formIdNumber");
        if (idEl) {
          idEl.value = admin.id_number || "";
          idEl.setAttribute("readonly", true);
        }

        setVal("formFirstName", admin.first_name);
        setVal("formMiddleName", admin.middle_name);
        setVal("formLastName", admin.last_name);
        setVal("formExtension", admin.extension);
        setVal("formBirthdate", admin.birthdate);
        setVal("formAge", admin.age);
        setVal("formGender", admin.gender);

        setVal("formStreet", admin.street);
        setVal("formBarangay", admin.barangay);
        setVal("formCity", admin.city);
        setVal("formProvince", admin.province);
        setVal("formCountry", admin.country);
        setVal("formZip", admin.zip);

        const secTitle = document.getElementById("securitySectionTitle");
        const secGrid = document.getElementById("securitySectionGrid");
        if (secTitle) secTitle.style.display = "none";
        if (secGrid) secGrid.style.display = "none";
        ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });

        setVal("formUsername", admin.username);
        setVal("formEmail", admin.email);
        setVal("formStatus", admin.status === "block" ? "block" : "active");

        const passGrp = document.getElementById("passwordGroup");
        const confGrp = document.getElementById("confirmPasswordGroup");
        const passInp = document.getElementById("formPassword");
        const confInp = document.getElementById("formConfirmPassword");

        if (passGrp) passGrp.style.display = "block";
        if (confGrp) confGrp.style.display = "none";
        if (passInp) passInp.required = false;
        if (confInp) confInp.required = false;

        if (adminModalTitle) adminModalTitle.textContent = "Edit Admin Account";
        if (adminModal) adminModal.classList.add("show");
      })
      .catch((err) => {
        console.error(err);
        alert("An error occurred while opening the edit form.");
      });
  };

  function closeAdminModal() {
    clearAllModalErrors();
    adminModal.classList.remove("show");
  }

  function handleAdminFormSubmit(e) {
    e.preventDefault();

    let firstInvalidInput = null;
    let hasError = false;

    clearAllModalErrors();

    adminForm.querySelectorAll("input, select").forEach((input) => {
      const err = validateField(input);
      if (err) {
        setFieldError(input, err);
        if (!firstInvalidInput) firstInvalidInput = input;
        hasError = true;
      }
    });

    if (hasError) {
      if (firstInvalidInput) {
        firstInvalidInput.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalidInput.focus();
      }
      return;
    }

    const mode = document.getElementById("formMode").value;
    const formData = new FormData(adminForm);
    const action = mode === "add" ? "addAdmin" : "updateAdmin";

    fetch(`../../php/auth/index.php?action=${action}`, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          alert(res.message);
          closeAdminModal();
          window.location.reload();
        } else {
          if (res.fieldErrors && typeof res.fieldErrors === "object") {
            let serverFirstInvalid = null;
            Object.keys(res.fieldErrors).forEach((fieldName) => {
              const inp = adminForm.querySelector(`[name="${fieldName}"]`);
              if (inp) {
                setFieldError(inp, res.fieldErrors[fieldName]);
                if (!serverFirstInvalid) serverFirstInvalid = inp;
              }
            });
            if (serverFirstInvalid) {
              serverFirstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
              serverFirstInvalid.focus();
            }
          } else {
            alert(res.message || "Operation failed.");
          }
        }
      })
      .catch((err) => {
        console.error(err);
        alert("An error occurred during form submission.");
      });
  }

  window.viewAdmin = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    document.getElementById("viewIdNumber").textContent = row.id_number;
    document.getElementById("viewName").textContent = row.name;
    document.getElementById("viewUsername").textContent = "@" + row.username;
    document.getElementById("viewEmail").textContent = row.email || "N/A";
    document.getElementById("viewRole").textContent = (row.role || "admin").toUpperCase();
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

    confirmModalTitle.textContent = `${actionName} Admin Account`;
    confirmModalMessage.innerHTML = `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> admin account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?${
      isBlocking
        ? `<br><br><label for="blockReasonInput" style="font-size: 0.85rem; display: block; margin-bottom: 6px;">Reason:</label><input type="text" id="blockReasonInput" class="form-control" style="width: 100%;" placeholder="e.g. Violation of system policy" />`
        : ""
    }`;

    confirmModalBtn.className = isBlocking ? "btn-danger" : "btn-primary";
    confirmModalBtn.textContent = `${actionName} Admin`;

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", row.id_number);
      body.append("status", nextStatus);
      const reasonInput = document.getElementById("blockReasonInput");
      if (reasonInput && reasonInput.value.trim() !== "") {
        body.append("reason", reasonInput.value.trim());
      }

      fetch("../../php/auth/index.php?action=toggleBlockAdmin", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            window.location.reload();
          } else {
            alert(res.message || "Failed to update status.");
          }
        });
    };

    confirmModal.classList.add("show");
  };

  window.confirmDeleteAdmin = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    confirmModalTitle.textContent = "Delete Admin Account";
    confirmModalMessage.innerHTML = `Are you sure you want to permanently <strong>DELETE</strong> admin account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?<br><br><span style="color:#dc2626; font-size: 0.85rem;">Warning: This action cannot be undone.</span>`;

    confirmModalBtn.className = "btn-danger";
    confirmModalBtn.textContent = "Delete Permanently";

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", row.id_number);

      fetch("../../php/auth/index.php?action=deleteAdmin", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            window.location.reload();
          } else {
            alert(res.message || "Failed to delete admin.");
          }
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

  /* ===================== ROLE CHANGE (dropdown) ===================== */

  const roleConfirmModal = document.getElementById("roleConfirmModal");
  const roleConfirmMessage = document.getElementById("roleConfirmMessage");
  const confirmRoleChangeBtn = document.getElementById("confirmRoleChangeBtn");
  const cancelRoleConfirmModalBtn = document.getElementById("cancelRoleConfirmModalBtn");
  const closeRoleConfirmModalBtn = document.getElementById("closeRoleConfirmModalBtn");

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
  if (closeRoleConfirmModalBtn) closeRoleConfirmModalBtn.addEventListener("click", closeRoleConfirm);

  document.querySelectorAll(".role-select").forEach((select) => {
    setRoleSelectCurrent(select);

    select.addEventListener("change", function () {
      const newRole = this.value;
      const previousRole = this.dataset.currentRole || "user";

      // Revert the dropdown until the change is confirmed (or rejected)
      this.value = previousRole;

      if (newRole === previousRole) return;

      pendingRoleChange = {
        id_number: this.dataset.id_number || "",
        username: this.dataset.username || "",
        name: this.dataset.name || "",
        newRole: newRole,
        previousRole: previousRole,
        confirmSelf: false,
      };

      roleConfirmMessage.innerHTML = [
        `You are about to change this administrator's role.`,
        ``,
        `<strong>New Role:</strong> ${escapeHtml(roleLabels[newRole] || newRole)}`,
        newRole === "user"
          ? `This will remove all administrator privileges.`
          : `Privileges must be assigned separately after the role change.`,
        ``,
        `Are you sure you want to continue?`,
      ].join("<br>");

      confirmRoleChangeBtn.textContent = "Confirm";
      openRoleConfirm();
    });
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
            // The account moved to its new management section
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
          alert(res.message || "Failed to change role.");
          closeRoleConfirm();
        })
        .catch((err) => {
          console.error(err);
          alert("An error occurred while changing the role.");
          closeRoleConfirm();
        });
    });
  }

  /* ===================== MANAGE PRIVILEGES (modal) ===================== */

  const privilegesModal = document.getElementById("privilegesModal");
  const privilegesIdNumber = document.getElementById("privilegesIdNumber");
  const privilegesUsername = document.getElementById("privilegesUsername");
  const closePrivilegesModalBtn = document.getElementById("closePrivilegesModalBtn");
  const cancelPrivilegesModalBtn = document.getElementById("cancelPrivilegesModalBtn");
  const savePrivilegesBtn = document.getElementById("savePrivilegesBtn");
  const privilegeCheckboxes = document.querySelectorAll(".privilege-checkbox");

  function closePrivilegesModal() {
    if (privilegesModal) privilegesModal.classList.remove("show");
  }

  if (closePrivilegesModalBtn) closePrivilegesModalBtn.addEventListener("click", closePrivilegesModal);
  if (cancelPrivilegesModalBtn) cancelPrivilegesModalBtn.addEventListener("click", closePrivilegesModal);

  window.openPrivilegesModal = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    if (privilegesIdNumber) privilegesIdNumber.value = row.id_number;
    if (privilegesUsername) privilegesUsername.value = row.username;

    privilegeCheckboxes.forEach((cb) => (cb.checked = false));

    fetch(`../../php/auth/index.php?action=getAdminPrivileges&id_number=${encodeURIComponent(row.id_number)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Could not fetch privileges.");
          return;
        }
        const granted = res.privileges || [];
        privilegeCheckboxes.forEach((cb) => {
          cb.checked = granted.indexOf(cb.value) !== -1;
        });
        if (privilegesModal) privilegesModal.classList.add("show");
      })
      .catch((err) => {
        console.error(err);
        alert("An error occurred while loading privileges.");
      });
  };

  if (savePrivilegesBtn) {
    savePrivilegesBtn.addEventListener("click", function () {
      const idNumber = privilegesIdNumber ? privilegesIdNumber.value : "";
      if (!idNumber) {
        alert("No administrator selected.");
        return;
      }

      const checked = [];
      privilegeCheckboxes.forEach((cb) => {
        if (cb.checked) checked.push(cb.value);
      });

      const body = new FormData();
      body.append("id_number", idNumber);
      body.append("privileges", JSON.stringify(checked));

      savePrivilegesBtn.disabled = true;

      fetch("../../php/auth/index.php?action=saveAdminPrivileges", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          savePrivilegesBtn.disabled = false;
          if (res.success) {
            alert(res.message || "Privileges updated.");
            closePrivilegesModal();
          } else {
            alert(res.message || "Failed to save privileges.");
          }
        })
        .catch((err) => {
          savePrivilegesBtn.disabled = false;
          console.error(err);
          alert("An error occurred while saving privileges.");
        });
    });
  }

  window.closeAllDropdowns = function () {
    document.querySelectorAll(".action-dropdown-menu.open").forEach((m) => m.classList.remove("open"));
    document.querySelectorAll(".action-dropdown-toggle.active").forEach((b) => b.classList.remove("active"));
  };

  window.toggleActionDropdown = function (btn) {
    const menu = btn.nextElementSibling;
    const isOpen = menu.classList.contains("open");
    closeAllDropdowns();
    if (!isOpen) {
      menu.classList.add("open");
      btn.classList.add("active");
    }
  };

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".action-dropdown")) {
      closeAllDropdowns();
    }
  });
});
