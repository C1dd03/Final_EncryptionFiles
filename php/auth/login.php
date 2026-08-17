<?php

/* ========================== ADD SESSION ======================== */
session_start();
if (isset($_SESSION['user_id'])) {
  header('Location: index.php?action=dashboard');
  exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET['blocked']) && $_GET['blocked'] == 1): ?>
  <p style="color: #dc2626; font-weight: bold; text-align:center;">
    Your account has been blocked. Please contact the Super Admin.
  </p>
<?php endif; ?>


<?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
  <p style="color: green; font-weight: bold; text-align:center;">
    🎉 Registration successful! Please log in.
  </p>
<?php endif; ?>


<form class="login-form" method="POST" onsubmit="return false;">

  <h2>Login</h2>

  <p class="empty-mgs"></p>
  <div class="form-field" style="margin-bottom: 10px;">
    <div class="input-field" >
      <input type="text" name="username" class="login-email" placeholder=" " />
      <label>Username</label>
    </div>
    <div class="field-error" id="username-error" role="alert" style="display:none; color:#dc3545; font-size:12px;"></div>
  </div>

  <div class="form-field" style="margin-bottom: 10px;">
    <div class="input-field password-field">
      <input type="password" name="password" class="login-password" placeholder=" " />
      <label>Password</label>
      <i class="fas fa-eye-slash toggle-password"></i>
    </div>
    <div class="field-error" id="password-error" role="alert" style="display:none; color:#dc3545; font-size:12px;"></div>
  </div>

  <button class="btn_submit">Login</button>

  <p class="toggle-link login-forgot-password" onclick="resetForgotLink()">
    <a href="index.php?action=forgot">Forgot Password?</a>
  </p>

  <p class="toggle-link register-link">
    Don't have an account? <a href="index.php?action=register"><b>Register</b></a>
  </p>
  <div class="field-error" id="login-form-error" role="alert" style="display:none; color:#dc3545; font-size:12px; margin-top:4px; text-align:center;"></div>

  <!-- add login-message -->
  <div class="msg" id="login-message" role="status" aria-live="polite" style="text-align: center;"></div>

</form>

<!-- OTP Verification Step (shown after username/password are accepted) -->
<div class="otp-verify-step" id="loginOtpStep" style="display:none;">
  <h2>Verify Your Login</h2>
  <p class="otp-info">Enter the 6-digit code sent to<br /><strong id="loginOtpEmail"></strong></p>

  <div class="form-field" style="margin-bottom: 10px;">
    <div class="input-field">
      <input type="text" name="otp" id="loginOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder=" " />
      <label>OTP Code</label>
    </div>
    <div class="field-error" id="login-otp-error" role="alert" style="display:none; color:#dc3545; font-size:12px; text-align:center;"></div>
  </div>

  <button class="btn_submit" id="verifyLoginOtpBtn" type="button">Verify &amp; Log In</button>

  <p class="otp-timer" id="loginOtpExpiry" style="text-align:center; font-size:12px; color:#6b7280; margin-top:6px;"></p>
  <p class="otp-timer" id="loginOtpResendTimer" style="text-align:center; font-size:12px; color:#6b7280;"></p>
  <div class="field-error" id="login-otp-message" role="alert" style="display:none; color:#dc3545; font-size:12px; text-align:center; margin-top:4px;"></div>
  <p class="otp-resend" style="text-align:center; font-size:13px; margin-top:8px;">
    Didn't receive the code? <a href="javascript:void(0)" id="loginResendOtp" style="display:none;">Resend OTP</a>
  </p>
  <div class="dev-otp-banner" id="loginOtpDevBanner" style="display:none; background:#fef9c3; color:#854d0e; font-size:12px; text-align:center; padding:8px; border-radius:6px; margin-top:8px;"></div>
  <p class="toggle-link" style="margin-top: 12px;">
    <a href="index.php?action=login">← Back to Login</a>
  </p>
</div>

<!-- Reserve Code for forgot password

// After failed login
$_SESSION['login_failed'] = true;

<p class="toggle-link forgot-password 
  <?php echo !empty($_SESSION['login_failed']) ? 'show' : ''; ?>">
  <a href="index.php?action=forgot">Forgot Password?</a>
</p>

.forgot-password {
  opacity: 0;
  max-height: 0;
  overflow: hidden;
  transition: opacity 0.4s ease, max-height 0.4s ease;
}

.forgot-password.show {
  opacity: 1;
  max-height: 40px; /* enough to reveal */
}

-->
<script>
  // Prevent going back to login page after login
  // if (window.history && window.history.pushState) {
  //   window.history.pushState(null, "", window.location.href);
  //   window.onpopstate = function () {
  //     window.history.pushState(null, "", window.location.href);
  //   };
  // }


  /* =========================== CHANGE disable back browser button ================================== */
  // Prevent going back to login page after login
  history.pushState(null, null, location.href);
  window.onpopstate = function() {
    history.go(1);
  };
</script>