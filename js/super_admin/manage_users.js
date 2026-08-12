document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;
  let currentLimit = 10;
  let currentSearch = "";
  let currentStatus = "all";
  let searchTimeout = null;

  // DOM Elements
  const userTableBody = document.getElementById("userTableBody");
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  // Modals
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

  // Initialize
  loadUsers();

  // Event Listeners
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        currentPage = 1;
        loadUsers();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", function () {
      currentStatus = this.value;
      currentPage = 1;
      loadUsers();
    });
  }

  if (entriesSelect) {
    entriesSelect.addEventListener("change", function () {
      currentLimit = parseInt(this.value, 10);
      currentPage = 1;
      loadUsers();
    });
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

  // Core Load Function
  function loadUsers() {
    if (!userTableBody) return;

    userTableBody.innerHTML = `
      <tr>
        <td colspan="8" class="empty-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading user accounts...</p>
        </td>
      </tr>`;

    const url = `../../php/auth/index.php?action=getUsers&search=${encodeURIComponent(
      currentSearch
    )}&status=${encodeURIComponent(
      currentStatus
    )}&page=${currentPage}&limit=${currentLimit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          showEmptyState(res.message || "Failed to load users.");
          return;
        }
        renderTable(res.data, res.currentPage, res.limit);
        renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
      })
      .catch((err) => {
        console.error(err);
        showEmptyState("An error occurred while fetching user accounts.");
      });
  }

  function renderTable(users, page, limit) {
    if (!users || users.length === 0) {
      showEmptyState("No user accounts found matching your criteria.");
      return;
    }

    const startIdx = (page - 1) * limit;
    let html = "";

    users.forEach((user, index) => {
      const rowId = startIdx + index + 1;
      const isBlocked = user.status === "block" || user.status === "blocked";
      const statusBadge = isBlocked
        ? `<span class="badge-status badge-blocked">Blocked</span>`
        : `<span class="badge-status badge-active">Active</span>`;

      const blockBtnText = isBlocked ? "Unblock" : "Block";
      const blockIcon = isBlocked ? "fa-solid fa-unlock" : "fa-solid fa-ban";
      const blockClass = isBlocked ? "unblock" : "block";

      html += `
        <tr>
          <td><strong>#${rowId}</strong></td>
          <td><code>${escapeHtml(user.id_number)}</code></td>
          <td><strong>${escapeHtml(user.name)}</strong></td>
          <td>@${escapeHtml(user.username)}</td>
          <td>${escapeHtml(user.email || "N/A")}</td>
          <td><span class="badge-role-user">User</span></td>
          <td>${statusBadge}</td>
          <td>
            <div class="action-buttons">
              <button class="btn-action view" title="View Details" onclick="viewUser('${escapeHtml(user.id_number)}')">
                <i class="fa-solid fa-eye"></i>
              </button>
              <button class="btn-action edit" title="Edit User" onclick="editUser('${escapeHtml(user.id_number)}')">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn-action ${blockClass}" title="${blockBtnText} User" onclick="confirmToggleBlock('${escapeHtml(user.id_number)}', '${escapeHtml(user.name)}', '${user.status}')">
                <i class="${blockIcon}"></i>
              </button>
              <button class="btn-action delete" title="Delete User" onclick="confirmDeleteUser('${escapeHtml(user.id_number)}', '${escapeHtml(user.name)}')">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>`;
    });

    userTableBody.innerHTML = html;
  }


  function showEmptyState(msg) {
    userTableBody.innerHTML = `
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

    controlsHtml += `<button class="page-btn" ${page <= 1 ? "disabled" : ""} onclick="changePage(${page - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;

    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
        controlsHtml += `<button class="page-btn ${i === page ? "active" : ""}" onclick="changePage(${i})">${i}</button>`;
      } else if (i === page - 2 || i === page + 2) {
        controlsHtml += `<span style="padding: 0 4px; color: var(--farm-muted);">...</span>`;
      }
    }

    controlsHtml += `<button class="page-btn" ${page >= totalPages ? "disabled" : ""} onclick="changePage(${page + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;

    paginationControls.innerHTML = controlsHtml;
  }

  window.changePage = function (newPage) {
    currentPage = newPage;
    loadUsers();
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

  // Add / Edit Modal
  function openAddModal() {
    if (userForm) userForm.reset();
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

    if (userModalTitle) userModalTitle.textContent = "Add New User";
    if (userModal) userModal.classList.add("show");
  }

  window.editUser = function (idNumber) {
    fetch(`../../php/auth/index.php?action=getUserDetail&id_number=${encodeURIComponent(idNumber)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Could not fetch user details.");
          return;
        }

        const user = res.data;
        if (userForm) userForm.reset();
        setVal("formMode", "edit");

        const idEl = document.getElementById("formIdNumber");
        if (idEl) {
          idEl.value = user.id_number || "";
          idEl.setAttribute("readonly", true);
        }

        // Fallback for single full name element if present
        setVal("formName", user.name || ((user.first_name || "") + " " + (user.last_name || "")).trim());

        // Personal Info
        setVal("formFirstName", user.first_name);
        setVal("formMiddleName", user.middle_name);
        setVal("formLastName", user.last_name);
        setVal("formExtension", user.extension);
        setVal("formBirthdate", user.birthdate);
        setVal("formAge", user.age);
        setVal("formGender", user.gender);

        // Address Info
        setVal("formStreet", user.street);
        setVal("formBarangay", user.barangay);
        setVal("formCity", user.city);
        setVal("formProvince", user.province);
        setVal("formCountry", user.country);
        setVal("formZip", user.zip);

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
  }

  function handleUserFormSubmit(e) {
    e.preventDefault();

    const mode = document.getElementById("formMode").value;
    const formData = new FormData(userForm);

    if (mode === "add") {
      const password = formData.get("password");
      const confirm = formData.get("confirm_password");
      if (password !== confirm) {
        alert("Passwords do not match!");
        return;
      }
    }

    const action = mode === "add" ? "addStandardUser" : "updateStandardUser";

    fetch(`../../php/auth/index.php?action=${action}`, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          alert(res.message);
          closeUserModal();
          loadUsers();
        } else {
          alert(res.message || "Operation failed.");
        }
      })
      .catch((err) => {
        console.error(err);
        alert("An error occurred during form submission.");
      });
  }

  // View Details Modal
  window.viewUser = function (idNumber) {
    fetch(`../../php/auth/index.php?action=getUserDetail&id_number=${encodeURIComponent(idNumber)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          alert(res.message || "Failed to load user details.");
          return;
        }

        const user = res.data;
        document.getElementById("viewIdNumber").textContent = user.id_number;
        document.getElementById("viewName").textContent = user.name;
        document.getElementById("viewUsername").textContent = "@" + user.username;
        document.getElementById("viewEmail").textContent = user.email || "N/A";
        document.getElementById("viewRole").textContent = user.role.toUpperCase();
        document.getElementById("viewStatus").textContent = user.status.toUpperCase();
        document.getElementById("viewCreated").textContent = user.created_at || "N/A";

        viewModal.classList.add("show");
      });
  };

  function closeViewModal() {
    viewModal.classList.remove("show");
  }

  // Confirmation Dialogs
  window.confirmToggleBlock = function (idNumber, name, currentStatus) {
    const isBlocking = currentStatus !== "block" && currentStatus !== "blocked";
    const actionName = isBlocking ? "Block" : "Unblock";
    const nextStatus = isBlocking ? "block" : "active";

    confirmModalTitle.textContent = `${actionName} User Account`;
    confirmModalMessage.innerHTML = `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> user account <strong>${escapeHtml(
      name
    )}</strong> (<code>${escapeHtml(idNumber)}</code>)?`;

    confirmModalBtn.className = isBlocking ? "btn-danger" : "btn-primary";
    confirmModalBtn.textContent = `${actionName} User`;

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", idNumber);
      body.append("status", nextStatus);

      fetch("../../php/auth/index.php?action=toggleBlockUser", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            loadUsers();
          } else {
            alert(res.message || "Failed to update status.");
          }
        });
    };

    confirmModal.classList.add("show");
  };

  window.confirmDeleteUser = function (idNumber, name) {
    confirmModalTitle.textContent = "Delete User Account";
    confirmModalMessage.innerHTML = `Are you sure you want to permanently <strong>DELETE</strong> user account <strong>${escapeHtml(
      name
    )}</strong> (<code>${escapeHtml(idNumber)}</code>)?<br><br><span style="color:#dc2626; font-size: 0.85rem;">Warning: This action cannot be undone.</span>`;

    confirmModalBtn.className = "btn-danger";
    confirmModalBtn.textContent = "Delete Permanently";

    activeConfirmCallback = function () {
      const body = new FormData();
      body.append("id_number", idNumber);

      fetch("../../php/auth/index.php?action=deleteStandardUser", {
        method: "POST",
        body: body,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            loadUsers();
          } else {
            alert(res.message || "Failed to delete user.");
          }
        });
    };

    confirmModal.classList.add("show");
  };

  function closeConfirmModal() {
    confirmModal.classList.remove("show");
    activeConfirmCallback = null;
  }

  // Utility
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
