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

    if (value === "" && input.required) {
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

    if (value === "") {
      if (["first_name", "middle_name", "last_name"].includes(name)) {
        return null;
      }
      if (["street", "barangay", "city", "province", "country"].includes(name)) {
        return null;
      }
      if (name === "zip" || name === "birthdate" || name === "gender") {
        return null;
      }
      if (name.startsWith("security_")) {
        return null;
      }
      return null;
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
      const tag = input.tagName.toLowerCase();
      if (tag === "select") {
        input.addEventListener("change", function () {
          const err = validateField(this);
          setFieldError(this, err);
          if (this.name === "password") {
            const confInp = document.getElementById("formConfirmPassword");
            if (confInp && (confInp.value || mode === "add")) {
              setFieldError(confInp, validateField(confInp));
            }
          }
        });
      } else {
        input.addEventListener("blur", function () {
          const err = validateField(this);
          setFieldError(this, err);
          if (this.name === "password") {
            const confInp = document.getElementById("formConfirmPassword");
            if (confInp && (confInp.value || mode === "add")) {
              setFieldError(confInp, validateField(confInp));
            }
          }
        });
      }
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

  let currentAdminStep = 1;
  const adminModalCard = document.getElementById("adminModalCard");

  function updateAdminStepperIndicators(stepNum) {
    const numbers = document.querySelectorAll("#adminModalStepper .modal-step-number");
    const lines = document.querySelectorAll("#adminModalStepper .modal-step-line");
    const titles = document.querySelectorAll("#adminModalStepper .modal-step-title");

    numbers.forEach((num, idx) => {
      const stepVal = idx + 1;
      num.classList.remove("active", "completed");
      if (stepVal < stepNum) {
        num.classList.add("completed");
      } else if (stepVal === stepNum) {
        num.classList.add("active");
      }
    });

    lines.forEach((line, idx) => {
      const lineStep = idx + 1;
      if (lineStep < stepNum) {
        line.classList.add("active");
      } else {
        line.classList.remove("active");
      }
    });

    titles.forEach((title, idx) => {
      const titleStep = idx + 1;
      title.classList.toggle("active", titleStep === stepNum);
    });
  }

  function showAdminStep(stepNum) {
    currentAdminStep = stepNum;
    const panes = document.querySelectorAll("#adminForm .modal-step-pane");
    panes.forEach((pane) => {
      const paneStep = parseInt(pane.getAttribute("data-step"), 10);
      if (paneStep === stepNum) {
        pane.classList.add("active");
      } else {
        pane.classList.remove("active");
      }
    });
    updateAdminStepperIndicators(stepNum);
  }

  function validateAdminStep(stepNum) {
    const pane = document.getElementById(`adminStep${stepNum}`);
    if (!pane) return true;

    let isValid = true;
    let firstInvalid = null;

    pane.querySelectorAll("input, select").forEach((input) => {
      const err = validateField(input);
      if (err) {
        setFieldError(input, err);
        if (!firstInvalid) firstInvalid = input;
        isValid = false;
      } else {
        clearFieldError(input);
      }
    });

    if (!isValid && firstInvalid) {
      firstInvalid.focus();
    }
    return isValid;
  }

  window.nextAdminStep = function (currentStep) {
    if (validateAdminStep(currentStep)) {
      showAdminStep(Math.min(currentStep + 1, 4));
    }
  };

  window.prevAdminStep = function (currentStep) {
    showAdminStep(Math.max(currentStep - 1, 1));
  };

  function openAddModal() {
    if (adminForm) adminForm.reset();
    clearAllModalErrors();
    setVal("formMode", "add");

    if (adminModalCard) adminModalCard.classList.remove("edit-mode");

    const profileHdr = document.getElementById("editAdminProfileHeader");
    if (profileHdr) profileHdr.style.display = "none";

    const idEl = document.getElementById("formIdNumber");
    if (idEl) {
      idEl.value = "";
      idEl.removeAttribute("readonly");
    }

    const secTitle = document.getElementById("securitySectionTitle");
    const secGrid = document.getElementById("securitySectionGrid");
    if (secTitle) secTitle.style.display = "";
    if (secGrid) secGrid.style.display = "";
    ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.required = true;
    });

    ["formFirstName", "formLastName", "formBirthdate", "formGender", "formStreet", "formBarangay", "formCity", "formProvince", "formCountry", "formZip"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.required = true;
    });

    const passGrp = document.getElementById("passwordGroup");
    const confGrp = document.getElementById("confirmPasswordGroup");
    const passInp = document.getElementById("formPassword");
    const confInp = document.getElementById("formConfirmPassword");
    const passLbl = document.getElementById("formPasswordLabel");

    if (passGrp) passGrp.style.display = "";
    if (confGrp) confGrp.style.display = "";
    if (passInp) passInp.required = true;
    if (confInp) confInp.required = true;
    if (passLbl) passLbl.textContent = "Password *";

    showAdminStep(1);

    if (adminModalTitle) adminModalTitle.textContent = "Add New Admin";
    if (adminModal) adminModal.classList.add("show");

    fetch("../../php/auth/index.php?action=getNextIds&type=admin")
      .then((res) => res.json())
      .then((res) => {
        if (res.success && res.admin_id && idEl) {
          idEl.value = res.admin_id;
        }
      })
      .catch((err) => console.error("Failed to fetch next admin ID:", err));
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

        if (adminModalCard) adminModalCard.classList.add("edit-mode");

        // Populate dynamic profile summary banner in edit mode
        const profileHdr = document.getElementById("editAdminProfileHeader");
        if (profileHdr) {
          profileHdr.style.display = "flex";
          const fullName = [admin.first_name, admin.middle_name, admin.last_name, admin.extension].filter(Boolean).join(" ");
          const initial = (admin.first_name ? admin.first_name.charAt(0) : "A").toUpperCase();
          const avatarEl = document.getElementById("editAdminAvatar");
          const nameEl = document.getElementById("editAdminDisplayName");
          const idEl = document.getElementById("editAdminIdNumber");
          const userEl = document.getElementById("editAdminUsername");
          const emailEl = document.getElementById("editAdminEmail");
          const statusBadge = document.getElementById("editAdminStatusBadge");
          const statusText = document.getElementById("editAdminStatusText");

          if (avatarEl) avatarEl.textContent = initial;
          if (nameEl) nameEl.textContent = fullName || admin.username || "Administrator Account";
          if (idEl) idEl.textContent = admin.id_number || "-";
          if (userEl) userEl.textContent = admin.username ? `@${admin.username}` : "-";
          if (emailEl) emailEl.textContent = admin.email || "-";

          if (statusBadge && statusText) {
            const status = admin.status || "active";
            const label = status === "pending_deletion" ? "Pending Deletion" : status.charAt(0).toUpperCase() + status.slice(1);
            statusBadge.className = `edit-status-badge ${status.replace("_", "-")}`;
            statusText.textContent = label;
          }
        }

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

        ["formSecQ1", "formSecA1", "formSecQ2", "formSecA2", "formSecQ3", "formSecA3"].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });

        setVal("formUsername", admin.username);
        setVal("formEmail", admin.email);
        setVal("formStatus", admin.status === "block" ? "blocked" : (admin.status || "active"));

        const passGrp = document.getElementById("passwordGroup");
        const confGrp = document.getElementById("confirmPasswordGroup");
        const passInp = document.getElementById("formPassword");
        const confInp = document.getElementById("formConfirmPassword");
        const passLbl = document.getElementById("formPasswordLabel");

        if (passGrp) passGrp.style.display = "";
        if (confGrp) confGrp.style.display = "none";
        if (passInp) {
          passInp.required = false;
          passInp.placeholder = "Leave blank to keep unchanged";
        }
        if (confInp) confInp.required = false;
        if (passLbl) passLbl.textContent = "New Password (Optional)";

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
    if (adminModalCard) adminModalCard.classList.remove("edit-mode");
    const profileHdr = document.getElementById("editAdminProfileHeader");
    if (profileHdr) profileHdr.style.display = "none";
  }

  function handleAdminFormSubmit(e) {
    e.preventDefault();

    const mode = document.getElementById("formMode").value;
    let firstInvalidInput = null;
    let hasError = false;

    clearAllModalErrors();

    if (mode === "add") {
      // Validate all steps in add mode
      for (let s = 1; s <= 4; s++) {
        if (!validateAdminStep(s)) {
          showAdminStep(s);
          return;
        }
      }
    } else {
      // In edit mode: validate all visible fields in edit frame
      adminForm.querySelectorAll("input:not([type=hidden]), select").forEach((input) => {
        if (input.offsetParent !== null) {
          const err = validateField(input);
          if (err) {
            setFieldError(input, err);
            if (!firstInvalidInput) firstInvalidInput = input;
            hasError = true;
          }
        }
      });

      if (hasError) {
        if (firstInvalidInput) {
          firstInvalidInput.focus();
        }
        return;
      }
    }
    const formData = new FormData(adminForm);
    const isEdit = mode !== "add";
    const adminName = (formData.get("first_name") || "") + " " + (formData.get("last_name") || "");

    // Show confirmation modal before saving changes / adding admin
    confirmModalTitle.textContent = isEdit ? "Save Changes?" : "Add Administrator?";
    confirmModalMessage.innerHTML = isEdit
      ? `Are you sure you want to save changes to admin account <strong>${escapeHtml(adminName.trim())}</strong>?`
      : `Are you sure you want to create a new administrator account for <strong>${escapeHtml(adminName.trim())}</strong>?`;

    confirmModalBtn.className = "btn-primary";
    confirmModalBtn.textContent = isEdit ? "Confirm & Save" : "Confirm & Create";

    activeConfirmCallback = function () {
      const action = mode === "add" ? "addAdmin" : "updateAdmin";

      fetch(`../../php/auth/index.php?action=${action}`, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            setFlashToast(isEdit ? "Admin successfully updated." : "Admin successfully created.", "success");
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
              showToast(res.message || "Operation failed.", "error");
            }
          }
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred during form submission.", "error");
        });
    };

    confirmModal.classList.add("show");
  }

  window.viewAdmin = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    fetch(`../../php/auth/index.php?action=getAdminDetail&id_number=${encodeURIComponent(row.id_number)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          showToast(res.message || "Could not fetch admin details.", "error");
          return;
        }

        const admin = res.data;
        document.getElementById("viewIdNumber").textContent = admin.id_number || "-";
        document.getElementById("viewFirstName").textContent = admin.first_name || "-";
        document.getElementById("viewMiddleName").textContent = admin.middle_name || "N/A";
        document.getElementById("viewLastName").textContent = admin.last_name || "-";
        document.getElementById("viewExtension").textContent = admin.extension || "N/A";
        document.getElementById("viewName").textContent = admin.name || "-";
        document.getElementById("viewUsername").textContent = "@" + (admin.username || "-");
        document.getElementById("viewEmail").textContent = admin.email || "N/A";
        document.getElementById("viewContact").textContent = admin.contact_number || "N/A";
        document.getElementById("viewRole").textContent = (admin.role || "admin").toUpperCase();
        document.getElementById("viewStatus").textContent = (admin.status || "active").replace(/_/g, " ").toUpperCase();
        document.getElementById("viewApproval").textContent = admin.status === "pending_approval" ? "PENDING APPROVAL" : (admin.status === "pending" ? "PENDING" : "APPROVED");
        document.getElementById("viewGender").textContent = (admin.gender || "-").toUpperCase();
        document.getElementById("viewBirthdate").textContent = admin.birthdate || "-";
        document.getElementById("viewAge").textContent = admin.age || "-";

        const address = [admin.street, admin.barangay, admin.city, admin.province, admin.country, admin.zip].filter(Boolean).join(", ");
        document.getElementById("viewAddress").textContent = address || "N/A";

        document.getElementById("viewCreated").textContent = admin.created_at || "N/A";
        document.getElementById("viewUpdated").textContent = admin.updated_at || "N/A";
        document.getElementById("viewLastLogin").textContent = admin.last_login || "N/A";

        viewModal.classList.add("show");
      })
      .catch((err) => {
        console.error(err);
        showToast("An error occurred while fetching admin details.", "error");
      });
  };

  function closeViewModal() {
    viewModal.classList.remove("show");
  }

  window.confirmToggleBlock = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    const isBlocking = row.status !== "block" && row.status !== "blocked";
    const actionName = isBlocking ? "Block" : "Unblock";
    const nextStatus = isBlocking ? "blocked" : "active";

    confirmModalTitle.textContent = `${actionName} Admin Account`;
    confirmModalMessage.innerHTML = `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> admin account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?${isBlocking
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
            setFlashToast(isBlocking ? "Admin successfully blocked." : "Admin successfully unblocked.", "success");
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

  window.confirmDeleteAdmin = function (btnEl) {
    const row = getRowData(btnEl);
    if (!row) return;

    confirmModalTitle.textContent = "Deactivate Admin Account";
    confirmModalMessage.innerHTML = `Are you sure you want to deactivate admin account <strong>${escapeHtml(
      row.name
    )}</strong> (<code>${escapeHtml(row.id_number)}</code>)?<br><br><span style="color:#475569; font-size:0.85rem;"><i class="fa-solid fa-database"></i> Status will change to Inactive, access will end immediately, and the record will be preserved.</span>`;

    confirmModalBtn.className = "btn-danger";
    confirmModalBtn.textContent = "Confirm Deactivation";

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
            setFlashToast("Admin account is now Inactive.", "success");
            window.location.reload();
          } else {
            showToast(res.message || "Failed to deactivate admin.", "error");
          }
        })
        .catch((err) => {
          console.error(err);
          showToast("An error occurred while deactivating admin.", "error");
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

  function closeAllRoleDropdowns(except) {
    document.querySelectorAll(".role-dropdown.open").forEach((d) => {
      if (d !== except) d.classList.remove("open");
    });
  }

  // ── Bind custom dropdown UI ──────────────────────────────────────────
  document.querySelectorAll(".role-dropdown").forEach((dropdown) => {
    const btn = dropdown.querySelector(".role-dropdown-btn");
    const menu = dropdown.querySelector(".role-dropdown-menu");
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
      const newRole = this.value;
      const previousRole = this.dataset.currentRole || "admin";

      this.value = previousRole; // revert until confirmed

      if (newRole === previousRole) return;

      pendingRoleChange = {
        id_number: this.dataset.id_number || "",
        username: this.dataset.username || "",
        name: this.dataset.name || "",
        newRole: newRole,
        previousRole: previousRole,
        confirmSelf: false,
      };

      const fromText = roleLabels[previousRole] || previousRole;
      const toText = roleLabels[newRole] || newRole;

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
            if (res.auto_logout) {
              setFlashToast("Super Admin role transferred. You have been logged out.", "info");
              setTimeout(() => {
                window.location.href = res.logout_redirect || '../../php/auth/index.php?action=login&reason=role_transferred';
              }, 1500);
              return;
            }
            setFlashToast("Role successfully changed.", "success");
            window.location.reload();
            return;
          }
          if (res.confirmation) {
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

    const targetNameEl = document.getElementById("privilegesTargetName");
    const targetIdEl = document.getElementById("privilegesTargetId");
    const targetUserEl = document.getElementById("privilegesTargetUser");
    if (targetNameEl) targetNameEl.textContent = row.name || "Administrator";
    if (targetIdEl) targetIdEl.textContent = row.id_number || "-";
    if (targetUserEl) targetUserEl.textContent = "@" + (row.username || "admin");

    privilegeCheckboxes.forEach((cb) => (cb.checked = false));

    fetch(`../../php/auth/index.php?action=getAdminPrivileges&id_number=${encodeURIComponent(row.id_number)}`)
      .then((res) => res.json())
      .then((res) => {
        if (!res.success) {
          showToast(res.message || "Could not fetch privileges.", "error");
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
        showToast("An error occurred while loading privileges.", "error");
      });
  };

  const btnSelectAllPriv = document.getElementById("btnSelectAllPriv");
  const btnDeselectAllPriv = document.getElementById("btnDeselectAllPriv");
  if (btnSelectAllPriv) {
    btnSelectAllPriv.addEventListener("click", function () {
      privilegeCheckboxes.forEach((cb) => (cb.checked = true));
    });
  }
  if (btnDeselectAllPriv) {
    btnDeselectAllPriv.addEventListener("click", function () {
      privilegeCheckboxes.forEach((cb) => (cb.checked = false));
    });
  }

  if (savePrivilegesBtn) {
    savePrivilegesBtn.addEventListener("click", function () {
      const idNumber = privilegesIdNumber ? privilegesIdNumber.value : "";
      const username = privilegesUsername ? privilegesUsername.value : "admin";
      if (!idNumber) {
        showToast("No administrator selected.", "error");
        return;
      }

      // Show confirmation before saving privileges
      confirmModalTitle.textContent = "Update Privileges?";
      confirmModalMessage.innerHTML = `Are you sure you want to update administrator privileges for <strong>${escapeHtml(username)}</strong>?`;
      confirmModalBtn.className = "btn-primary";
      confirmModalBtn.textContent = "Confirm & Save";

      activeConfirmCallback = function () {
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
              closePrivilegesModal();
              showToast("Privileges successfully updated.", "success");
            } else {
              showToast(res.message || "Failed to save privileges.", "error");
            }
          })
          .catch((err) => {
            savePrivilegesBtn.disabled = false;
            console.error(err);
            showToast("An error occurred while saving privileges.", "error");
          });
      };

      confirmModal.classList.add("show");
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
      } catch (e) { }
      sessionStorage.removeItem("flash_toast");
    }
  }

  checkFlashToast();
});
