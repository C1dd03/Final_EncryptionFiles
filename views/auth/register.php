<form class="register-form" action="index.php?action=registerUser" method="post" onsubmit="return handleSubmit(this)" novalidate>
   <?php if (!empty($error)): ?>
      <p style="color:red; margin-bottom:10px;"><?php echo $error; ?></p>
  <?php endif; ?>
  
  <h2>Registration</h2>

  <!-- Step 1: Personal Info -->
  <div class="step step-1 active">
    <span>Personal Information </span>

    <div class="input-field static-label">
      <label>User ID <small>*</small></label>
      <input type="text" name="id_number" value="<?php echo htmlspecialchars($nextId ?? ''); ?>" readonly />
    </div>

    <div class="input-field">
      <input type="text" name="first_name" required placeholder=" " />
      <label class="label">First Name</label>
    </div>

    <div class="input-field optional">
      <input type="text" name="middle_name" placeholder=" " />
      <label>Middle Name</label>
    </div>

    <div class="input-field">
      <input type="text" name="last_name" required placeholder=" " />
      <label>Last Name</label>
    </div>



    <div class="input-field">
      <!-- Dropdown -->
      <select id="extension" name="extension">
        <option value="">Select Extension</option>
    <option value="Jr.">Jr.</option>
    <option value="Sr.">Sr.</option>
    <option value="Other">Other</option>
  </select>
  
  <!-- Hidden text input -->
  <input
  type="text"
  id="other_extension"
  name="other_extension"
  placeholder="Enter Roman Numeral (I - X)"
  style="display: none; margin-top: 8px;"
  />

</div>


    
    <div class="age-sex">
      <div class="input-field birthdate-field">
        <input type="date" 
         id="birthdate" 
         name="birthdate" 
         required
         placeholder=" "
         max="<?= date('Y-m-d', strtotime('-18 years')) ?>" />
        <label for="birthdate">Birthdate</label>
        <div class="birthdate-tooltip">
          Must be 18+ to register.
        </div>
      </div>

      <!-- Auto-calculated age field -->
      <div class="input-field static-label">
        <label for="age">Age <small>*</small></label>
        <input type="text" id="age" name="age" readonly>
      </div>
      
      <div class="input-field">
        <select name="gender" required>
          <option value="" disabled selected hidden></option>
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>
        <label>Gender</label>
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

    <div class="input-field">
      <input type="text" name="street" required placeholder=" " />
      <label>Purok/Street</label>
    </div>

    <div class="input-field">
      <input type="text" name="barangay" required placeholder=" " />
      <label>Barangay</label>
    </div>

    <div class="input-field">
      <input type="text" name="city" required placeholder=" " />
      <label>Municipal/City</label>
    </div>

    <div class="input-field">
      <input type="text" name="province" required placeholder=" " />
      <label>Province</label>
    </div>

    <div class="input-field">
      <input type="text" name="country" required placeholder=" " />
      <label>Country</label>
    </div>

    <div class="input-field">
      <input type="text" name="zip" required placeholder=" " />
      <label>Zip Code</label>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(2)">&lt; Prev</button>
      <button type="button" class="btn next-btn" onclick="nextStep(2)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 3: Security Questions -->
  <div class="step step-3">
  <span>Security Questions</span>

  <div class="input-field">
    <input type="password" id="security_q1" name="security_q1" required placeholder=" " />
    <label>Who was your best friend in elementary school?</label>
    <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('security_q1', this)"></i>
  </div>

  <div class="input-field">
    <input type="password" id="security_q2" name="security_q2" required placeholder=" " />
    <label>What was the name of your favorite pet?</label>
    <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('security_q2', this)"></i>
  </div>

  <div class="input-field">
    <input type="password" id="security_q3" name="security_q3" required placeholder=" " />
    <label>Who was your favorite high school teacher?</label>
    <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('security_q3', this)"></i>
  </div>

  <div style="display: flex; justify-content: space-between; gap: 10px">
    <button type="button" class="btn prev-btn" onclick="prevStep(3)">&lt; Prev</button>
    <button type="button" class="btn next-btn" onclick="nextStep(3)">Next &gt;</button>
  </div>
</div>


  <!-- Step 4: Account Info -->
  <div class="step step-4">
    <span>Account Information</span>

    <div class="input-field">
      <input type="text" name="username" required placeholder=" " />
      <label>Username</label>
    </div>


    <div> <!-- Password container  -->
      <div class="pass-input-field">
        <input type="password" name="password" class="password" required placeholder=" " />
        <label>Password</label>
      </div>
      <!-- Password  strenght container bar -->
      <div class="password-strenght-container">
        <div class="password-strenght"></div>
      </div>
      <!-- Password Strength Message -->
      <div id="message"></div>
      <div id="password-guide" style="font-size: 11px; margin-top: 3px; display: none;"></div>

    </div>

    <div class="input-field">
      <input type="password" name="confirm_password" required placeholder=" " />
      <label>Re-enter-password</label>
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
