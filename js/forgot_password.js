document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("forgotForm");
  if (!form) return;

  const steps = [...form.querySelectorAll(".step")];
  const numbers = [...form.querySelectorAll(".number")];
  const lines = [...form.querySelectorAll(".forgot-pass .line")];
  const idInput = document.getElementById("forgotIdInput");
  const accountSummary = document.getElementById("forgotAccountSummary");
  const otpInput = document.getElementById("forgotOtpInput");

  const questionSelect1 = document.getElementById("forgotSecurityQuestion1");
  const questionSelect2 = document.getElementById("forgotSecurityQuestion2");
  const questionSelect3 = document.getElementById("forgotSecurityQuestion3");
  const answerInput1 = document.getElementById("forgotSecurityAnswer1");
  const answerInput2 = document.getElementById("forgotSecurityAnswer2");
  const answerInput3 = document.getElementById("forgotSecurityAnswer3");

  const newPassword = document.getElementById("newPassword");
  const confirmPassword = document.getElementById("confirmPassword");
  let resetToken = "";
  let timers = [];

  const errorEl = (id, message = "") => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = message;
      el.style.visibility = message ? "visible" : "hidden";
    }
  };

  const showStep = (number) => {
    steps.forEach((step, index) => step.classList.toggle("active", index === number - 1));
    numbers.forEach((node, index) => node.classList.toggle("active", index < number));
    lines.forEach((node, index) => node.classList.toggle("active", index < number - 1));
  };

  const post = async (action, values = {}) => {
    const response = await fetch(`index.php?action=${action}`, {
      method: "POST",
      credentials: "same-origin",
      body: new URLSearchParams(values),
    });
    return response.json();
  };

  const clearTimers = () => {
    timers.forEach(clearInterval);
    timers = [];
  };

  const startOtpTimers = (expires, cooldown) => {
    clearTimers();
    const expiry = document.getElementById("forgotOtpExpiry");
    const resendTimer = document.getElementById("forgotOtpResendTimer");
    const resend = document.getElementById("forgotResendOtp");
    if (typeof otpStartExpiryTimer === "function") timers.push(otpStartExpiryTimer(expires || 600, expiry));
    if (typeof otpStartResendTimer === "function") timers.push(otpStartResendTimer(cooldown || 60, resend, resendTimer));
  };

  // Attach password toggle listeners for eye icons
  const setupPasswordToggles = () => {
    form.querySelectorAll(".toggle-password").forEach((icon) => {
      if (icon.dataset.listenerAttached === "true") return;
      icon.dataset.listenerAttached = "true";
      icon.addEventListener("click", function () {
        const container = this.closest(".pass-input-field") || this.closest(".password-field");
        if (!container) return;
        const input = container.querySelector("input");
        if (!input) return;
        if (input.type === "password") {
          input.type = "text";
          this.classList.remove("fa-eye-slash");
          this.classList.add("fa-eye");
        } else {
          input.type = "password";
          this.classList.remove("fa-eye");
          this.classList.add("fa-eye-slash");
        }
      });
    });
  };
  setupPasswordToggles();

  // Step 1: Submit ID Number & Request OTP
  document.getElementById("forgotStartBtn").addEventListener("click", async () => {
    errorEl("idError");
    const idNumber = idInput.value.trim();
    if (!idNumber) return errorEl("idError", "ID Number is required.");
    const button = document.getElementById("forgotStartBtn");
    otpSetButtonLoading(button, true, "Sending Code...", button.textContent || "Send OTP");
    try {
      const data = await post("verifyForgotEmail", { id_number: idNumber });
      if (!data.success) {
        otpSetButtonLoading(button, false, "", "Send OTP");
        return errorEl("idError", data.message || "Unable to start password recovery.");
      }
      document.getElementById("maskedForgotId").textContent = data.masked_id;
      document.getElementById("maskedForgotEmail").textContent = data.masked_email;
      accountSummary.hidden = false;
      if (typeof otpShowDevBanner === "function") otpShowDevBanner(document.getElementById("forgotOtpDevBanner"), data.dev_otp);
      otpInput.value = "";
      startOtpTimers(data.expires_in, data.cooldown);
      showStep(2);
      otpInput.focus();
    } catch (error) {
      otpSetButtonLoading(button, false, "", "Send OTP");
      errorEl("idError", "Unable to connect. Please try again.");
    }
  });

  // Step 2: Verify OTP & Populate 3 Security Questions
  document.getElementById("forgotVerifyOtpBtn").addEventListener("click", async () => {
    errorEl("otpError");
    const otp = otpInput.value.trim();
    if (!/^\d{6}$/.test(otp)) return errorEl("otpError", "Enter the 6-digit OTP.");
    const button = document.getElementById("forgotVerifyOtpBtn");
    button.disabled = true;
    try {
      const data = await post("verifyForgotOtp", { otp });
      if (!data.success) return errorEl("otpError", data.message || "Invalid OTP.");
      clearTimers();

      const selects = [questionSelect1, questionSelect2, questionSelect3];
      const questions = Array.isArray(data.questions)
        ? data.questions
        : (data.question ? [data.question] : []);

      questions.forEach((q, idx) => {
        const select = selects[idx];
        if (!select) return;
        let optionExists = false;
        for (let i = 0; i < select.options.length; i++) {
          if (String(select.options[i].value) === String(q.question_id)) {
            optionExists = true;
            break;
          }
        }
        if (!optionExists) {
          const opt = document.createElement("option");
          opt.value = q.question_id;
          opt.textContent = q.question_text;
          select.appendChild(opt);
        }
        select.value = String(q.question_id);
      });

      showStep(3);
      setupPasswordToggles();
      if (answerInput1) answerInput1.focus();
    } catch (error) {
      errorEl("otpError", "Unable to verify OTP. Please try again.");
    } finally {
      button.disabled = false;
    }
  });

  // Resend OTP
  document.getElementById("forgotResendOtp").addEventListener("click", async (event) => {
    event.preventDefault();
    errorEl("otpError");
    const data = await post("sendForgotOtp");
    if (!data.success) return errorEl("otpError", data.message || "Unable to resend the OTP.");
    if (typeof otpShowDevBanner === "function") otpShowDevBanner(document.getElementById("forgotOtpDevBanner"), data.dev_otp);
    otpInput.value = "";
    startOtpTimers(data.expires_in, data.cooldown);
  });

  // Step 3: Verify All 3 Security Answers
  document.getElementById("forgotVerifySecurityBtn").addEventListener("click", async () => {
    errorEl("securityError");
    const q1 = questionSelect1 ? questionSelect1.value : "";
    const a1 = answerInput1 ? answerInput1.value.trim() : "";
    const q2 = questionSelect2 ? questionSelect2.value : "";
    const a2 = answerInput2 ? answerInput2.value.trim() : "";
    const q3 = questionSelect3 ? questionSelect3.value : "";
    const a3 = answerInput3 ? answerInput3.value.trim() : "";

    if (!q1 || !a1 || !q2 || !a2 || !q3 || !a3) {
      return errorEl("securityError", "Please select and answer all 3 security questions.");
    }

    const button = document.getElementById("forgotVerifySecurityBtn");
    button.disabled = true;
    try {
      const data = await post("verifySecurityAnswers", {
        security_question_1: q1,
        security_answer_1: a1,
        security_question_2: q2,
        security_answer_2: a2,
        security_question_3: q3,
        security_answer_3: a3,
      });
      if (!data.success) {
        return errorEl("securityError", data.message || "Incorrect security answers.");
      }
      resetToken = data.reset_token;
      showStep(4);
      newPassword.focus();
    } catch (error) {
      errorEl("securityError", "Unable to verify security answers. Please try again.");
    } finally {
      button.disabled = false;
    }
  });

  // Navigation Back Buttons
  form.querySelectorAll('[data-back="1"]').forEach((btn) => {
    btn.addEventListener("click", () => {
      clearTimers();
      form.reset();
      resetToken = "";
      accountSummary.hidden = true;
      showStep(1);
    });
  });

  form.querySelectorAll('[data-step="2"]').forEach((btn) => {
    btn.addEventListener("click", () => {
      showStep(2);
      otpInput.focus();
    });
  });

  // Step 4: Submit New Password
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    errorEl("passwordError");
    if (!resetToken) return errorEl("passwordError", "Complete OTP and security verification first.");
    if (newPassword.value !== confirmPassword.value) return errorEl("passwordError", "Passwords do not match.");
    const button = document.getElementById("forgotPasswordSubmit");
    button.disabled = true;
    try {
      const data = await post("resetPassword", {
        new_password: newPassword.value,
        confirm_password: confirmPassword.value,
        reset_token: resetToken,
      });
      if (!data.success) {
        errorEl("passwordError", data.message || "Unable to change the password.");
        button.disabled = false;
        return;
      }
      window.location.href = data.redirect || "index.php?action=login&password_reset=1";
    } catch (error) {
      errorEl("passwordError", "Unable to connect. Please try again.");
      button.disabled = false;
    }
  });
});
