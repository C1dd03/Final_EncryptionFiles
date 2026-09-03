document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const monthFilter = document.getElementById("monthFilter");
  const yearFilter = document.getElementById("yearFilter");
  const startDateInput = document.getElementById("startDateInput");
  const endDateInput = document.getElementById("endDateInput");
  const btnClearDate = document.getElementById("btnClearDate");
  const entriesSelect = document.getElementById("entriesSelect");

  const tableBody = document.getElementById("approvalsTableBody");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  const detailsModal = document.getElementById("detailsModal");
  const detailsModalBody = document.getElementById("detailsModalBody");

  const confirmModal = document.getElementById("confirmModal");
  const confirmModalTitle = document.getElementById("confirmModalTitle");
  const confirmModalMsg = document.getElementById("confirmModalMsg");
  const rejectReasonGroup = document.getElementById("rejectReasonGroup");
  const rejectReasonInput = document.getElementById("rejectReasonInput");
  const approvalPasswordGroup = document.getElementById("approvalPasswordGroup");
  const approvalOperatorPassword = document.getElementById("approvalOperatorPassword");
  const confirmModalSubmitBtn = document.getElementById("confirmModalSubmitBtn");

  let currentPage = 1;
  let currentPendingData = [];
  let pendingAction = null; // { action: 'approve' | 'reject', id_number: string, name: string }

  function escapeHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showToast(msg, type = "info") {
    const container = document.getElementById("toastContainer");
    if (!container) return;
    const toast = document.createElement("div");
    toast.style.padding = "12px 18px";
    toast.style.marginTop = "8px";
    toast.style.borderRadius = "8px";
    toast.style.color = "#fff";
    toast.style.fontSize = "13px";
    toast.style.fontWeight = "500";
    toast.style.boxShadow = "0 4px 6px -1px rgba(0,0,0,0.1)";
    toast.style.display = "flex";
    toast.style.alignItems = "center";
    toast.style.gap = "8px";

    if (type === "success") {
      toast.style.background = "#10b981";
      toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${escapeHtml(msg)}</span>`;
    } else if (type === "error") {
      toast.style.background = "#ef4444";
      toast.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>${escapeHtml(msg)}</span>`;
    } else {
      toast.style.background = "#3b82f6";
      toast.innerHTML = `<i class="fa-solid fa-circle-info"></i> <span>${escapeHtml(msg)}</span>`;
    }

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = "opacity 0.3s";
      toast.style.opacity = "0";
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function fetchPendingRegistrations() {
    const search = searchInput ? searchInput.value.trim() : "";
    const status = statusFilter ? statusFilter.value : "all";
    const month = monthFilter ? monthFilter.value : "all";
    const year = yearFilter ? yearFilter.value : "all";
    const startDate = startDateInput ? startDateInput.value : "";
    const endDate = endDateInput ? endDateInput.value : "";
    const limit = entriesSelect ? entriesSelect.value : 10;

    tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Loading pending registrations...</td></tr>`;

    const url = `../../php/auth/index.php?action=getPendingRegistrations&search=${encodeURIComponent(
      search
    )}&status=${encodeURIComponent(status)}&month=${encodeURIComponent(month)}&year=${encodeURIComponent(
      year
    )}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(
      endDate
    )}&page=${currentPage}&limit=${limit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px; color:#ef4444;">${escapeHtml(
            res.message || "Failed to load registrations."
          )}</td></tr>`;
          return;
        }

        currentPendingData = res.data || [];
        renderTable(currentPendingData);
        renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
      })
      .catch((err) => {
        console.error("Error fetching approvals:", err);
        tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px; color:#ef4444;">An unexpected error occurred while fetching registrations.</td></tr>`;
      });
  }

  function renderTable(data) {
    if (!data || data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:35px; color:#64748b;">
        <i class="fa-solid fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; color:#94a3b8;"></i>
        No pending registration approvals found.
      </td></tr>`;
      return;
    }

    let html = "";
    data.forEach((user) => {
      const idNo = escapeHtml(user.id_number || "-");
      const fullName = escapeHtml(user.full_name || user.username || "-");
      const username = escapeHtml(user.username || "-");
      const email = escapeHtml(user.email || "-");
      const genderAge = `${escapeHtml(user.gender || "")} (${escapeHtml(String(user.age || "-"))})`;
      const createdAt = escapeHtml(user.created_at || "-");
      const regStatus = escapeHtml(user.status || "pending");

      const isPending = regStatus === "pending";
      const isApproved = regStatus === "approved";
      const isRejected = regStatus === "rejected";

      let statusBadge = "";
      if (isPending) {
        statusBadge = `<span class="badge-pending"><i class="fa-regular fa-clock"></i> Pending</span>`;
      } else if (isApproved) {
        statusBadge = `<span style="background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:9999px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-check"></i> Approved</span>`;
      } else if (isRejected) {
        statusBadge = `<span style="background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:9999px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>`;
      } else {
        statusBadge = `<span class="badge-pending"><i class="fa-regular fa-clock"></i> ${escapeHtml(regStatus)}</span>`;
      }

      html += `<tr>
        <td><strong>${idNo}</strong></td>
        <td>${fullName}</td>
        <td>${username}</td>
        <td>${email}</td>
        <td>${genderAge}</td>
        <td>${createdAt}</td>
        <td>${statusBadge}</td>
        <td>
          <div class="action-dropdown">
            <button type="button" class="action-dropdown-btn" onclick="toggleActionDropdown(this)">
              Options <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
            </button>
            <div class="action-dropdown-menu">
              <button type="button" class="action-menu-item view" onclick="viewUserDetails('${idNo}')">
                <i class="fa-solid fa-eye"></i> View Details
              </button>
              ${isPending ? `<div class="action-menu-divider"></div>
              <button type="button" class="action-menu-item approve" onclick="confirmApprove('${idNo}', '${fullName}')">
                <i class="fa-solid fa-check"></i> Approve
              </button>
              <button type="button" class="action-menu-item reject" onclick="confirmReject('${idNo}', '${fullName}')">
                <i class="fa-solid fa-xmark"></i> Reject
              </button>` : ""}
            </div>
          </div>
        </td>
      </tr>`;
    });

    tableBody.innerHTML = html;
  }

  /* ── Dropdown Toggle Logic ── */
  window.toggleActionDropdown = function (btn) {
    const menu = btn.nextElementSibling;
    const isOpen = menu.classList.contains("open");

    // Close all open dropdowns first
    document.querySelectorAll(".action-dropdown-menu.open").forEach((m) => m.classList.remove("open"));
    document.querySelectorAll(".action-dropdown-btn.active").forEach((b) => b.classList.remove("active"));

    if (!isOpen) {
      menu.classList.add("open");
      btn.classList.add("active");
    }
  };

  // Close dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".action-dropdown")) {
      document.querySelectorAll(".action-dropdown-menu.open").forEach((m) => m.classList.remove("open"));
      document.querySelectorAll(".action-dropdown-btn.active").forEach((b) => b.classList.remove("active"));
    }
  });

  function renderPagination(totalRecords, totalPages, curPage, limit) {
    const start = totalRecords > 0 ? (curPage - 1) * limit + 1 : 0;
    const end = Math.min(curPage * limit, totalRecords);
    paginationInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

    let controlsHtml = "";
    if (totalPages > 1) {
      controlsHtml += `<button type="button" class="page-btn" ${
        curPage === 1 ? "disabled" : ""
      } onclick="goToPage(${curPage - 1})"><i class="fa-solid fa-angle-left"></i> Prev</button>`;

      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= curPage - 2 && i <= curPage + 2)) {
          controlsHtml += `<button type="button" class="page-btn ${
            i === curPage ? "active" : ""
          }" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === curPage - 3 || i === curPage + 3) {
          controlsHtml += `<span style="padding:4px 8px;">...</span>`;
        }
      }

      controlsHtml += `<button type="button" class="page-btn" ${
        curPage === totalPages ? "disabled" : ""
      } onclick="goToPage(${curPage + 1})">Next <i class="fa-solid fa-angle-right"></i></button>`;
    }
    paginationControls.innerHTML = controlsHtml;
  }

  window.goToPage = function (page) {
    currentPage = page;
    fetchPendingRegistrations();
  };

  window.viewUserDetails = function (idNumber) {
    const user = currentPendingData.find((u) => u.id_number === idNumber);
    if (!user) return;

    const address = [
      user.purok_street,
      user.barangay,
      user.city_municipality,
      user.province,
      user.country,
      user.zip_code,
    ]
      .filter(Boolean)
      .join(", ");

    detailsModalBody.innerHTML = `
      <div class="detail-grid">
        <div class="detail-item"><div class="detail-label">ID Number</div><div class="detail-val">${escapeHtml(user.id_number)}</div></div>
        <div class="detail-item"><div class="detail-label">Full Name</div><div class="detail-val">${escapeHtml(user.full_name)}</div></div>
        <div class="detail-item"><div class="detail-label">Username</div><div class="detail-val">${escapeHtml(user.username)}</div></div>
        <div class="detail-item"><div class="detail-label">Email Address</div><div class="detail-val">${escapeHtml(user.email)}</div></div>
        <div class="detail-item"><div class="detail-label">Birthdate</div><div class="detail-val">${escapeHtml(user.birthdate)} (Age: ${escapeHtml(String(user.age))})</div></div>
        <div class="detail-item"><div class="detail-label">Gender</div><div class="detail-val">${escapeHtml(user.gender)}</div></div>
        <div class="detail-item" style="grid-column: 1 / -1;"><div class="detail-label">Address</div><div class="detail-val">${escapeHtml(address || "Not specified")}</div></div>
        <div class="detail-item"><div class="detail-label">Submitted On</div><div class="detail-val">${escapeHtml(user.created_at)}</div></div>
        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-val">${escapeHtml(user.status || "pending") === "pending" ? '<span class="badge-pending"><i class="fa-regular fa-clock"></i> Pending</span>' : escapeHtml(user.status)}</div></div>
      </div>
    `;
    detailsModal.style.display = "flex";
  };

  window.closeDetailsModal = function () {
    detailsModal.style.display = "none";
  };

  window.confirmApprove = function (idNumber, name) {
    pendingAction = { action: "approve", id_number: idNumber, name: name };
    confirmModalTitle.textContent = "Approve Registration";
    confirmModalMsg.innerHTML = `Are you sure you want to approve the registration for <strong>${escapeHtml(
      name
    )}</strong> (ID: ${escapeHtml(idNumber)})? The account will be activated immediately.`;
    rejectReasonGroup.style.display = "none";
    approvalPasswordGroup.style.display = "none";
    approvalOperatorPassword.value = "";
    confirmModalSubmitBtn.style.background = "#10b981";
    confirmModalSubmitBtn.textContent = "Yes, Continue";
    confirmModal.style.display = "flex";
  };

  window.confirmReject = function (idNumber, name) {
    pendingAction = { action: "reject", id_number: idNumber, name: name };
    confirmModalTitle.textContent = "Reject Registration";
    confirmModalMsg.innerHTML = `Are you sure you want to reject the registration for <strong>${escapeHtml(
      name
    )}</strong> (ID: ${escapeHtml(idNumber)})? This will mark the registration as rejected.`;
    rejectReasonGroup.style.display = "block";
    approvalPasswordGroup.style.display = "none";
    rejectReasonInput.value = "";
    confirmModalSubmitBtn.style.background = "#ef4444";
    confirmModalSubmitBtn.textContent = "Reject & Remove";
    confirmModal.style.display = "flex";
  };

  window.closeConfirmModal = function () {
    confirmModal.style.display = "none";
    pendingAction = null;
  };

  confirmModalSubmitBtn.addEventListener("click", function () {
    if (!pendingAction) return;

    if (pendingAction.action === "approve") {
      if (approvalPasswordGroup.style.display === "none") {
        approvalPasswordGroup.style.display = "block";
        confirmModalSubmitBtn.textContent = "Verify & Approve";
        approvalOperatorPassword.focus();
        return;
      }
      if (!approvalOperatorPassword.value) {
        alert("Enter your current password to approve this account.");
        approvalOperatorPassword.focus();
        return;
      }
      confirmModalSubmitBtn.disabled = true;
      confirmModalSubmitBtn.textContent = "Approving...";

      const formData = new FormData();
      formData.append("id_number", pendingAction.id_number);
      formData.append("operator_password", approvalOperatorPassword.value);

      fetch("../../php/auth/index.php?action=approveRegistration", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          if (res.success) {
            showToast(res.message || "Registration approved successfully!", "success");
            fetchPendingRegistrations();
          } else {
            showToast(res.message || "Failed to approve registration.", "error");
          }
        })
        .catch((err) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          console.error("Approve error:", err);
          showToast("An error occurred.", "error");
        });
    } else if (pendingAction.action === "reject") {
      const reason = rejectReasonInput.value.trim();
      if (!reason) {
        alert("Please provide a reason for rejecting this registration.");
        rejectReasonInput.focus();
        return;
      }

      confirmModalSubmitBtn.disabled = true;
      confirmModalSubmitBtn.textContent = "Rejecting...";

      const formData = new FormData();
      formData.append("id_number", pendingAction.id_number);
      formData.append("reason", reason);

      fetch("../../php/auth/index.php?action=rejectRegistration", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          if (res.success) {
            showToast(res.message || "Registration rejected.", "success");
            fetchPendingRegistrations();
          } else {
            showToast(res.message || "Failed to reject registration.", "error");
          }
        })
        .catch((err) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          console.error("Reject error:", err);
          showToast("An error occurred.", "error");
        });
    }
  });

  // Filter Listeners
  let debounceTimeout;
  searchInput.addEventListener("input", function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      currentPage = 1;
      fetchPendingRegistrations();
    }, 300);
  });

  statusFilter.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  monthFilter.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  yearFilter.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  startDateInput.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  endDateInput.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  btnClearDate.addEventListener("click", function () {
    searchInput.value = "";
    statusFilter.value = "all";
    monthFilter.value = "all";
    yearFilter.value = "all";
    startDateInput.value = "";
    endDateInput.value = "";
    currentPage = 1;
    fetchPendingRegistrations();
  });

  entriesSelect.addEventListener("change", function () {
    currentPage = 1;
    fetchPendingRegistrations();
  });

  // Initial Load
  fetchPendingRegistrations();
});
