document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("forgotForm");
  if (!form) return;

  const steps = [...form.querySelectorAll(".step")];
  const numbers = [...form.querySelectorAll(".number")];
  const lines = [...form.querySelectorAll(".forgot-pass .line")];
  const idInput = document.getElementById("forgotIdInput");
  const accountSummary = document.getElementById("forgotAccountSummary");
  const otpInput = document.getElementById("forgotOtpInput");
  const questionSelect = document.getElementById("forgotSecurityQuestion");
  const answerInput = document.getElementById("forgotSecurityAnswer");
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

  document.getElementById("forgotStartBtn").addEventListener("click", async () => {
    errorEl("idError");
    const idNumber = idInput.value.trim();
    if (!idNumber) return errorEl("idError", "ID Number is required.");
    const button = document.getElementById("forgotStartBtn");
    button.disabled = true;
    try {
      const data = await post("verifyForgotEmail", { id_number: idNumber });
      if (!data.success) return errorEl("idError", data.message || "Unable to start password recovery.");
      document.getElementById("maskedForgotId").textContent = data.masked_id;
      document.getElementById("maskedForgotEmail").textContent = data.masked_email;
      accountSummary.hidden = false;
      if (typeof otpShowDevBanner === "function") otpShowDevBanner(document.getElementById("forgotOtpDevBanner"), data.dev_otp);
      otpInput.value = "";
      startOtpTimers(data.expires_in, data.cooldown);
      showStep(2);
      otpInput.focus();
    } catch (error) {
      errorEl("idError", "Unable to connect. Please try again.");
    } finally {
      button.disabled = false;
    }
  });

  document.getElementById("forgotVerifyOtpBtn").addEventListener("click", async () => {
    errorEl("otpError");
    const otp = otpInput.value.trim();
    if (!/^\d{6}$/.test(otp)) return errorEl("otpError", "Enter the 6-digit OTP.");
    const data = await post("verifyForgotOtp", { otp });
    if (!data.success) return errorEl("otpError", data.message || "Invalid OTP.");
    clearTimers();
    questionSelect.innerHTML = "";
    const option = document.createElement("option");
    option.value = data.question.question_id;
    option.textContent = data.question.question_text;
    option.selected = true;
    questionSelect.appendChild(option);
    questionSelect.disabled = true;
    showStep(3);
    answerInput.focus();
  });

  document.getElementById("forgotResendOtp").addEventListener("click", async (event) => {
    event.preventDefault();
    errorEl("otpError");
    const data = await post("sendForgotOtp");
    if (!data.success) return errorEl("otpError", data.message || "Unable to resend the OTP.");
    if (typeof otpShowDevBanner === "function") otpShowDevBanner(document.getElementById("forgotOtpDevBanner"), data.dev_otp);
    otpInput.value = "";
    startOtpTimers(data.expires_in, data.cooldown);
  });

  document.getElementById("forgotVerifySecurityBtn").addEventListener("click", async () => {
    errorEl("securityError");
    const answer = answerInput.value.trim();
    if (!answer) return errorEl("securityError", "Security Answer is required.");
    const data = await post("verifySecurityAnswers", {
      question_id: questionSelect.value,
      security_answer: answer,
    });
    if (!data.success) return errorEl("securityError", data.message || "Incorrect security answer.");
    resetToken = data.reset_token;
    showStep(4);
    newPassword.focus();
  });

  form.querySelector('[data-back="1"]').addEventListener("click", () => {
    clearTimers();
    form.reset();
    resetToken = "";
    accountSummary.hidden = true;
    showStep(1);
  });

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
