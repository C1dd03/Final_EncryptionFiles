<form class="register-form" action="index.php?action=registerUser" method="post" onsubmit="return handleSubmit(this)" novalidate <?php if (!empty($showOtpStep)) echo 'style="display:none;"'; ?>>
  <?php if (!empty($error)): ?>
    <p style="color:red; margin-bottom:10px;"><?php echo $error; ?></p>
  <?php endif; ?>

  <h2>Registration</h2>
  <div style="display: flex; justify-content: space-around; align-items: center;">
    <div class=line2></div>
    <div class=number>1</div>
    <div class=line></div>
    <div class=number>2</div>
    <div class=line></div>
    <div class=number>3</div>
    <div class=line></div>
    <div class=number>4</div>
    <div class=line2></div>
  </div>
  <div class="title-container" style="display: flex; justify-content:space-between; align-items: center;padding:0px 12px 0px 12px">

    <div class=title>Personal Information</div>
    <div class=line></div>
    <div class=title>Address Information</div>
    <div class=line></div>
    <div class=title>Security Questions</div>
    <div class=line></div>
    <div class=title>Account Information</div>
  </div>
  <!-- Step 1: Personal Info -->
  <div class="step step-1 active">
    <span>Personal Information </span>
    <div style="display:flex;align-items:center; gap: 15px">
      <div style="display:flex;  width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field static-label">
            <label>User ID <small>*</small></label>
            <input
              type="text"
              name="id_number"
              value="<?php echo htmlspecialchars($nextId ?? ''); ?>"
              readonly style="background-color: transparent;" />
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="first_name" required placeholder=" " />
            <label class="label">First Name</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field optional">
            <input type="text" name="middle_name" placeholder=" " />
            <label>Middle Name</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="last_name" required placeholder=" " />
            <label>Last Name</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
      </div>

      <div style="display:flex; ; width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field optional">
            <input
              type="text"
              name="extension"
              id="extension_input"
              placeholder=" "
              pattern="^(Jr\.?|Sr\.?|I|II|III|IV|V|VI|VII|VIII|IX|X)$"
              title="Allowed: Jr., Sr., I, II, III, IV, V, VI, VII, VIII, IX, X" />
            <label for="extension_input">Name Extension</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <!-- <div class="age-sex"> -->
        <div class="age-sex-column form-field">
          <div class="input-field birthdate-field">
            <input
              type="date"
              id="birthdate"
              name="birthdate"
              required
              placeholder=" "
              max="<?= date('Y-m-d', strtotime('-18 years')) ?>" />
            <label for="birthdate">Birthdate</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
          <div class="birthdate-tooltip">Must be 18+ to register.</div>
        </div>

        <!-- Auto-calculated age field -->
        <div class="age-sex-column form-field">
          <div class="input-field static-label">
            <label for="age">Age <small>*</small></label>
            <input type="text" id="age" name="age" readonly style="background-color: transparent;" />
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="age-sex-column form-field">
          <div class="input-field">
            <select name="gender" required>
              <option value="" disabled selected hidden></option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
            <label>Gender</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
        <!-- </div> -->
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: top">
      <label class="message">Click Next to Continue</label>
      <button type="button" class="btn next-btn" onclick="nextStep(1)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 2: Address Info -->
  <div class="step step-2">
    <span>Address Information</span>
    <div style="display:flex;align-items:center; gap: 15px">
      <div style="display:flex;   width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field">
            <input type="text" name="street" required placeholder=" " />
            <label>Purok/Street</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="barangay" required placeholder=" " />
            <label>Barangay</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="city" required placeholder=" " />
            <label>Municipal/City</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
      </div>
      <div style="display:flex;  width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field">
            <input type="text" name="province" required placeholder=" " />
            <label>Province</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="country" required placeholder=" " />
            <label>Country</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="number" name="zip" required placeholder=" " />
            <label>Zip Code</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(2)">&lt; Prev</button>
      <button type="button" class="btn next-btn" onclick="nextStep(2)">Next &gt;</button>
    </div>

  </div>
  <!-- Step 3: Security Questions -->
  <div class="step step-3">
    <span>Security Questions</span>
    <div style="display:flex;align-items:center; gap: 15px;">
      <div style="display:flex; width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field">
            <select name="security_question_1" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="1">Who is your best friend in elementary?</option>
              <option value="2">What is the name of your favorite pet?</option>
              <option value="3">Who is your favorite teacher in high school?</option>
            </select>
            <label style="width: 120px; text-align: start;">Question 1</label>
          </div>
        </div>
        <!-- <div class="form-field">
            <div class="input-field password-field">
              <input type="password" name="security_q1" required placeholder=" " />
              <label>Answer</label>
              <i class="fas fa-eye-slash toggle-password"></i>
            </div>
          </div> -->

        <div class="form-field">
          <div class="input-field">
            <select name="security_question_2" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="4">What is your mother’s maiden name?</option>
              <option value="5">What city were you born in?</option>
              <option value="6">What is your favorite color?</option>
            </select>
            <label style="width: 120px; text-align: start;">Question 2</label>
          </div>
        </div>
        <!-- <div class="form-field">
            <div class="input-field password-field">
              <input type="password" name="security_q2" required placeholder=" " />
              <label>Answer</label>
              <i class="fas fa-eye-slash toggle-password"></i>
            </div>
          </div> -->

        <div class="form-field">
          <div class="input-field">
            <select name="security_question_3" required>
              <option value="" disabled selected hidden>Select a question</option>
              <option value="7">What is your favorite food?</option>
              <option value="8">What was the name of your first school?</option>
              <option value="9">What is your father’s middle name?</option>
            </select>
            <label style="width: 120px; text-align: start;">Question 3</label>
          </div>
        </div>
        <!-- <div class="form-field">
          <div class="input-field password-field">
            <input type="password" name="security_q3" required placeholder=" " />
            <label>Answer</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
        </div> -->
      </div>
      <div style="display:flex;  width: 100%; flex-direction: column; ">
        <!-- answer1 -->
        <div class="form-field">
          <div class="input-field password-field">
            <input type="password" name="security_q1" required placeholder=" " />
            <label>Answer</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
        <!-- answer2 -->
        <div class="form-field">
          <div class="input-field password-field">
            <input type="password" name="security_q2" required placeholder=" " />
            <label>Answer</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
        <!-- answer3 -->
        <div class="form-field">
          <div class="input-field password-field">
            <input type="password" name="security_q3" required placeholder=" " />
            <label>Answer</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(3)">&lt; Prev</button>
      <button type="button" class="btn next-btn" onclick="nextStep(3)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 4: Account Info -->
  <div class="step step-4">
    <span>Account Information</span>
    <div style="display:flex;align-items:center; gap: 15px;">
      <div style="display:flex;  width: 100%; flex-direction: column; ">
        <div class="form-field">
          <div class="input-field">
            <input type="text" name="username" required placeholder=" " />
            <label>Username</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

        <div class="form-field">
          <div class="input-field">
            <input type="text" name="email" required placeholder=" " style="margin-top: 3px;" />
            <label>Email</label>
          </div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>
      </div>

      <div style="display:flex; width: 100%; flex-direction: column; gap: 0;">
        <div class="password-container" style="position: relative;">
          <div class="pass-input-field">
            <input type="password" name="password" class="password" required placeholder=" " />
            <label>Password</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
          <div class="input-error-container" style="font-size: 10px;" aria-live="polite"></div>
          <!-- Password strength container bar -->
          <div class="password-strength-container">
            <div class="password-strength"></div>
          </div>
          <!-- Password Strength Message -->
          <div id="message"></div>

        </div>

        <div class="form-field" style="position:relative;">
          <div class="input-field password-field">
            <input type="password" name="confirm_password" required placeholder=" " style="margin-top: 3px;" />
            <label>Re-enter-password</label>
            <i class="fas fa-eye-slash toggle-password"></i>
          </div>
          <div class="password-match-message" style="position: absolute; top: 40px; font-size: 11px; left: 50%; transform: translateX(-50%); text-align: center; width: 100%;"></div>
          <div class="input-error-container" aria-live="polite"></div>
        </div>

      </div>
    </div>
    <!-- <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(4)">&lt; Prev</button>
      <label class="Empty"></label>
    </div> -->
    <div style="display: flex; justify-content: space-between; gap: 10px; margin-bottom: 10px;">
      <button type="button" class="btn prev-btn" onclick="prevStep(4)">&lt; Prev</button>
      <label class="message">Click Previous to go Back</label>
    </div>

    <button class="btn_submit" type="submit">Register</button>
  </div>

  <p class="toggle-link">
    Already have an account? <a href="index.php?action=login"><b>Login</b></a>
  </p>
</form>

<!-- OTP Verification Step (shown after the account is created) -->
<div class="otp-verify-step" id="registerOtpStep" style="<?php echo !empty($showOtpStep) ? 'display:block;' : 'display:none;'; ?>">
  <h2>Verify Your Email</h2>
  <p class="otp-info">We sent a 6-digit code to<br /><strong><?php echo htmlspecialchars($otpMaskedEmail ?? ''); ?></strong></p>

  <?php if (!empty($otpIssueError)): ?>
    <p class="message-error" style="visibility:visible;"><?php echo htmlspecialchars($otpIssueError); ?></p>
  <?php endif; ?>

  <div class="form-field" style="margin-bottom: 10px;">
    <div class="input-field">
      <input type="text" name="otp" id="registerOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder=" " />
      <label>OTP Code</label>
    </div>
    <div class="field-error" id="register-otp-error" role="alert" style="display:none; color:#dc3545; font-size:12px; text-align:center;"></div>
  </div>

  <button class="btn_submit" id="verifyRegisterOtpBtn" type="button">Verify Email</button>

  <p class="otp-timer" id="registerOtpExpiry" style="text-align:center; font-size:12px; color:#6b7280; margin-top:6px;"></p>
  <p class="otp-timer" id="registerOtpResendTimer" style="text-align:center; font-size:12px; color:#6b7280;"></p>
  <div class="field-error" id="register-otp-message" role="alert" style="display:none; color:#dc3545; font-size:12px; text-align:center; margin-top:4px;"></div>
  <p class="otp-resend" style="text-align:center; font-size:13px; margin-top:8px;">
    Didn't receive the code? <a href="javascript:void(0)" id="registerResendOtp" style="display:none;">Resend OTP</a>
  </p>
  <div class="dev-otp-banner" id="registerOtpDevBanner" style="display:none; background:#fef9c3; color:#854d0e; font-size:12px; text-align:center; padding:8px; border-radius:6px; margin-top:8px;"></div>
  <p class="toggle-link" style="margin-top: 12px;">
    <a href="index.php?action=register">← Back to Registration</a>
  </p>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
  <div class="success-modal-content">
    <div class="success-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2 class="success-title">SUCCESS</h2>
    <p class="success-message">Congratulations, your account<br>has been successfully created.</p>
    <p class="success-id">Your ID: <strong id="userIdDisplay"></strong></p>
    <button class="success-btn" onclick="goToLogin()">Go to Login Form</button>
  </div>
</div>

<?php if (!empty($showOtpStep)): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function () {
    (function () {
      const otpInput = document.getElementById('registerOtpInput');
      const otpError = document.getElementById('register-otp-error');
      const otpMessage = document.getElementById('register-otp-message');
      const otpExpiryEl = document.getElementById('registerOtpExpiry');
      const otpResendTimerEl = document.getElementById('registerOtpResendTimer');
      const otpResendLink = document.getElementById('registerResendOtp');
      const otpDevBanner = document.getElementById('registerOtpDevBanner');
      const verifyBtn = document.getElementById('verifyRegisterOtpBtn');
      let otpTimers = [];

      function setError(text) {
        otpError.textContent = text;
        otpError.style.display = text ? 'block' : 'none';
      }

      function setMessage(text) {
        otpMessage.textContent = text;
        otpMessage.style.display = text ? 'block' : 'none';
      }

      // Countdowns + dev banner
      otpTimers.push(otpStartExpiryTimer(<?php echo (int)($otpExpiresIn ?? 300); ?>, otpExpiryEl));
      otpTimers.push(otpStartResendTimer(<?php echo (int)($otpCooldown ?? 60); ?>, otpResendLink, otpResendTimerEl));
      otpShowDevBanner(otpDevBanner, <?php echo json_encode($devOtp ?? null); ?>);
      if (otpInput) otpInput.focus();

      function verifyOtp() {
        const code = otpInput.value.trim();
        setError('');
        setMessage('');
        if (!/^\d{6}$/.test(code)) {
          setError('Please enter the 6-digit code.');
          return;
        }
        otpSetButtonLoading(verifyBtn, true, 'Verifying...', 'Verify Email');
        otpPost('index.php?action=verifyRegisterOtp', { otp: code })
          .then((data) => {
            if (data.success) {
              otpTimers.forEach((t) => clearInterval(t));
              const modal = document.getElementById('successModal');
              const userIdDisplay = document.getElementById('userIdDisplay');
              if (userIdDisplay) userIdDisplay.textContent = data.id_number || '';
              if (modal) modal.classList.add('show');
            } else {
              otpSetButtonLoading(verifyBtn, false, '', 'Verify Email');
              setError(data.message || 'Invalid code. Please try again.');
              otpInput.value = '';
              otpInput.focus();
            }
          })
          .catch((err) => {
            console.error('OTP verify error:', err);
            otpSetButtonLoading(verifyBtn, false, '', 'Verify Email');
            setMessage('An error occurred. Please try again.');
          });
      }

      function resendOtp() {
        setError('');
        setMessage('');
        otpResendLink.style.display = 'none';
        otpPost('index.php?action=resendOtp', {})
          .then((data) => {
            otpShowDevBanner(otpDevBanner, data.dev_otp);
            if (data.success) {
              setMessage(data.message || 'A new code has been sent.');
              otpTimers.push(otpStartExpiryTimer(data.expires_in || 300, otpExpiryEl));
              otpTimers.push(otpStartResendTimer(data.cooldown || 60, otpResendLink, otpResendTimerEl));
              otpInput.value = '';
              otpInput.focus();
            } else {
              setMessage(data.message || 'Could not resend the code.');
              otpTimers.push(otpStartResendTimer(data.cooldown || 60, otpResendLink, otpResendTimerEl));
            }
          })
          .catch((err) => {
            console.error('OTP resend error:', err);
            setMessage('An error occurred. Please try again.');
            otpResendLink.style.display = 'inline';
          });
      }

      verifyBtn.addEventListener('click', verifyOtp);
      otpInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          verifyOtp();
        }
      });
      otpResendLink.addEventListener('click', (e) => {
        e.preventDefault();
        resendOtp();
      });
    })();
    });
  </script>
<?php endif; ?>

<script>
  function goToLogin() {
    window.location.href = 'index.php?action=login';
  }
</script>
<script src="../../public/js/reset-form.js"></script>