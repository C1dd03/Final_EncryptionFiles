<?php

/* Session is started by UserController::showLogin before output. */
$forcePasswordChange = isset($_SESSION['user_id'], $_GET['force_password_change']) && $_GET['force_password_change'] === '1';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET['blocked']) && $_GET['blocked'] == 1): ?>
  <p style="color: #dc2626; font-weight: bold; text-align:center;">
    Your account has been blocked. Please contact the Super Admin.
  </p>
<?php endif; ?>

<?php if (isset($_GET['password_reset']) && $_GET['password_reset'] == 1): ?>
  <p style="color:#15803d;font-weight:bold;text-align:center;">
    Password changed successfully. You can now log in.
  </p>
<?php endif; ?>

<?php if (isset($_GET['inactive']) && $_GET['inactive'] == 1): ?>
  <p style="color:#dc2626;font-weight:bold;text-align:center;">
    This account is inactive and can no longer access the system.
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

<div id="requiredPasswordModal" class="required-password-modal" aria-modal="true" role="dialog" aria-labelledby="requiredPasswordTitle">
  <div class="required-password-card">
    <div class="required-password-icon"><i class="fa-solid fa-key"></i></div>
    <h2 id="requiredPasswordTitle">Change Your Default Password</h2>
    <p>For security, replace the temporary/default password before entering the portal. New accounts use <strong>@Abcde12345</strong>.</p>
    <div class="input-field password-field">
      <input type="password" id="requiredNewPassword" placeholder=" " autocomplete="new-password" />
      <label>New Password</label>
      <i class="fas fa-eye-slash toggle-password"></i>
    </div>
    <div class="input-field password-field">
      <input type="password" id="requiredConfirmPassword" placeholder=" " autocomplete="new-password" />
      <label>Confirm New Password</label>
      <i class="fas fa-eye-slash toggle-password"></i>
    </div>
    <div id="requiredPasswordMessage" class="field-error" role="alert"></div>
    <button type="button" class="btn_submit" id="requiredPasswordSubmit">Change Password</button>
    <a class="required-password-logout" href="logout.php">Log out instead</a>
  </div>
</div>



<style>
  .required-password-modal {
    display: none;
    position: absolute;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.72);
    align-items: center;
    justify-content: center;
    padding: 24px 18px;
    box-sizing: border-box;
  }
  .required-password-modal.active {
    display: flex;
  }
  .required-password-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 18px;
    padding: 24px 22px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    box-sizing: border-box;
    max-height: 100%;
    overflow-y: auto;
  }
  .required-password-card h2 {
    font-size: 20px;
    color: var(--farm-text, #1f2937);
    margin: 0 0 8px;
    line-height: 1.3;
  }
  .required-password-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e8f3e5;
    color: #477246;
    display: grid;
    place-items: center;
    margin: 0 auto 10px;
    font-size: 22px;
  }
  .required-password-card p {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 16px;
    line-height: 1.4;
  }
  .required-password-card .input-field {
    margin-bottom: 12px;
    text-align: left;
  }
  .required-password-card .field-error {
    min-height: 18px;
    color: #dc2626;
    font-size: 12px;
    margin: 2px 0 6px;
  }
  .required-password-card .btn_submit {
    width: 100%;
    margin-top: 4px;
  }
  .required-password-logout {
    display: inline-block;
    margin-top: 12px;
    font-size: 12px;
    color: #64748b;
    text-decoration: underline;
  }
  .required-password-logout:hover {
    color: #1e293b;
  }
</style>

<script>
  (() => {
    const modal = document.getElementById('requiredPasswordModal');
    const submit = document.getElementById('requiredPasswordSubmit');
    const message = document.getElementById('requiredPasswordMessage');
    let targetRedirect = 'index.php?action=dashboard';

    window.showRequiredPasswordModal = function (redirect) {
      targetRedirect = redirect || targetRedirect;
      modal.classList.add('active');
      const container = document.querySelector('.form-container');
      if (container) {
        container.classList.add('required-password-container');
      }
      document.querySelector('.login-form').style.display = 'none';
      document.getElementById('requiredNewPassword').focus();
    };

    submit.addEventListener('click', async () => {
      const password = document.getElementById('requiredNewPassword').value;
      const confirm = document.getElementById('requiredConfirmPassword').value;
      message.textContent = '';
      submit.disabled = true;
      try {
        const body = new URLSearchParams({new_password: password, confirm_password: confirm});
        const response = await fetch('index.php?action=changeRequiredPassword', {method:'POST', body, credentials:'same-origin'});
        const data = await response.json();
        if (!data.success) {
          message.textContent = data.message || 'Unable to change password.';
          submit.disabled = false;
          return;
        }
        window.location.href = data.redirect || targetRedirect;
      } catch (error) {
        message.textContent = 'Unable to connect. Please try again.';
        submit.disabled = false;
      }
    });

    <?php if ($forcePasswordChange): ?>
    window.addEventListener('DOMContentLoaded', () => window.showRequiredPasswordModal());
    <?php endif; ?>
  })();

  (() => {
  })();
</script>


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
