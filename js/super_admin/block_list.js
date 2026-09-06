document.addEventListener("DOMContentLoaded", function () {
  // Separate state per section: Blocked Admins vs Blocked Users
  const adminState = { page: 1 };
  const userState = { page: 1 };
  let currentLimit = 10;
  let currentSearch = "";
  let searchTimeout = null;

  // Shared controls
  const searchInput = document.getElementById("searchInput");
  const entriesSelect = document.getElementById("entriesSelect");

  // Blocked Admins table
  const blockAdminTableBody = document.getElementById("blockAdminTableBody");
  const adminPaginationInfo = document.getElementById("adminPaginationInfo");
  const adminPaginationControls = document.getElementById("adminPaginationControls");

  // Blocked Users table
  const blockUserTableBody = document.getElementById("blockUserTableBody");
  const userPaginationInfo = document.getElementById("userPaginationInfo");
  const userPaginationControls = document.getElementById("userPaginationControls");

  // Detail View Modal Elements
  const viewModal = document.getElementById("viewModal");
  const closeViewModalBtn = document.getElementById("closeViewModalBtn");
  const closeViewModalFooterBtn = document.getElementById("closeViewModalFooterBtn");
  const viewRecordId = document.getElementById("viewRecordId");
  const viewIdNumber = document.getElementById("viewIdNumber");
  const viewName = document.getElementById("viewName");
  const viewUsername = document.getElementById("viewUsername");
  const viewEmail = document.getElementById("viewEmail");
  const viewRole = document.getElementById("viewRole");
  const viewBlockedBy = document.getElementById("viewBlockedBy");
  const viewReason = document.getElementById("viewReason");
  const viewBlockedAt = document.getElementById("viewBlockedAt");
  const viewStatus = document.getElementById("viewStatus");

  // Confirmation Modal Elements
  const confirmModal = document.getElementById("confirmModal");
  const confirmModalTitle = document.getElementById("confirmModalTitle");
  const confirmModalMessage = document.getElementById("confirmModalMessage");
  const confirmModalBtn = document.getElementById("confirmModalBtn");
  const cancelConfirmModalBtn = document.getElementById("cancelConfirmModalBtn");
  const closeConfirmModalBtn = document.getElementById("closeConfirmModalBtn");

  let activeConfirmCallback = null;
  let unblockConfirmed = false;

  // Initial Load: both sections separately
  loadBlockList("admin");
  loadBlockList("user");

  // Event Listeners
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        adminState.page = 1;
        userState.page = 1;
        loadBlockList("admin");
        loadBlockList("user");
      }, 300);
    });
  }

  if (entriesSelect) {
    entriesSelect.value = currentLimit;
    entriesSelect.addEventListener("change", function () {
      currentLimit = parseInt(this.value, 10);
      adminState.page = 1;
      userState.page = 1;
      loadBlockList("admin");
      loadBlockList("user");
    });
  }

  if (closeViewModalBtn) closeViewModalBtn.addEventListener("click", closeViewModal);
  if (closeViewModalFooterBtn) closeViewModalFooterBtn.addEventListener("click", closeViewModal);
  if (closeConfirmModalBtn) closeConfirmModalBtn.addEventListener("click", closeConfirmModal);
  if (cancelConfirmModalBtn) cancelConfirmModalBtn.addEventListener("click", closeConfirmModal);

  if (confirmModalBtn) {
    confirmModalBtn.addEventListener("click", function () {
      if (!unblockConfirmed) {
        unblockConfirmed = true;
        const group = document.getElementById("unblockPasswordGroup");
        if (group) group.style.display = "block";
        confirmModalBtn.textContent = "Verify & Unblock";
        const password = document.getElementById("unblockOperatorPassword");
        if (password) password.focus();
        return;
      }
      if (activeConfirmCallback) {
        if (activeConfirmCallback() === false) return;
      }
      closeConfirmModal();
    });
  }

  function stateFor(roleFilter) {
    return roleFilter === "admin" ? adminState : userState;
  }

  function bodyFor(roleFilter) {
    return roleFilter === "admin" ? blockAdminTableBody : blockUserTableBody;
  }

  // Load one block list section (admins or users)
  function loadBlockList(roleFilter) {
    const body = bodyFor(roleFilter);
    const state = stateFor(roleFilter);
    if (!body) return;

    body.innerHTML = `
      <tr>
        <td colspan="9" class="empty-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading block list records...</p>
        </td>
      </tr>
    `;

    const url = `../../php/auth/index.php?action=getBlockList&role_filter=${encodeURIComponent(
      roleFilter
    )}&search=${encodeURIComponent(currentSearch)}&status=blocked&page=${state.page}&limit=${currentLimit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          renderTable(roleFilter, res.data || []);
          renderPagination(roleFilter, res.totalRecords, res.totalPages, res.currentPage, res.limit);
        } else {
          body.innerHTML = `
            <tr>
              <td colspan="9" class="empty-state">
                <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
                <p>${escapeHtml(res.message || "Failed to load block list records.")}</p>
              </td>
            </tr>
          `;
        }
      })
      .catch((err) => {
        console.error("Error fetching block list:", err);
        body.innerHTML = `
          <tr>
            <td colspan="9" class="empty-state">
              <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
              <p>Connection error. Please try again.</p>
            </td>
          </tr>
        `;
      });
  }

  // Render rows for one section
  function renderTable(roleFilter, data) {
    const body = bodyFor(roleFilter);
    if (!body) return;

    if (!data || data.length === 0) {
      body.innerHTML = `
        <tr>
          <td colspan="9" class="empty-state">
            <i class="fa-solid fa-user-check"></i>
            <p>No block list records found.</p>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    data.forEach((row) => {
      const isBlocked = (row.status || "").toLowerCase() === "blocked";
      const statusBadge = isBlocked
        ? `<span class="status-badge badge-blocked"><i class="fa-solid fa-lock"></i> Blocked</span>`
        : `<span class="status-badge badge-unblocked"><i class="fa-solid fa-lock-open"></i> Unblocked</span>`;

      const idNoDisplay = row.id_number || "-";
      const nameDisplay = row.name || "-";
      const usernameDisplay = row.username || "-";
      const emailDisplay = row.email || "-";
      const blockedByDisplay = row.blocked_by || "Super Admin";
      const blockedAtDisplay = formatDate(row.blocked_at);

      const unblockBtnHtml = isBlocked
        ? `<button type="button" class="action-menu-item unblock btn-unblock-trigger" data-id="${row.id}" data-name="${escapeHtml(nameDisplay)}">
            <i class="fa-solid fa-key" aria-hidden="true"></i> Unblock Account
           </button>`
        : `<button type="button" class="action-menu-item unblock" disabled title="Already Unblocked">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Unblocked
           </button>`;

      rowsHtml += `
        <tr>
          <td><strong>#${row.id}</strong></td>
          <td>${escapeHtml(idNoDisplay)}</td>
          <td>${escapeHtml(nameDisplay)}</td>
          <td>${escapeHtml(usernameDisplay)}</td>
          <td>${escapeHtml(emailDisplay)}</td>
          <td>${escapeHtml(blockedByDisplay)}</td>
          <td>${escapeHtml(blockedAtDisplay)}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="action-dropdown">
              <button type="button" class="action-dropdown-btn" aria-expanded="false" aria-label="Options for ${escapeHtml(nameDisplay)}">
                Options <i class="fa-solid fa-chevron-down dropdown-chevron" aria-hidden="true"></i>
              </button>
              <div class="action-dropdown-menu">
                <button type="button" class="action-menu-item view btn-view-trigger" data-id="${row.id}">
                  <i class="fa-solid fa-eye" aria-hidden="true"></i> View Details
                </button>
                <div class="action-menu-divider"></div>
                ${unblockBtnHtml}
              </div>
            </div>
          </td>
        </tr>
      `;
    });

    body.innerHTML = rowsHtml;

    // Attach Event Listeners to View Buttons (scoped to this section)
    body.querySelectorAll(".btn-view-trigger").forEach((btn) => {
      btn.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        openViewModal(id);
      });
    });

    // Attach Event Listeners to Unblock Buttons (scoped to this section)
    body.querySelectorAll(".btn-unblock-trigger").forEach((btn) => {
      btn.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        openUnblockConfirmation(id);
      });
    });
  }

  // Render pagination for one section
  function renderPagination(roleFilter, totalRecords, totalPages, page, limit) {
    const info = roleFilter === "admin" ? adminPaginationInfo : userPaginationInfo;
    const controls = roleFilter === "admin" ? adminPaginationControls : userPaginationControls;
    if (!info || !controls) return;

    if (totalRecords === 0) {
      info.textContent = "Showing 0 to 0 of 0 entries";
      controls.innerHTML = "";
      return;
    }

    const start = (page - 1) * limit + 1;
    const end = Math.min(page * limit, totalRecords);
    info.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

    let controlsHtml = "";

    const prevDisabled = page <= 1 ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${prevDisabled}" data-page="${page - 1}">Previous</button>`;

    for (let p = 1; p <= totalPages; p++) {
      if (
        p === 1 ||
        p === totalPages ||
        (p >= page - 2 && p <= page + 2)
      ) {
        const activeClass = p === page ? "active" : "";
        controlsHtml += `<button type="button" class="page-link ${activeClass}" data-page="${p}">${p}</button>`;
      } else if (p === page - 3 || p === page + 3) {
        controlsHtml += `<span class="page-link disabled">...</span>`;
      }
    }

    const nextDisabled = page >= totalPages ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${nextDisabled}" data-page="${page + 1}">Next</button>`;

    controls.innerHTML = controlsHtml;

    controls.querySelectorAll("button.page-link").forEach((btn) => {
      btn.addEventListener("click", function () {
        if (this.classList.contains("disabled") || this.classList.contains("active")) return;
        const targetPage = parseInt(this.getAttribute("data-page"), 10);
        if (targetPage && targetPage > 0 && targetPage <= totalPages) {
          stateFor(roleFilter).page = targetPage;
          loadBlockList(roleFilter);
        }
      });
    });
  }

  // Open View Details Modal
  function openViewModal(id) {
    fetch(`../../php/auth/index.php?action=getBlockDetail&id=${id}`)
      .then((res) => res.json())
      .then((res) => {
        if (res.success && res.data) {
          const d = res.data;
          if (viewRecordId) viewRecordId.textContent = `#${d.id}`;
          if (viewIdNumber) viewIdNumber.textContent = d.id_number || "-";
          if (viewName) viewName.textContent = d.name || "-";
          if (viewUsername) viewUsername.textContent = d.username || "-";
          if (viewEmail) viewEmail.textContent = d.email || "-";
          if (viewRole) viewRole.textContent = (d.role || "-").toUpperCase();
          if (viewBlockedBy) viewBlockedBy.textContent = d.blocked_by || "Super Admin";
          if (viewReason) viewReason.textContent = d.reason || "-";
          if (viewBlockedAt) viewBlockedAt.textContent = formatDate(d.blocked_at);
          if (viewStatus) {
            const isBlocked = (d.status || "").toLowerCase() === "blocked";
            viewStatus.innerHTML = isBlocked
              ? `<span class="status-badge badge-blocked">Blocked</span>`
              : `<span class="status-badge badge-unblocked">Unblocked</span>`;
          }

          if (viewModal) viewModal.classList.add("active");
        } else {
          showToast(res.message || "Failed to load account details.", "error");
        }
      })
      .catch((err) => {
        console.error("Error fetching detail:", err);
        showToast("Error loading account detail.", "error");
      });
  }

  function closeViewModal() {
    if (viewModal) viewModal.classList.remove("active");
  }

  // Open Unblock Confirmation Dialog
  function openUnblockConfirmation(id) {
    if (confirmModalTitle) confirmModalTitle.textContent = "Unblock Account";
    if (confirmModalMessage) {
      confirmModalMessage.textContent = "Are you sure you want to unblock this account?";
    }
    const password = document.getElementById("unblockOperatorPassword");
    if (password) password.value = "";
    const group = document.getElementById("unblockPasswordGroup");
    if (group) group.style.display = "none";
    unblockConfirmed = false;
    if (confirmModalBtn) confirmModalBtn.textContent = "Yes, Continue";

    activeConfirmCallback = function () {
      executeUnblock(id);
    };

    if (confirmModal) confirmModal.classList.add("active");
  }

  function closeConfirmModal() {
    if (confirmModal) confirmModal.classList.remove("active");
    activeConfirmCallback = null;
    unblockConfirmed = false;
  }

  // Execute Unblock Action AJAX
  function executeUnblock(id) {
    const formData = new FormData();
    formData.append("id", id);
    const password = document.getElementById("unblockOperatorPassword");
    if (!password || !password.value) {
      alert("Enter your current password to unblock this account.");
      if (password) password.focus();
      return false;
    }
    formData.append("operator_password", password.value);

    fetch("../../php/auth/index.php?action=unblockAccount", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          showToast(res.message || "Account unblocked successfully.", "success");
          // Refresh both sections
          adminState.page = 1;
          userState.page = 1;
          loadBlockList("admin");
          loadBlockList("user");
        } else {
          showToast(res.message || "Failed to unblock account.", "error");
        }
      })
      .catch((err) => {
        console.error("Error unblocking account:", err);
        showToast("An error occurred while unblocking account.", "error");
      });
  }

  // Helper Utilities
  function formatDate(dateTimeStr) {
    if (!dateTimeStr) return "-";
    try {
      const dt = new Date(dateTimeStr);
      if (isNaN(dt.getTime())) return dateTimeStr;
      return dt.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch (e) {
      return dateTimeStr;
    }
  }

  function escapeHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showToast(message, type = "success") {
    let container = document.querySelector(".toast-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "toast-container";
      document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    const icon = type === "success" ? "fa-circle-check" : "fa-circle-exclamation";
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${escapeHtml(message)}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transition = "opacity 0.3s ease";
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }
});
