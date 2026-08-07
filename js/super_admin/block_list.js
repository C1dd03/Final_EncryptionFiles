document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;
  let currentLimit = 10;
  let currentSearch = "";
  let currentStatus = "blocked"; // Default filter is Blocked
  let searchTimeout = null;

  // DOM Elements
  const blockTableBody = document.getElementById("blockTableBody");
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  // Detail View Modal Elements
  const viewModal = document.getElementById("viewModal");
  const closeViewModalBtn = document.getElementById("closeViewModalBtn");
  const viewRecordId = document.getElementById("viewRecordId");
  const viewIdNumber = document.getElementById("viewIdNumber");
  const viewName = document.getElementById("viewName");
  const viewUsername = document.getElementById("viewUsername");
  const viewEmail = document.getElementById("viewEmail");
  const viewRole = document.getElementById("viewRole");
  const viewBlockedBy = document.getElementById("viewBlockedBy");
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

  // Initial Load
  loadBlockList();

  // Event Listeners
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        currentPage = 1;
        loadBlockList();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.value = currentStatus; // Set default filter to 'blocked'
    statusFilter.addEventListener("change", function () {
      currentStatus = this.value;
      currentPage = 1;
      loadBlockList();
    });
  }

  if (entriesSelect) {
    entriesSelect.value = currentLimit;
    entriesSelect.addEventListener("change", function () {
      currentLimit = parseInt(this.value, 10);
      currentPage = 1;
      loadBlockList();
    });
  }

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

  // Load Block List
  function loadBlockList() {
    if (!blockTableBody) return;

    blockTableBody.innerHTML = `
      <tr>
        <td colspan="9" class="empty-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <p>Loading block list records...</p>
        </td>
      </tr>
    `;

    const url = `../../php/auth/index.php?action=getBlockList&search=${encodeURIComponent(
      currentSearch
    )}&status=${encodeURIComponent(currentStatus)}&page=${currentPage}&limit=${currentLimit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          renderTable(res.data || []);
          renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
        } else {
          blockTableBody.innerHTML = `
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
        blockTableBody.innerHTML = `
          <tr>
            <td colspan="9" class="empty-state">
              <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
              <p>Connection error. Please try again.</p>
            </td>
          </tr>
        `;
      });
  }

  // Render Block List Table Rows
  function renderTable(data) {
    if (!data || data.length === 0) {
      blockTableBody.innerHTML = `
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
        ? `<button type="button" class="btn-icon btn-unblock btn-unblock-trigger" data-id="${row.id}" data-name="${escapeHtml(nameDisplay)}" title="Unblock Account">
            <i class="fa-solid fa-key"></i> Unblock
           </button>`
        : `<button type="button" class="btn-icon btn-unblock" disabled title="Already Unblocked">
            <i class="fa-solid fa-check"></i> Unblocked
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
            <div class="action-buttons">
              <button type="button" class="btn-icon btn-view btn-view-trigger" data-id="${row.id}" title="View Details">
                <i class="fa-solid fa-eye"></i>
              </button>
              ${unblockBtnHtml}
            </div>
          </td>
        </tr>
      `;
    });

    blockTableBody.innerHTML = rowsHtml;

    // Attach Event Listeners to View Buttons
    document.querySelectorAll(".btn-view-trigger").forEach((btn) => {
      btn.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        openViewModal(id);
      });
    });

    // Attach Event Listeners to Unblock Buttons
    document.querySelectorAll(".btn-unblock-trigger").forEach((btn) => {
      btn.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        openUnblockConfirmation(id);
      });
    });
  }

  // Render Pagination Controls
  function renderPagination(totalRecords, totalPages, page, limit) {
    if (!paginationInfo || !paginationControls) return;

    if (totalRecords === 0) {
      paginationInfo.textContent = "Showing 0 to 0 of 0 entries";
      paginationControls.innerHTML = "";
      return;
    }

    const start = (page - 1) * limit + 1;
    const end = Math.min(page * limit, totalRecords);
    paginationInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

    let controlsHtml = "";

    // Previous Button
    const prevDisabled = page <= 1 ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${prevDisabled}" data-page="${page - 1}">Previous</button>`;

    // Page Numbers
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

    // Next Button
    const nextDisabled = page >= totalPages ? "disabled" : "";
    controlsHtml += `<button type="button" class="page-link ${nextDisabled}" data-page="${page + 1}">Next</button>`;

    paginationControls.innerHTML = controlsHtml;

    // Attach Pagination Click Handlers
    paginationControls.querySelectorAll("button.page-link").forEach((btn) => {
      btn.addEventListener("click", function () {
        if (this.classList.contains("disabled") || this.classList.contains("active")) return;
        const targetPage = parseInt(this.getAttribute("data-page"), 10);
        if (targetPage && targetPage > 0 && targetPage <= totalPages) {
          currentPage = targetPage;
          loadBlockList();
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

    activeConfirmCallback = function () {
      executeUnblock(id);
    };

    if (confirmModal) confirmModal.classList.add("active");
  }

  function closeConfirmModal() {
    if (confirmModal) confirmModal.classList.remove("active");
    activeConfirmCallback = null;
  }

  // Execute Unblock Action AJAX
  function executeUnblock(id) {
    const formData = new FormData();
    formData.append("id", id);

    fetch("../../php/auth/index.php?action=unblockAccount", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          showToast(res.message || "Account unblocked successfully.", "success");
          loadBlockList();
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
