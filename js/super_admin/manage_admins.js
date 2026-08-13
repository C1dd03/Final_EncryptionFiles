document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;
  let currentLimit = 10;
  let currentSearch = "";
  let currentStatus = "all";
  let searchTimeout = null;

  // DOM Elements
  const adminTableBody = document.getElementById("adminTableBody");
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  // Modals
  const adminModal = document.getElementById("adminModal");
  const adminModalTitle = document.getElementById("adminModalTitle");
  const adminForm = document.getElementById("adminForm");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const cancelModalBtn = document.getElementById("cancelModalBtn");
  const openAddAdminBtn = document.getElementById("openAddAdminBtn");

  const viewModal = document.getElementById("viewModal");
  const closeViewModalBtn = document.getElementById("closeViewModalBtn");

  const confirmModal = document.getElementById("confirmModal");
  const confirmModalTitle = document.getElementById("confirmModalTitle");
  const confirmModalMessage = document.getElementById("confirmModalMessage");
  const confirmModalBtn = document.getElementById("confirmModalBtn");
  const cancelConfirmModalBtn = document.getElementById("cancelConfirmModalBtn");
  const closeConfirmModalBtn = document.getElementById("closeConfirmModalBtn");

  let activeConfirmCallback = null;

  // Initialize
  loadAdmins();

  // Event Listeners
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        currentPage = 1;
        loadAdmins();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", function () {
      currentStatus = this.value;
      currentPage = 1;
      loadAdmins();
    });
  }

  if (entriesSelect) {
    entriesSelect.addEventListener("change", function () {
      currentLimit = parseInt(this.value, 10);
      currentPage = 1;
      loadAdmins();
    });
  }

  if (openAddAdminBtn) {
    openAddAdminBtn.addEventListener("click", openAddModal);
  }

  if (closeModalBtn) closeModalBtn.addEventListener("click", closeAdminModal);
  if (cancelModalBtn) cancelModalBtn.addEventListener("click", closeAdminModal);

  if (closeViewModalBtn) closeViewModalBtn.addEventListener("click", closeViewModal);
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

  // Core Load Function
  function loadAdmins() {
    if (!adminTableBody) return;

    adminTableBody.innerHTML = `
      <tr>
        <td colspan="8" class="empty-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading admin accounts...</p>
        </td>
      </tr>`;

    const url = `../../php/auth/index.php?action=getAdmins&search=${encodeURIComponent(
      currentSearch
    )}&status=${encodeURIComponent(
      currentStatus
    )}&page=${currentPage}&limit=${currentLimit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          showEmptyState(res.message || "Failed to load admins.");
          return;
        }

        renderTable(res.data, res.currentPage, res.limit);
        renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
      })
      .catch((err) => {
        console.error(err);
        showEmptyState("An error occurred while fetching admin accounts.");
      });
  }

  function renderTable(admins, page, limit) {
    if (!admins || admins.length === 0) {
      showEmptyState("No admin accounts found matching your criteria.");
      return;
    }

    const startIdx = (page - 1) * limit;
    let html = "";

    admins.forEach((admin, index) => {
      const rowId = startIdx + index + 1;
      const isBlocked = admin.status === "block" || admin.status === "blocked";
      const statusBadge = isBlocked
        ? `<span class="badge-status badge-blocked">Blocked</span>`
        : `<span class="badge-status badge-active">Active</span>`;

      const blockBtnText = isBlocked ? "Unblock" : "Block";
      const blockIcon = isBlocked ? "fa-solid fa-unlock" : "fa-solid fa-ban";
      const blockClass = isBlocked ? "unblock" : "block";

      html += `
        <tr>
          <td><strong>#${rowId}</strong></td>
          <td><code>${escapeHtml(admin.id_number)}</code></td>
          <td><strong>${escapeHtml(admin.name)}</strong></td>
          <td>@${escapeHtml(admin.username)}</td>
          <td>${escapeHtml(admin.email || "N/A")}</td>
          <td><span class="badge-role">Admin</span></td>
          <td>${statusBadge}</td>
          <td>
            <div class="action-buttons">
              <button class="btn-action view" title="View Details" onclick="viewAdmin('${escapeHtml(admin.id_number)}')">
                <i class="fa-solid fa-eye"></i>
              </button>
              <button class="btn-action edit" title="Edit Admin" onclick="editAdmin('${escapeHtml(admin.id_number)}')">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn-action ${blockClass}" title="${blockBtnText} Admin" onclick="confirmToggleBlock('${escapeHtml(admin.id_number)}', '${escapeHtml(admin.name)}', '${admin.status}')">
                <i class="${blockIcon}"></i>
              </button>
              <button class="btn-action delete" title="Delete Admin" onclick="confirmDeleteAdmin('${escapeHtml(admin.id_number)}', '${escapeHtml(admin.name)}')">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>`;
    });

    adminTableBody.innerHTML = html;
  }


  function showEmptyState(msg) {
    adminTableBody.innerHTML = `
      <tr>
        <td colspan="8" class="empty-state">
          <i class="fa-solid fa-user-slash"></i>
          <p>${escapeHtml(msg)}</p>
        </td>
      </tr>`;
    if (paginationInfo) paginationInfo.textContent = "Showing 0 entries";
    if (paginationControls) paginationControls.innerHTML = "";
  }

  function renderPagination(totalRecords, totalPages, page, limit) {
    if (!paginationInfo || !paginationControls) return;

    if (totalRecords === 0) {
      paginationInfo.textContent = "Showing 0 entries";
      paginationControls.innerHTML = "";
      return;
    }

    const start = (page - 1) * limit + 1;
    const end = Math.min(page * limit, totalRecords);
    paginationInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

    let controlsHtml = "";

    // Prev
    controlsHtml += `<button class="page-btn" ${page <= 1 ? "disabled" : ""} onclick="changePage(${page - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;

    // Page Buttons
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
        controlsHtml += `<button class="page-btn ${i === page ? "active" : ""}" onclick="changePage(${i})">${i}</button>`;
      } else if (i === page - 2 || i === page + 2) {
        controlsHtml += `<span style="padding: 0 4px; color: var(--farm-muted);">...</span>`;
      }
    }

    // Next
    controlsHtml += `<button class="page-btn" ${page >= totalPages ? "disabled" : ""} onclick="changePage(${page + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;

    paginationControls.innerHTML = controlsHtml;
  }

  window.changePage = function (newPage) {
    currentPage = newPage;
    loadAdmins();
  };

  // Helper function to safely set input values without throwing null errors
  function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) {
      el.value = (val !== null && val !== undefined) ? val : "";
    }
  }

  // Age calculation listener
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

  // --- REAL-TIME ERROR HANDLING HELPER FUNCTIONS ---
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

  // Real-time Field Validator
  function validateField(input) {
    if (!input || input.disabled || input.readOnly || input.offsetParent === null) {
      return null;
    }

    const name = input.name;
    const rawValue = input.value;
    const value = rawValue.trim();
    const mode = document.getElementById("formMode")?.value || "add";

    // Required check
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

    // Name Fields (First, Middle, Last)
    if (["first_name", "middle_name", "last_name"].includes(name)) {
      if (value === "") return null; // Middle name optional

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

      // Capitalization check per word
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

    // Extension Field
    if (name === "extension" && value !== "") {
      const normalized = normalizeExtension(value);
      const validExts = ["Jr.", "Sr.", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
      if (!validExts.includes(normalized)) {
        return "Extension must be Jr., Sr., or Roman numerals I–X.";
      }
    }

    // Birthdate
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

    // Gender
    if (name === "gender" && value !== "") {
      if (!["male", "female"].includes(value.toLowerCase())) {
        return "Please select a valid gender.";
      }
    }

    // Address Fields
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

    // Zip Code
    if (name === "zip" && value !== "") {
      if (!/^\d+$/.test(value)) return "Zip Code must contain numbers only.";
      if (value.length < 4 || value.length > 6) return "Zip Code must be 4 to 6 digits.";
    }

    // Security Answers
    if (name.startsWith("security_q") && value !== "") {
      if (/^\s+$/.test(rawValue)) return "Answer cannot contain only spaces.";
      if (/\s/.test(rawValue)) return "Answer cannot contain spaces.";
    }

    // Username
    if (name === "username" && value !== "") {
      if (/\s/.test(rawValue)) return "Username cannot contain spaces.";
      if (/\s{2,}/.test(rawValue)) return "Username cannot contain double spaces.";
      if (/([a-zA-Z])\1\1/i.test(value)) return "Username cannot contain 3 identical letters in a row.";
    }

    // Email
    if (name === "email" && value !== "") {
      if (/\s/.test(rawValue)) return "Email cannot contain spaces.";
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return "Invalid email address format.";
    }

    // Password
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

    // Confirm Password
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

  // Attach Realtime Event Listeners to Admin Form Fields
  if (adminForm) {
    adminForm.querySelectorAll("input, select").forEach((input) => {
      ["input", "blur", "change"].forEach((evtType) => {
        input.addEventListener(evtType, function () {
          const err = validateField(this);
          setFieldError(this, err);

          // Re-validate confirm password if password changes
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

  // Add / Edit Modal Logic
  function openAddModal() {
    if (adminForm) adminForm.reset();
    clearAllModalErrors();
    setVal("formMode", "add");

    const idEl = document.getElementById("formIdNumber");
    if (idEl) {
      idEl.value = "";
      idEl.removeAttribute("readonly");
    }

    // Security Questions visible & required on add
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

  window.editAdmin = function (idNumber) {
    fetch(`../../php/auth/index.php?action=getAdminDetail&id_number=${encodeURIComponent(idNumber)}`)
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

        // Fallback for single full name element if present
        setVal("formName", admin.name || ((admin.first_name || "") + " " + (admin.last_name || "")).trim());

        // Personal Info
        setVal("formFirstName", admin.first_name);
        setVal("formMiddleName", admin.middle_name);
        setVal("formLastName", admin.last_name);
        setVal("formExtension", admin.extension);
        setVal("formBirthdate", admin.birthdate);
        setVal("formAge", admin.age);
        setVal("formGender", admin.gender);

        // Address Info
        setVal("formStreet", admin.street);
        setVal("formBarangay", admin.barangay);
        setVal("formCity", admin.city);
        setVal("formProvince", admin.province);
        setVal("formCountry", admin.country);
        setVal("formZip", admin.zip);

        // Security Questions hidden on edit
        const secTitle = document.getElementById("securitySectionTitle");
        const secGrid = document.getElementById("securitySectionGrid");
        if (secTitle) secTitle.style.display = "none";
        if (secGrid) secGrid.style.display = "none";
        ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });

        // Account Info
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

    // Validate all visible form inputs
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
          loadAdmins();
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

  // View Details Modal
  window.viewAdmin = function (idNumber) {
    fetch(`../../php/auth/index.php?action=getAdminDetail&id_number=${encodeURIComponent(idNumber)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Failed to load admin details.");
          return;
        }

        const admin = res.data;
        document.getElementById("viewIdNumber").textContent = admin.id_number;
        document.getElementById("viewName").textContent = admin.name;
        document.getElementById("viewUsername").textContent = "@" + admin.username;
        document.getElementById("viewEmail").textContent = admin.email || "N/A";
        document.getElementById("viewRole").textContent = admin.role.toUpperCase();
        document.getElementById("viewStatus").textContent = admin.status.toUpperCase();
        document.getElementById("viewCreated").textContent = admin.created_at || "N/A";

        viewModal.classList.add("show");
      });
  };

  function closeViewModal() {
    viewModal.classList.remove("show");
  }

  // Confirmation Dialog Handlers
  window.confirmToggleBlock = function (idNumber, name, currentStatus) {
    const isBlocking = currentStatus !== "block" && currentStatus !== "blocked";
    const actionName = isBlocking ? "Block" : "Unblock";
    const nextStatus = isBlocking ? "block" : "active";

    confirmModalTitle.textContent = `${actionName} Admin Account`;
    confirmModalMessage.innerHTML = `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> admin account <strong>${escapeHtml(
      name
    )}</strong> (<code>${escapeHtml(idNumber)}</code>)?`;

    confirmModalBtn.className = isBlocking ? "btn-danger" : "btn-primary";
    confirmModalBtn.textContent = `${actionName} Admin`;

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", idNumber);
      body.append("status", nextStatus);

      fetch("../../php/auth/index.php?action=toggleBlockAdmin", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            loadAdmins();
          } else {
            alert(res.message || "Failed to update status.");
          }
        });
    };

    confirmModal.classList.add("show");
  };

  window.confirmDeleteAdmin = function (idNumber, name) {
    confirmModalTitle.textContent = "Delete Admin Account";
    confirmModalMessage.innerHTML = `Are you sure you want to permanently <strong>DELETE</strong> admin account <strong>${escapeHtml(
      name
    )}</strong> (<code>${escapeHtml(idNumber)}</code>)?<br><br><span style="color:#dc2626; font-size: 0.85rem;">Warning: This action cannot be undone.</span>`;

    confirmModalBtn.className = "btn-danger";
    confirmModalBtn.textContent = "Delete Permanently";

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", idNumber);

      fetch("../../php/auth/index.php?action=deleteAdmin", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            loadAdmins();
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

  // Utility function
  function escapeHtml(text) {
    if (!text) return "";
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // Dropdown helpers
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

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".action-dropdown")) {
      closeAllDropdowns();
    }
  });
});
