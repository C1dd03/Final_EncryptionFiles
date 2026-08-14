document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;
  let currentLimit = 10;
  let currentSearch = "";
  let currentAction = "all";
  let currentRole = "all";
  let startDate = "";
  let endDate = "";
  let searchTimeout = null;

  // DOM Elements
  const auditTableBody = document.getElementById("auditTableBody");
  const searchInput = document.getElementById("searchInput");
  const actionFilter = document.getElementById("actionFilter");
  const roleFilter = document.getElementById("roleFilter");
  const startDateInput = document.getElementById("startDateInput");
  const endDateInput = document.getElementById("endDateInput");
  const btnClearDate = document.getElementById("btnClearDate");
  const entriesSelect = document.getElementById("entriesSelect");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  // Initialize
  loadAuditLogs();

  // ✅ Real-time polling — reload audit logs every 3 seconds
  setInterval(function () {
    loadAuditLogs(true);
  }, 3000);

  // Event Listeners
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        currentPage = 1;
        loadAuditLogs();
      }, 300);
    });
  }

  if (actionFilter) {
    actionFilter.addEventListener("change", function () {
      currentAction = this.value;
      currentPage = 1;
      loadAuditLogs();
    });
  }

  if (roleFilter) {
    roleFilter.addEventListener("change", function () {
      currentRole = this.value;
      currentPage = 1;
      loadAuditLogs();
    });
  }

  if (startDateInput) {
    startDateInput.addEventListener("change", function () {
      startDate = this.value;
      currentPage = 1;
      loadAuditLogs();
    });
  }

  if (endDateInput) {
    endDateInput.addEventListener("change", function () {
      endDate = this.value;
      currentPage = 1;
      loadAuditLogs();
    });
  }

  if (btnClearDate) {
    btnClearDate.addEventListener("click", function () {
      if (startDateInput) startDateInput.value = "";
      if (endDateInput) endDateInput.value = "";
      startDate = "";
      endDate = "";
      currentPage = 1;
      loadAuditLogs();
    });
  }

  if (entriesSelect) {
    entriesSelect.value = currentLimit;
    entriesSelect.addEventListener("change", function () {
      currentLimit = parseInt(this.value, 10);
      currentPage = 1;
      loadAuditLogs();
    });
  }

  // Fetch Audit Logs
  // silent = true means polling refresh — no spinner, no flicker
  function loadAuditLogs(silent) {
    if (!auditTableBody) return;

    if (!silent) {
      auditTableBody.innerHTML = `
        <tr>
          <td colspan="8" class="empty-state">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <p>Loading audit log records...</p>
          </td>
        </tr>
      `;
    }

    const url = `../../php/auth/index.php?action=getAuditLogs&search=${encodeURIComponent(
      currentSearch
    )}&action_filter=${encodeURIComponent(currentAction)}&role_filter=${encodeURIComponent(
      currentRole
    )}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(
      endDate
    )}&page=${currentPage}&limit=${currentLimit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (res.success) {
          if (res.restricted) {
            // Admin without audit log privileges
            auditTableBody.innerHTML = `
              <tr>
                <td colspan="8" class="empty-state">
                  <i class="fa-solid fa-file-shield"></i>
                  <p>${escapeHtml(res.message || "Access Restricted")}</p>
                </td>
              </tr>
            `;
            if (paginationInfo) paginationInfo.textContent = "Showing 0 to 0 of 0 entries";
            if (paginationControls) paginationControls.innerHTML = "";
            return;
          }
          renderTable(res.data || []);
          renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
        } else {
          auditTableBody.innerHTML = `
            <tr>
              <td colspan="8" class="empty-state">
                <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
                <p>${escapeHtml(res.message || "Failed to load audit logs.")}</p>
              </td>
            </tr>
          `;
        }
      })
      .catch((err) => {
        console.error("Error fetching audit logs:", err);
        auditTableBody.innerHTML = `
          <tr>
            <td colspan="8" class="empty-state">
              <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
              <p>Connection error. Please try again.</p>
            </td>
          </tr>
        `;
      });
  }

  // Render Audit Log Table Rows
  function renderTable(data) {
    if (!data || data.length === 0) {
      auditTableBody.innerHTML = `
        <tr>
          <td colspan="8" class="empty-state">
            <i class="fa-solid fa-clipboard-list"></i>
            <p>No audit logs found.</p>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    data.forEach((row) => {
      const idDisplay = row.id ? `#${row.id}` : "-";
      const userIdDisplay = row.id_number || "-";
      const usernameDisplay = row.username || "-";
      const roleBadge = formatRoleBadge(row.role);
      const actionBadge = formatActionBadge(row.action);
      const detailsDisplay = row.details || "-";
      const timeInDisplay = formatDate(row.time_in);
      const timeOutDisplay = row.time_out ? formatDate(row.time_out) : "NULL";

      rowsHtml += `
        <tr>
          <td><strong>${escapeHtml(idDisplay)}</strong></td>
          <td>${escapeHtml(userIdDisplay)}</td>
          <td>${escapeHtml(usernameDisplay)}</td>
          <td>${roleBadge}</td>
          <td>${actionBadge}</td>
          <td class="details-cell">${escapeHtml(detailsDisplay)}</td>
          <td>${escapeHtml(timeInDisplay)}</td>
          <td>${escapeHtml(timeOutDisplay)}</td>
        </tr>
      `;
    });

    auditTableBody.innerHTML = rowsHtml;
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
          loadAuditLogs();
        }
      });
    });
  }

  // Helper Badge Formatter
  function formatActionBadge(action) {
    if (!action) return `<span class="action-badge badge-default">-</span>`;

    const actLower = action.toLowerCase();
    let badgeClass = "badge-default";
    let iconClass = "fa-circle-info";

    if (actLower === "login") {
      badgeClass = "badge-login";
      iconClass = "fa-right-to-bracket";
    } else if (actLower === "logout") {
      badgeClass = "badge-logout";
      iconClass = "fa-right-from-bracket";
    } else if (actLower.includes("create")) {
      badgeClass = "badge-create";
      iconClass = "fa-user-plus";
    } else if (actLower.includes("update")) {
      badgeClass = "badge-update";
      iconClass = "fa-user-pen";
    } else if (actLower.includes("delete")) {
      badgeClass = "badge-delete";
      iconClass = "fa-user-minus";
    } else if (actLower.includes("block") && !actLower.includes("unblock")) {
      badgeClass = "badge-block";
      iconClass = "fa-user-slash";
    } else if (actLower.includes("unblock")) {
      badgeClass = "badge-unblock";
      iconClass = "fa-user-check";
    }

    return `<span class="action-badge ${badgeClass}"><i class="fa-solid ${iconClass}"></i> ${escapeHtml(action)}</span>`;
  }

  function formatRoleBadge(role) {
    if (!role) return `<span class="role-badge role-user">User</span>`;
    const r = role.toLowerCase();
    if (r === "superadmin" || r === "super admin") {
      return `<span class="role-badge role-superadmin">Super Admin</span>`;
    } else if (r === "admin") {
      return `<span class="role-badge role-admin">Admin</span>`;
    } else {
      return `<span class="role-badge role-user">User</span>`;
    }
  }

  function formatDate(dateTimeStr) {
    if (!dateTimeStr) return "-";
    try {
      // PHP/MySQL returns datetime as "YYYY-MM-DD HH:mm:ss" in Philippine local time.
      // Parse directly without timezone conversion so the displayed time matches
      // exactly what was stored (Asia/Manila).
      const normalized = dateTimeStr.trim().replace(' ', 'T');
      const parts = normalized.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/);
      if (!parts) return dateTimeStr;
      const [, yyyy, mm, dd, hh, min, ss] = parts;

      const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];
      const monthName = monthNames[parseInt(mm, 10) - 1];
      const day = parseInt(dd, 10);
      const hour24 = parseInt(hh, 10);
      const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12;
      const ampm = hour24 < 12 ? "AM" : "PM";

      return `${monthName} ${day}, ${yyyy} ${hour12}:${min}:${ss} ${ampm}`;
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
});
