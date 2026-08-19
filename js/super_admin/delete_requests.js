document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const entriesSelect = document.getElementById("entriesSelect");

  const tableBody = document.getElementById("requestsTableBody");
  const paginationInfo = document.getElementById("paginationInfo");
  const paginationControls = document.getElementById("paginationControls");

  const detailsModal = document.getElementById("detailsModal");
  const detailsModalBody = document.getElementById("detailsModalBody");

  const confirmModal = document.getElementById("confirmModal");
  const confirmModalTitle = document.getElementById("confirmModalTitle");
  const confirmModalMsg = document.getElementById("confirmModalMsg");
  const rejectNotesGroup = document.getElementById("rejectNotesGroup");
  const rejectNotesInput = document.getElementById("rejectNotesInput");
  const confirmModalSubmitBtn = document.getElementById("confirmModalSubmitBtn");

  let currentPage = 1;
  let currentRequestsData = [];
  let pendingAction = null; // { action: 'approve' | 'reject', requestId: number, username: string }

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

  function fetchDeleteRequests() {
    const search = searchInput ? searchInput.value.trim() : "";
    const status = statusFilter ? statusFilter.value : "pending";
    const limit = entriesSelect ? entriesSelect.value : 10;

    tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Loading deletion requests...</td></tr>`;

    const url = `../../php/auth/index.php?action=getDeleteRequests&search=${encodeURIComponent(
      search
    )}&status=${encodeURIComponent(status)}&page=${currentPage}&limit=${limit}`;

    fetch(url)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px; color:#ef4444;">${escapeHtml(
            res.message || "Failed to load deletion requests."
          )}</td></tr>`;
          return;
        }

        currentRequestsData = res.data || [];
        renderTable(currentRequestsData);
        renderPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
      })
      .catch((err) => {
        console.error("Error fetching delete requests:", err);
        tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px; color:#ef4444;">An unexpected error occurred while fetching requests.</td></tr>`;
      });
  }

  function renderTable(data) {
    if (!data || data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:35px; color:#64748b;">
        <i class="fa-solid fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; color:#94a3b8;"></i>
        No deletion requests found.
      </td></tr>`;
      return;
    }

    let html = "";
    data.forEach((req) => {
      const reqId = escapeHtml(String(req.id));
      const targetId = escapeHtml(req.user_id_number || "-");
      const targetName = escapeHtml(req.user_name || req.user_username || "-");
      const adminName = escapeHtml(req.requested_by_username || "-");
      const reason = escapeHtml(req.reason || "-");
      const requestedAt = escapeHtml(req.requested_at || "-");
      const status = req.status;

      let statusBadge = "";
      if (status === "pending") {
        statusBadge = `<span class="badge-status-pending"><i class="fa-regular fa-clock"></i> Pending</span>`;
      } else if (status === "approved") {
        statusBadge = `<span class="badge-status-approved"><i class="fa-solid fa-check"></i> Deleted</span>`;
      } else if (status === "rejected") {
        statusBadge = `<span class="badge-status-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>`;
      }

      let actionsHtml = `<button type="button" class="btn-view-req" onclick="viewRequestDetails(${reqId})" title="View Details">
        <i class="fa-solid fa-eye"></i> Details
      </button>`;

      if (status === "pending") {
        actionsHtml += `
          <button type="button" class="btn-approve-del" onclick="confirmApproveDelete(${reqId}, '${targetName}')" title="Approve & Permanently Delete">
            <i class="fa-solid fa-trash-can"></i> Delete
          </button>
          <button type="button" class="btn-reject-req" onclick="confirmRejectRequest(${reqId}, '${targetName}')" title="Reject Deletion Request">
            <i class="fa-solid fa-ban"></i> Reject
          </button>
        `;
      }

      html += `<tr>
        <td>#${reqId}</td>
        <td><strong>${targetId}</strong></td>
        <td>${targetName} <br><small style="color:#64748b;">@${escapeHtml(req.user_username)}</small></td>
        <td><i class="fa-solid fa-user-shield" style="color:#4f46e5;"></i> ${adminName}</td>
        <td><div class="reason-box" title="${reason}">${reason}</div></td>
        <td>${requestedAt}</td>
        <td>${statusBadge}</td>
        <td><div class="action-btn-group">${actionsHtml}</div></td>
      </tr>`;
    });

    tableBody.innerHTML = html;
  }

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
    fetchDeleteRequests();
  };

  window.viewRequestDetails = function (reqId) {
    const req = currentRequestsData.find((r) => Number(r.id) === Number(reqId));
    if (!req) return;

    let userDetails = {};
    try {
      userDetails = JSON.parse(req.user_details || "{}");
    } catch (e) {
      userDetails = {};
    }

    const address = [
      userDetails.purok_street,
      userDetails.barangay,
      userDetails.city_municipality,
      userDetails.province,
      userDetails.country,
      userDetails.zip_code,
    ]
      .filter(Boolean)
      .join(", ");

    detailsModalBody.innerHTML = `
      <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; margin-bottom:14px;">
        <h4 style="margin:0 0 4px 0; color:#991b1b; font-size:13px; font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> Deletion Request Summary</h4>
        <p style="margin:0; font-size:13px; color:#7f1d1d;"><strong>Requested By:</strong> ${escapeHtml(req.requested_by_username)} (Admin)</p>
        <p style="margin:4px 0 0 0; font-size:13px; color:#7f1d1d;"><strong>Stated Reason:</strong> "${escapeHtml(req.reason)}"</p>
        <p style="margin:4px 0 0 0; font-size:12px; color:#991b1b;"><strong>Submitted At:</strong> ${escapeHtml(req.requested_at)}</p>
        ${req.reviewed_by ? `<p style="margin:4px 0 0 0; font-size:12px; color:#1e293b;"><strong>Reviewed By:</strong> ${escapeHtml(req.reviewed_by)} at ${escapeHtml(req.reviewed_at || '')}</p>` : ''}
        ${req.review_notes ? `<p style="margin:4px 0 0 0; font-size:12px; color:#1e293b;"><strong>Review Notes:</strong> ${escapeHtml(req.review_notes)}</p>` : ''}
      </div>

      <h4 style="margin:10px 0 6px 0; font-size:14px; font-weight:700; color:#1e293b;"><i class="fa-solid fa-user"></i> Target User Complete Profile</h4>
      <div class="detail-grid">
        <div class="detail-item"><div class="detail-label">ID Number</div><div class="detail-val">${escapeHtml(req.user_id_number)}</div></div>
        <div class="detail-item"><div class="detail-label">Full Name</div><div class="detail-val">${escapeHtml(req.user_name)}</div></div>
        <div class="detail-item"><div class="detail-label">Username</div><div class="detail-val">${escapeHtml(req.user_username)}</div></div>
        <div class="detail-item"><div class="detail-label">Email Address</div><div class="detail-val">${escapeHtml(req.user_email)}</div></div>
        <div class="detail-item"><div class="detail-label">Role</div><div class="detail-val">${escapeHtml(req.user_role)}</div></div>
        <div class="detail-item"><div class="detail-label">Birthdate / Age</div><div class="detail-val">${escapeHtml(userDetails.birthdate || '-')} (${escapeHtml(String(userDetails.age || '-'))})</div></div>
        <div class="detail-item"><div class="detail-label">Gender</div><div class="detail-val">${escapeHtml(userDetails.gender || '-')}</div></div>
        <div class="detail-item"><div class="detail-label">Account Created</div><div class="detail-val">${escapeHtml(userDetails.created_at || '-')}</div></div>
        <div class="detail-item" style="grid-column: 1 / -1;"><div class="detail-label">Registered Address</div><div class="detail-val">${escapeHtml(address || "Not specified")}</div></div>
      </div>
    `;
    detailsModal.style.display = "flex";
  };

  window.closeDetailsModal = function () {
    detailsModal.style.display = "none";
  };

  window.confirmApproveDelete = function (reqId, targetName) {
    pendingAction = { action: "approve", requestId: reqId, name: targetName };
    confirmModalTitle.textContent = "Confirm & Execute User Deletion";
    confirmModalMsg.innerHTML = `Are you sure you want to approve this request and <strong style="color:#ef4444;">permanently delete</strong> the account for <strong>${escapeHtml(
      targetName
    )}</strong>? This action cannot be undone.`;
    rejectNotesGroup.style.display = "none";
    confirmModalSubmitBtn.style.background = "#ef4444";
    confirmModalSubmitBtn.textContent = "Approve & Delete Account";
    confirmModal.style.display = "flex";
  };

  window.confirmRejectRequest = function (reqId, targetName) {
    pendingAction = { action: "reject", requestId: reqId, name: targetName };
    confirmModalTitle.textContent = "Reject Deletion Request";
    confirmModalMsg.innerHTML = `Are you sure you want to reject the deletion request for <strong>${escapeHtml(
      targetName
    )}</strong>? The user account will remain safe and active.`;
    rejectNotesGroup.style.display = "block";
    rejectNotesInput.value = "";
    confirmModalSubmitBtn.style.background = "#64748b";
    confirmModalSubmitBtn.textContent = "Reject Request";
    confirmModal.style.display = "flex";
  };

  window.closeConfirmModal = function () {
    confirmModal.style.display = "none";
    pendingAction = null;
  };

  confirmModalSubmitBtn.addEventListener("click", function () {
    if (!pendingAction) return;

    if (pendingAction.action === "approve") {
      confirmModalSubmitBtn.disabled = true;
      confirmModalSubmitBtn.textContent = "Deleting...";

      const formData = new FormData();
      formData.append("request_id", pendingAction.requestId);

      fetch("../../php/auth/index.php?action=approveDeleteRequest", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          if (res.success) {
            showToast(res.message || "Account successfully deleted.", "success");
            fetchDeleteRequests();
          } else {
            showToast(res.message || "Failed to delete account.", "error");
          }
        })
        .catch((err) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          console.error("Approve delete error:", err);
          showToast("An error occurred.", "error");
        });
    } else if (pendingAction.action === "reject") {
      const notes = rejectNotesInput.value.trim() || "Rejected by Super Admin";

      confirmModalSubmitBtn.disabled = true;
      confirmModalSubmitBtn.textContent = "Rejecting...";

      const formData = new FormData();
      formData.append("request_id", pendingAction.requestId);
      formData.append("notes", notes);

      fetch("../../php/auth/index.php?action=rejectDeleteRequest", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          if (res.success) {
            showToast(res.message || "Request rejected.", "success");
            fetchDeleteRequests();
          } else {
            showToast(res.message || "Failed to reject request.", "error");
          }
        })
        .catch((err) => {
          confirmModalSubmitBtn.disabled = false;
          closeConfirmModal();
          console.error("Reject request error:", err);
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
      fetchDeleteRequests();
    }, 300);
  });

  statusFilter.addEventListener("change", function () {
    currentPage = 1;
    fetchDeleteRequests();
  });

  entriesSelect.addEventListener("change", function () {
    currentPage = 1;
    fetchDeleteRequests();
  });

  // Initial Load
  fetchDeleteRequests();
});
