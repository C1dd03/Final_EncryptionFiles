<form class="register-form" action="index.php?action=registerUser" method="post" onsubmit="return handleSubmit(this)" novalidate>
   <?php if (!empty($error)): ?>
      <p style="color:red; margin-bottom:10px;"><?php echo $error; ?></p>
  <?php endif; ?>
  
  <h2>Registration</h2>

  <!-- Step 1: Personal Info -->
  <div class="step step-1 active">
    <span>Personal Information </span>

    <div class="form-field">
      <div class="input-field static-label">
        <label>User ID <small>*</small></label>
        <input
          type="text"
          name="id_number"
          value="<?php echo htmlspecialchars($nextId ?? ''); ?>"
          readonly
        />
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

    

    <div class="form-field">
      <div class="input-field optional">
        <input
          type="text"
          name="extension"
          id="extension_input"
          placeholder=" "
          pattern="^(Jr\\.?|Sr\\.?|I|II|III|IV|V|VI|VII|VIII|IX|X)$"
          title="Allowed: Jr., Sr., I, II, III, IV, V, VI, VII, VIII, IX, X"
        />
        <label for="extension_input">Name Extension (e.g., Jr., Sr., I, II, ...)</label>
      </div>
      <div class="input-error-container" aria-live="polite"></div>
    </div>

    <div class="age-sex">
      <div class="age-sex-column form-field">
        <div class="input-field birthdate-field">
          <input
            type="date"
            id="birthdate"
            name="birthdate"
            required
            placeholder=" "
            max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
          />
          <label for="birthdate">Birthdate</label>
        </div>
        <div class="input-error-container" aria-live="polite"></div>
        <div class="birthdate-tooltip">Must be 18+ to register.</div>
      </div>

      <!-- Auto-calculated age field -->
      <div class="age-sex-column form-field">
        <div class="input-field static-label">
          <label for="age">Age <small>*</small></label>
          <input type="text" id="age" name="age" readonly />
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
    </div>

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <label class="message">Click Next to Continue</label>
      <button type="button" class="btn next-btn" onclick="nextStep(1)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 2: Address Info -->
  <div class="step step-2">
    <span>Address Information</span>

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

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(2)">&lt; Prev</button>
      <button type="button" class="btn next-btn" onclick="nextStep(2)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 3: Security Questions -->
  <div class="step step-3">
    <span>Security Questions</span>

    <div class="form-field">
      <div class="input-field">
        <select name="security_question_1" required>
          <option value="" disabled selected hidden>Select a question</option>
          <option value="1">Who is your best friend in elementary?</option>
          <option value="2">What is the name of your favorite pet?</option>
          <option value="3">Who is your favorite teacher in high school?</option>
        </select>
        <label>Question 1</label>
      </div>
    </div>
    <div class="form-field">
      <div class="input-field password-field">
        <input type="password" name="security_q1" required placeholder=" " />
        <label>Answer</label>
        <i class="fas fa-eye toggle-password"></i>
      </div>
    </div>

    <div class="form-field">
      <div class="input-field">
        <select name="security_question_2" required>
          <option value="" disabled selected hidden>Select a question</option>
          <option value="4">What is your mother’s maiden name?</option>
          <option value="5">What city were you born in?</option>
          <option value="6">What is your favorite color?</option>
        </select>
        <label>Question 2</label>
      </div>
    </div>
    <div class="form-field">
      <div class="input-field password-field">
        <input type="password" name="security_q2" required placeholder=" " />
        <label>Answer</label>
        <i class="fas fa-eye toggle-password"></i>
      </div>
    </div>

    <div class="form-field">
      <div class="input-field">
        <select name="security_question_3" required>
          <option value="" disabled selected hidden>Select a question</option>
          <option value="7">What is your favorite food?</option>
          <option value="8">What was the name of your first school?</option>
          <option value="9">What is your father’s middle name?</option>
        </select>
        <label>Question 3</label>
      </div>
    </div>
    <div class="form-field">
      <div class="input-field password-field">
        <input type="password" name="security_q3" required placeholder=" " />
        <label>Answer</label>
        <i class="fas fa-eye toggle-password"></i>
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

    <div class="form-field">
      <div class="input-field">
        <input type="text" name="username" required placeholder=" " />
        <label>Username</label>
      </div>
      <div class="input-error-container" aria-live="polite"></div>
    </div>

    <div class="form-field">
      <div class="input-field">
        <input type="text" name="email" required placeholder=" " />
        <label>Email</label>
      </div>
      <div class="input-error-container" aria-live="polite"></div>
    </div>

    <div class="password-container">
      <div class="pass-input-field">
        <input type="password" name="password" class="password" required placeholder=" " />
        <label>Password</label>
        <i class="fas fa-eye toggle-password"></i>
      </div>
      <!-- Password  strenght container bar -->
      <div class="password-strenght-container">
        <div class="password-strenght"></div>
      </div>
      <!-- Password Strength Message -->
      <div id ="message"></div>
      <div class="input-error-container" aria-live="polite"></div>
    </div>
    
    <div class="form-field">
      <div class="input-field password-field">
        <input type="password" name="confirm_password" required placeholder=" " />
        <label>Re-enter-password</label>
        <i class="fas fa-eye toggle-password"></i>
      </div>
      <div class="input-error-container" aria-live="polite"></div>
    </div>

    <!-- <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(4)">&lt; Prev</button>
      <label class="Empty"></label>
    </div> -->
    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(4)">&lt; Prev</button>
      <label class="message">Click Previous to go Back</label>
    </div>

    <button class="btn_submit" type="submit">Register</button>
  </div>

  <p class="toggle-link">
    Already have an account? <a href="index.php?action=login"><b>Login</b></a>
  </p>
</form>

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

<?php if (isset($registrationSuccess) && $registrationSuccess): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('successModal');
    const userIdDisplay = document.getElementById('userIdDisplay');
    userIdDisplay.textContent = '<?php echo htmlspecialchars($registeredId ?? ''); ?>';
    modal.classList.add('show');
});

function goToLogin() {
    window.location.href = 'index.php?action=login';
}
</script>
<?php endif; ?>
