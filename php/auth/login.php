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
    <div class="input-field">
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
    <!-- <p>For security, replace the temporary password before entering the portal.<br><strong style="color:#477246;">Default: @Abcde12345</strong></p> -->

    <div class="rp-identity-fields">
      <div class="rp-email-row">
        <div class="input-field"><input id="requiredEmail" type="email" maxlength="150" autocomplete="email" placeholder=" " aria-describedby="rpEmailError"><label for="requiredEmail">Registered Email</label></div>
        <small id="rpEmailError" class="rp-identity-error" aria-live="polite"></small>
      </div>
      <p class="rp-question-note">Answer all three questions. At least two answers must be correct.</p>
      <div class="rp-question-row">
        <div><div class="input-field rp-question-field"><select id="requiredQuestion1" aria-describedby="rpQuestionError1" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="1">Who is your best friend in elementary?</option>
              <option value="2">What is the name of your favorite pet?</option>
              <option value="3">Who is your favorite teacher in high school?</option>
            </select><label for="requiredQuestion1">Question 1</label></div><small id="rpQuestionError1" class="rp-identity-error" aria-live="polite"></small></div>
        <div><div class="input-field password-field"><input id="requiredAnswer1" type="password" maxlength="72" autocomplete="off" placeholder=" " aria-describedby="rpAnswerError1"><label for="requiredAnswer1">Answer 1</label><i class="fas fa-eye-slash toggle-password"></i></div><small id="rpAnswerError1" class="rp-identity-error" aria-live="polite"></small></div>
      </div>
      <div class="rp-question-row">
        <div><div class="input-field rp-question-field"><select id="requiredQuestion2" aria-describedby="rpQuestionError2" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="4">What is your mother’s maiden name?</option>
              <option value="5">What city were you born in?</option>
              <option value="6">What is your favorite color?</option>
            </select><label for="requiredQuestion2">Question 2</label></div><small id="rpQuestionError2" class="rp-identity-error" aria-live="polite"></small></div>
        <div><div class="input-field password-field"><input id="requiredAnswer2" type="password" maxlength="72" autocomplete="off" placeholder=" " aria-describedby="rpAnswerError2"><label for="requiredAnswer2">Answer 2</label><i class="fas fa-eye-slash toggle-password"></i></div><small id="rpAnswerError2" class="rp-identity-error" aria-live="polite"></small></div>
      </div>
      <div class="rp-question-row">
        <div><div class="input-field rp-question-field"><select id="requiredQuestion3" aria-describedby="rpQuestionError3" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="7">What is your favorite food?</option>
              <option value="8">What was the name of your first school?</option>
              <option value="9">What is your father’s middle name?</option>
            </select><label for="requiredQuestion3">Question 3</label></div><small id="rpQuestionError3" class="rp-identity-error" aria-live="polite"></small></div>
        <div><div class="input-field password-field"><input id="requiredAnswer3" type="password" maxlength="72" autocomplete="off" placeholder=" " aria-describedby="rpAnswerError3"><label for="requiredAnswer3">Answer 3</label><i class="fas fa-eye-slash toggle-password"></i></div><small id="rpAnswerError3" class="rp-identity-error" aria-live="polite"></small></div>
      </div>
    </div>

    <div class="rp-password-group">
      <div class="input-field password-field" id="requiredNewPasswordField">
        <input type="password" id="requiredNewPassword" placeholder=" " autocomplete="new-password" />
        <label>New Password</label>
        <i class="fas fa-eye-slash toggle-password"></i>
      </div>
      <div class="rp-strength-bar" aria-hidden="true"><span id="rpStrengthBar"></span></div>
      <div class="rp-password-feedback" id="rpStrengthHint" aria-live="polite"></div>
    </div>

    <div class="rp-password-group rp-confirm-group">
      <div class="input-field password-field" id="requiredConfirmPasswordField">
        <input type="password" id="requiredConfirmPassword" placeholder=" " autocomplete="new-password" />
        <label>Confirm New Password</label>
        <i class="fas fa-eye-slash toggle-password"></i>
      </div>
      <div class="rp-password-feedback" id="rpMatchHint" aria-live="polite"></div>
    </div>

    <div id="requiredPasswordMessage" class="field-error" role="alert"></div>
    <button type="button" class="btn_submit" id="requiredPasswordSubmit">Change Password</button>
    <a class="required-password-logout" href="logout.php">Log out instead</a>
  </div>
</div>



<style>
  /* ── Required-password overlay ── */
  .required-password-modal {
    display: none;
    position: fixed;
    /* fill the whole viewport, not just the card */
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.72);
    align-items: center;
    justify-content: center;
    padding: 24px 18px;
    box-sizing: border-box;
    backdrop-filter: blur(3px);
  }

  .required-password-modal.active {
    display: flex;
    animation: rpFadeIn 0.25s ease forwards;
  }

  @keyframes rpFadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  .required-password-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 20px;
    padding: 32px 28px 28px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    box-sizing: border-box;
    max-height: calc(100dvh - 48px);
    overflow-y: auto;
    animation: rpSlideUp 0.28s ease forwards;
  }

  @keyframes rpSlideUp {
    from {
      transform: translateY(20px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .required-password-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e8f3e5, #d4edda);
    color: #477246;
    display: grid;
    place-items: center;
    margin: 0 auto 14px;
    font-size: 24px;
    box-shadow: 0 4px 14px rgba(71, 114, 70, 0.18);
  }

  .required-password-card h2 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
    line-height: 1.3;
  }

  .required-password-card>p {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 20px;
    line-height: 1.5;
  }

  .required-password-card .input-field {
    margin-bottom: 0;
    text-align: left;
  }

  .rp-password-group {
    position: relative;
    margin-bottom: 14px;
  }

  .rp-confirm-group {
    margin-top: 8px;
  }

  /* Same three-pixel strength indicator used by Registration. */
  .rp-strength-bar {
    position: relative;
    z-index: 2;
    width: 100%;
    height: 3px;
    margin-top: -3px;
    background: #e0e0e0;
    border-radius: 0 0 5px 5px;
    overflow: hidden;
  }

  .rp-strength-bar span {
    display: none;
    height: 3px;
    margin: 0;
    border: 0;
    border-radius: 0 0 5px 5px;
    width: 0%;
    background: red;
    transition: width 0.5s ease, background-color 0.5s ease;
  }

  .rp-password-feedback {
    font-size: 11px;
    line-height: 1.35;
    text-align: center;
    min-height: 15px;
    margin-top: 2px;
    font-weight: 500;
  }

  .required-password-card .field-error {
    min-height: 18px;
    color: #dc2626;
    font-size: 12px;
    margin: 4px 0 8px;
    text-align: left;
    line-height: 1.4;
  }

  .required-password-card .btn_submit {
    width: 100%;
    margin-top: 6px;
    padding: 12px;
    font-size: 15px;
    border-radius: 10px;
  }

  .required-password-logout {
    display: inline-block;
    margin-top: 14px;
    font-size: 12px;
    color: #94a3b8;
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.15s;
  }

  .required-password-logout:hover {
    color: #dc2626;
  }

  #requiredPasswordModal .required-password-card{width:min(900px,100%);max-width:900px;max-height:none;overflow:visible;padding:20px 26px;box-sizing:border-box;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px 18px}
  #requiredPasswordModal{overflow:visible;padding:12px}
  #requiredPasswordModal .required-password-icon{display:none}
  #requiredPasswordTitle,.rp-identity-fields,#requiredPasswordMessage,.required-password-logout{grid-column:1/-1}
  #requiredPasswordTitle{margin:0 0 6px;font-size:22px}
  .rp-identity-fields{display:grid;gap:6px;text-align:left}
  .rp-email-row{width:calc((100% - 18px)/2);min-width:0}
  @media(max-width:540px){.rp-email-row{width:100%}}
  .rp-question-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px}
  #requiredPasswordModal .input-field{margin:8px 0 0;min-width:0;height:42px}
  #requiredPasswordModal .input-field input,#requiredPasswordModal .input-field select{width:100%;box-sizing:border-box;font:inherit;font-size:13px}
  #requiredPasswordModal .rp-question-field label{top:-8px;left:10px;transform:none;font-size:11px;background:#f8f7ef;padding:0 5px;pointer-events:none}
  .rp-identity-error{display:block;min-height:14px;font-size:11px;line-height:1.3;color:#b91c1c;margin-top:3px}
  #requiredPasswordModal [aria-invalid="true"]{outline:1px solid #b91c1c}
  .rp-question-note{margin:2px 0;font-size:12px;color:#64748b}
  #requiredPasswordModal .rp-password-group{margin:0}
  #requiredPasswordSubmit{grid-column:1/-1;margin:0}
  #requiredPasswordModal .required-password-logout{margin:0}
  @media(max-width:540px){#requiredPasswordModal .required-password-card{padding:16px;gap:8px} .rp-question-row{gap:10px} #requiredPasswordTitle{font-size:18px} #requiredPasswordModal .input-field select{font-size:11px}}
</style>

<script>
  (() => {
    const modal = document.getElementById('requiredPasswordModal');
    const submit = document.getElementById('requiredPasswordSubmit');
    const message = document.getElementById('requiredPasswordMessage');
    const newPassInput = document.getElementById('requiredNewPassword');
    const confPassInput = document.getElementById('requiredConfirmPassword');
    const newPassField = document.getElementById('requiredNewPasswordField');
    const confPassField = document.getElementById('requiredConfirmPasswordField');
    const strengthBar = document.getElementById('rpStrengthBar');
    const strengthHint = document.getElementById('rpStrengthHint');
    const matchHint = document.getElementById('rpMatchHint');
    let targetRedirect = 'index.php?action=dashboard';
    const identityInputs = {email: document.getElementById('requiredEmail')};
    const identityErrors = {email: document.getElementById('rpEmailError')};
    for (let i = 1; i <= 3; i++) {
      identityInputs[`security_question_${i}`] = document.getElementById(`requiredQuestion${i}`);
      identityInputs[`security_answer_${i}`] = document.getElementById(`requiredAnswer${i}`);
      identityErrors[`security_question_${i}`] = document.getElementById(`rpQuestionError${i}`);
      identityErrors[`security_answer_${i}`] = document.getElementById(`rpAnswerError${i}`);
    }
    function showIdentityError(key, error) {
      identityErrors[key].textContent = error || '';
      identityInputs[key].setAttribute('aria-invalid', error ? 'true' : 'false');
    }
    function validateIdentity(key) {
      const value = identityInputs[key].value.trim();
      let error = '';
      if (key === 'email' && (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || value.length > 150)) error = 'Enter a valid email address, e.g. name@example.com.';
      if (key.startsWith('security_question_') && !/^[1-9]$/.test(value)) error = 'Select a question.';
      if (key.startsWith('security_answer_') && (!value || new TextEncoder().encode(value).length > 72)) error = 'Enter your saved answer (1?72 bytes).';
      showIdentityError(key, error);
      return !error;
    }
    Object.keys(identityInputs).forEach(key => {
      ['input', 'blur', 'change'].forEach(event => identityInputs[key].addEventListener(event, () => { validateIdentity(key); message.textContent = ''; }));
    });
    let emailCheckTimer;
    let emailCheckVersion = 0;
    function scheduleEmailCheck() {
      clearTimeout(emailCheckTimer);
      const version = ++emailCheckVersion;
      if (!validateIdentity('email')) return;
      const email = identityInputs.email.value.trim();
      emailCheckTimer = setTimeout(async () => {
        try {
          const response = await fetch('index.php?action=checkEmail', {
            method: 'POST', credentials: 'same-origin',
            body: new URLSearchParams({email, purpose: 'required_password'})
          });
          const result = await response.json();
          if (version === emailCheckVersion && identityInputs.email.value.trim() === email) {
            showIdentityError('email', result.available ? '' : result.message);
          }
        } catch (_) { /* Final submission also verifies the email on the server. */ }
      }, 400);
    }
    ['input', 'blur', 'change'].forEach(event => identityInputs.email.addEventListener(event, scheduleEmailCheck));


    // --- Password strength meter ---
    const criteria = [{
        test: (v) => v.length >= 8,
        label: '8+ chars'
      },
      {
        test: (v) => /[a-z]/.test(v),
        label: 'lowercase'
      },
      {
        test: (v) => /[A-Z]/.test(v),
        label: 'uppercase'
      },
      {
        test: (v) => /\d/.test(v),
        label: 'number'
      },
      {
        test: (v) => /[!@#$%^&*(),.?":{}|<>_\-]/.test(v),
        label: 'special char'
      },
    ];

    function updateRegistrationStyleStrength(value) {
      const val = value || '';
      if (/\s/.test(val)) {
        newPassField.style.borderColor = 'red';
        strengthBar.style.display = 'none';
        strengthHint.style.color = 'red';
        strengthHint.textContent = 'Password cannot contain spaces';
        return;
      }
      if (!val) {
        newPassField.style.borderColor = '';
        strengthBar.style.display = 'none';
        strengthHint.textContent = '';
        return;
      }

      const score = criteria.filter(c => c.test(val)).length;
      const missing = criteria.filter(c => !c.test(val)).map(c => c.label);
      strengthBar.style.display = 'block';
      if (score < 4) {
        newPassField.style.borderColor = 'red';
        strengthBar.style.width = '25%';
        strengthBar.style.backgroundColor = 'red';
        strengthHint.style.color = 'red';
        strengthHint.textContent = 'Missing: ' + missing.join(', ');
      } else if (score === 4) {
        newPassField.style.borderColor = 'orange';
        strengthBar.style.width = '75%';
        strengthBar.style.backgroundColor = 'orange';
        strengthHint.style.color = 'orange';
        strengthHint.textContent = 'Add ' + missing.join(', ') + ' for stronger password';
      } else {
        newPassField.style.borderColor = '#23ad5c';
        strengthBar.style.width = '100%';
        strengthBar.style.backgroundColor = '#23ad5c';
        strengthHint.style.color = '#23ad5c';
        strengthHint.textContent = 'Your password is strong';
      }
    }

    function updatePasswordMatch() {
      const password = newPassInput.value;
      const confirm = confPassInput.value;
      if (/\s/.test(confirm)) {
        confPassField.style.borderColor = 'red';
        matchHint.style.color = 'red';
        matchHint.textContent = 'Confirm password cannot contain spaces';
      } else if (!confirm) {
        confPassField.style.borderColor = '';
        matchHint.textContent = '';
      } else if (password === confirm) {
        confPassField.style.borderColor = '#23ad5c';
        matchHint.style.color = '#23ad5c';
        matchHint.textContent = 'Password match.';
      } else {
        confPassField.style.borderColor = 'red';
        matchHint.style.color = 'red';
        matchHint.textContent = 'Password does not match.';
      }
    }

    newPassInput.addEventListener('input', () => {
      updateRegistrationStyleStrength(newPassInput.value);
      updatePasswordMatch();
      message.textContent = '';
    });
    confPassInput.addEventListener('input', () => {
      updatePasswordMatch();
      message.textContent = '';
    });

    // --- Show modal ---
    window.showRequiredPasswordModal = function(redirect) {
      targetRedirect = redirect || targetRedirect;

      // The .form-container has a CSS transform animation which creates a new
      // stacking context — fixed-position children get clipped to that container
      // instead of the viewport. Move the modal to <body> to escape it.
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }

      modal.classList.add('active');
      const loginForm = document.querySelector('.login-form');
      if (loginForm) loginForm.style.display = 'none';

      // Check initial password value (e.g. if browser autofilled)
      updateRegistrationStyleStrength(newPassInput.value || '');
      updatePasswordMatch();

      identityInputs.email.focus();
    };

    // --- Submit handler ---
    async function doSubmit() {
      if (submit.disabled) return;
      const identityValid = Object.keys(identityInputs).map(validateIdentity).every(Boolean);
      if (!identityValid) return;
      const password = newPassInput.value;
      const confirm = confPassInput.value;
      message.textContent = '';
      if (!password) {
        message.textContent = 'Please enter a new password.';
        return;
      }
      if (!criteria.every(rule => rule.test(password)) || /\s/.test(password) || new TextEncoder().encode(password).length > 72) {
        message.textContent = 'Use 8?72 bytes with uppercase, lowercase, a number, and a special character; no spaces.';
        return;
      }
      if (password !== confirm) {
        message.textContent = 'Passwords do not match.';
        return;
      }
      submit.disabled = true;
      try {
        const body = new URLSearchParams({
          email: identityInputs.email.value.trim(),
          ...Object.fromEntries(Object.entries(identityInputs).map(([key, input]) => [key, input.value])),
          new_password: password,
          confirm_password: confirm
        });
        const response = await fetch('index.php?action=changeRequiredPassword', {
          method: 'POST',
          body,
          credentials: 'same-origin'
        });
        const data = await response.json();
        if (!data.success) {
          Object.entries(data.fieldErrors || {}).forEach(([key, error]) => { if (identityErrors[key]) showIdentityError(key, error); });
          message.textContent = data.message || 'Unable to change password.';
          submit.disabled = false;
          return;
        }
        window.location.href = data.redirect || targetRedirect;
      } catch (error) {
        message.textContent = 'Unable to connect. Please try again.';
        submit.disabled = false;
      }
    }

    submit.addEventListener('click', doSubmit);
    // Allow pressing Enter in either field to submit
    [...Object.values(identityInputs), newPassInput, confPassInput].forEach(inp => inp.addEventListener('keydown', e => {
      if (e.key === 'Enter') doSubmit();
    }));

    <?php if ($forcePasswordChange): ?>
      window.addEventListener('DOMContentLoaded', () => window.showRequiredPasswordModal());
    <?php endif; ?>
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
