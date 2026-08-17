/* Shared helpers for the OTP verification screens (login / register / forgot password). */

// POST helper that returns parsed JSON
function otpPost(url, data) {
  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
    credentials: "same-origin",
  }).then((res) => res.json());
}

// Countdown before the "Resend OTP" link becomes clickable
function otpStartResendTimer(seconds, linkEl, timerEl, onDone) {
  if (linkEl) linkEl.style.display = "none";
  let remaining = Math.max(0, Math.floor(seconds || 0));

  const tick = () => {
    if (remaining <= 0) {
      clearInterval(interval);
      if (timerEl) timerEl.textContent = "";
      if (linkEl) linkEl.style.display = "inline";
      if (typeof onDone === "function") onDone();
      return;
    }
    if (timerEl) timerEl.textContent = "Resend code in " + remaining + "s";
    remaining -= 1;
  };

  tick();
  const interval = setInterval(tick, 1000);
  return interval;
}

// Countdown until the code expires
function otpStartExpiryTimer(seconds, el) {
  if (!el) return;
  let remaining = Math.max(0, Math.floor(seconds || 0));

  const fmt = (s) =>
    String(Math.floor(s / 60)).padStart(2, "0") + ":" + String(s % 60).padStart(2, "0");

  const tick = () => {
    if (remaining <= 0) {
      el.textContent = "Code expired. Please resend.";
      clearInterval(interval);
      return;
    }
    el.textContent = "Code expires in " + fmt(remaining);
    remaining -= 1;
  };

  tick();
  const interval = setInterval(tick, 1000);
  return interval;
}

// Toggle a loading state on a button (spinner + label) while an OTP is being prepared/verified
function otpSetButtonLoading(btn, loading, loadingText, normalText) {
  if (!btn) return;
  if (loading) {
    btn.disabled = true;
    btn.classList.add("loading");
    btn.dataset.normalText = normalText || btn.textContent;
    btn.innerHTML =
      '<span class="btn-spinner"></span>' + (loadingText || "Loading...");
  } else {
    btn.disabled = false;
    btn.classList.remove("loading");
    btn.textContent = btn.dataset.normalText || normalText || "";
  }
}

// Dev-mode banner: reveals the OTP when config/mail.php has dev_show_otp enabled
function otpShowDevBanner(containerEl, otp) {
  if (!containerEl || !otp) return;
  containerEl.innerHTML =
    "Dev mode: your OTP is <strong>" + otp + "</strong> (no email was sent — add SMTP credentials in config/mail.php)";
  containerEl.style.display = "block";
}
