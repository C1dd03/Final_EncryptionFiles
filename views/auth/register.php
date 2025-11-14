<?php
  // Prevent caching so going back to this page shows a fresh, empty form
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header("Expires: 0");
?>
<?php if (!empty($successAlert)): ?>
  <script>
    // Wait until the page has fully loaded so the layout is visible
    window.addEventListener('load', function () {
      // Give the browser a moment to paint the page before blocking with alert
      requestAnimationFrame(function () {
        alert("<?= htmlspecialchars($successAlert) ?>");
        window.location.href = "index.php?action=login";
      });
    });
  </script>
<?php endif; ?>
<form class="register-form" action="index.php?action=registerUser" method="post" onsubmit="return handleSubmit(this)" novalidate>
   <?php if (!empty($error)): ?>
      <p style="color:red; margin-bottom:10px;"><?php echo $error; ?></p>
  <?php endif; ?>
  
  <h2>Registration</h2>

  <!-- Step 1: Personal Info -->
  <div class="step step-1 active">
    <span class="span">Personal Information </span>

    <div class="input-field static-label">
      <label>User ID <small>*</small></label>
      <input type="text" name="id_number" value="<?php echo htmlspecialchars($nextId ?? ''); ?>" readonly />
    </div>

    <div class="input-field">
      <input type="text" name="first_name" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label class="label">First Name</label>
    </div>

    <div class="input-field optional">
      <input type="text" name="middle_name" placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Middle Name</label>
    </div>

    <div class="input-field">
      <input type="text" name="last_name" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Last Name</label>
    </div>



    <div class="input-field">
      <!-- Dropdown -->
      <select id="extension" name="extension">
        <option value="">Select Extension</option>
        <option value="Jr">Jr</option>
        <option value="Sr">Sr</option>
        <option value="Other">Other</option>
      </select>
  
      <!-- Hidden text input -->
      <input type="text" id="other_extension" name="other_extension" placeholder="Enter Roman Numeral (I - X)" style="display: none;"
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
    <span class="span">Address Information</span>

    <div class="input-field">
      <input type="text" name="street" required placeholder=" " pattern="[A-Za-z0-9\s\-.]+" title="Letters, numbers, spaces, hyphen, and dot only" />
      <label>Purok/Street</label>
    </div>

    <div class="input-field">
      <input type="text" name="barangay" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Barangay</label>
    </div>

    <div class="input-field">
      <input type="text" name="city" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Municipal/City</label>
    </div>

    <div class="input-field">
      <input type="text" name="province" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Province</label>
    </div>

    <div class="input-field">
      <input type="text" name="country" required placeholder=" " pattern="[A-Za-z\s-]+" title="Letters, spaces, and hyphen only" />
      <label>Country</label>
    </div>

    <div class="input-field">
      <input type="text" name="zip" required placeholder=" " pattern="[0-9]{4,6}" inputmode="numeric" title="4-6 digit Zip Code" />
      <label>Zip Code</label>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" onclick="prevStep(2)">&lt; Prev</button>
      <button type="button" class="btn next-btn" onclick="nextStep(2)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 3: Security Questions -->
  <div class="step step-3">
  <span class="span">Security Questions</span>

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
    <span class="span">Account Information</span>

    <div class="input-field">
      <input type="text" name="username" required placeholder=" " />
      <label>Username</label>
    </div>


    <div> <!-- Password container  -->
      <div class="pass-input-field">
        <input type="password" name="password" class="password" id="password" required placeholder=" " />
        <label>Password</label>
        <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('password', this)"></i>
      </div>
      <!-- Password  strenght container bar -->
      <div class="password-strenght-container">
        <div class="password-strenght"></div>
      </div>
      <!-- Password Strength Message -->
      <div id="message"></div>
      <div id="password-guide" style="font-size: 12px; margin-top: 3px; display: none;"></div>

    </div>

    <div class="input-field">
      <input type="password" name="confirm_password" id="confirm_password" required placeholder=" " />
      <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('confirm_password', this)"></i>
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


<script>

document.addEventListener("DOMContentLoaded", () => {
  const usernameInput = document.querySelector("input[name='username']");
  const form = document.querySelector(".register-form");
  const USERNAME_MIN = 3;
  const USERNAME_MAX = 50;

  // Inline error span
  let usernameError = document.getElementById("username-error");
  if (!usernameError) {
    usernameError = document.createElement("span");
    usernameError.id = "username-error";
    usernameError.style.color = "red";
    usernameError.style.fontSize = "12px";
    usernameError.style.textDecoration = "none";
    usernameInput.parentNode.appendChild(usernameError);
  }

  const validateUsername = (username) => {
    if (username === "") return "Username cannot be empty.";
    if (username.length < USERNAME_MIN) return `Username must be at least ${USERNAME_MIN} characters.`;
    if (username.length > USERNAME_MAX) return `Username cannot exceed ${USERNAME_MAX} characters.`;
    if (!/^[A-Za-z]/.test(username)) return "Username: Must start with a letter.";
    if (!/^[A-Za-z0-9._-]+$/.test(username)) return "Username: Letters, numbers, ., _, - only.";
    if (/\.\.|__|--|\._|_\./.test(username)) return "Username: No consecutive symbols.";
    if (!/[a-zA-Z]/.test(username)) return "Username must contain at least one letter.";
    return null;
  };

  const nextField = usernameInput.closest(".input-field").nextElementSibling?.querySelector("input");

  usernameInput.addEventListener("blur", async () => {
    const username = usernameInput.value.trim();
    usernameError.textContent = "";

    // Run validation
    const error = validateUsername(username);
    if (error) {
      usernameError.textContent = error;
      usernameInput.dataset.taken = "true";
      if (nextField) nextField.disabled = true; // prevent moving
      usernameInput.focus();
      return;
    }

    // AJAX check
    try {
      const response = await fetch("index.php?action=checkUsername", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "username=" + encodeURIComponent(username)
      });
      const result = await response.text();
      if (result === "taken") {
        usernameError.textContent = "Username is already taken.";
        usernameInput.dataset.taken = "true";
        if (nextField) nextField.disabled = true;
        usernameInput.focus();
      } else {
        usernameError.textContent = "";
        usernameInput.dataset.taken = "false";
        if (nextField) nextField.disabled = false;
      }
    } catch (err) {
      console.error("AJAX Error:", err);
      usernameInput.dataset.taken = "false";
      if (nextField) nextField.disabled = false;
    }
  });

  form.addEventListener("submit", (e) => {
    const username = usernameInput.value.trim();
    const error = validateUsername(username);
    if (usernameInput.dataset.taken === "true" || error) {
      e.preventDefault();
      usernameError.textContent = error || "Please choose a different username.";
      usernameInput.focus();
    }
  });
});


</script>


<script>
  // Clear form on initial load to avoid stale values
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.register-form');
    if (form) form.reset();
  });

  // If user returns via browser back/forward cache, refresh to clear fields
  window.addEventListener('pageshow', function (event) {
    try {
      var nav = (performance && performance.getEntriesByType) ? performance.getEntriesByType('navigation')[0] : null;
      var isBFCache = event.persisted || (nav && nav.type === 'back_forward');
      if (isBFCache) {
        // Reload ensures fresh server-rendered form and new generated ID
        window.location.reload();
      }
    } catch (e) {
      // Fallback: reset the form
      var form = document.querySelector('.register-form');
      if (form) form.reset();
    }
  });
</script>