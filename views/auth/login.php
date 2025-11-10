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

  
if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
  <p style="color: green; font-weight: bold; text-align:center;">
    🎉 Registration successful! Please log in.
  </p>
<?php endif; ?>


<form class="login-form" method="POST" onsubmit="return false;">
  <h2>Login</h2>

    <p class="empty-mgs" ></p>

  <div class="input-field">
    <input type="text" name="username" class="login-email"  placeholder=" " />
    <label>Username</label>
  </div>

  <div class="input-field " >
    <input type="password" name="password" class="login-password" placeholder=" " />
  
    <label>Password</label>
  </div>


  <button class="btn_submit">Login</button>

  <p class="toggle-link login-forgot-password" onclick="resetForgotLink()">
    <a href="index.php?action=forgot">Forgot Password?</a>
  </p>

  <p class="toggle-link register-link">
    Don't have an account? <a href="index.php?action=register" ><b>Register</b></a>
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
    window.onpopstate = function () {
    history.go(1);
  };
</script>