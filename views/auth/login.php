<?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
  <p style="color: green; font-weight: bold; text-align:center;">
    🎉 Registration successful! Please log in.
  </p>
<?php endif; ?>


<form class="login-form">
  <h2>Login</h2>

  <div class="input-field">
    <input type="text" name="username" class="username" required placeholder=" " />
    <label>Email</label>
  </div>

  <div class="input-field">
    <input type="password" name="password" class="password" required placeholder=" " />
    <label>Password</label>
  </div>


  <button class="btn_submit">Login</button>

  <p class="toggle-link forgot-password" onclick="resetForgotLink()">
    <a href="index.php?action=forgot">Forgot Password?</a>
  </p>

  <p class="toggle-link">
    Don't have an account? <a href="index.php?action=register"><b>Register</b></a>
  </p>
  
  <!-- add login-message -->
  <div class="msg" id="login-message" role="status" aria-live="polite" style="text-align: center;" ></div>
  
</form>

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
