<?php
/* ==========================================================
   START SESSION & REDIRECT IF ALREADY LOGGED IN
========================================================== */
session_start();

// Redirect logged-in users directly to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?action=dashboard');
    exit();
}


/* ==========================================================
   PREVENT BROWSER CACHING (for proper logout/login flow)
========================================================== */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>

<!-- ==========================================================
     FLASH MESSAGE (Shown After Successful Registration)
========================================================== -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <p style="
        color: green; 
        font-weight: bold; 
        text-align: center; 
        margin-bottom: 15px;
        background-color: #e9fbe9;
        border: 1px solid #a7d7a7;
        padding: 10px;
        border-radius: 6px;">
        <?= htmlspecialchars($_SESSION['flash_message']); ?>
    </p>
    <?php unset($_SESSION['flash_message']); // remove message after showing ?>
<?php endif; ?>


<!-- ==========================================================
     LOGIN FORM
========================================================== -->
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


<script>
  // Prevent navigation back to dashboard after logout
  (function() {
    // Check if we came from logout
    var urlParams = new URLSearchParams(window.location.search);
    var isLogout = urlParams.get('logout') === '1';
    
    if (isLogout) {
      // Replace current history entry to remove dashboard from browser history
      // This prevents the back button from going back to dashboard
      var cleanUrl = window.location.pathname + '?action=login';
      if (window.history.replaceState) {
        window.history.replaceState(null, null, cleanUrl);
      }
    }
    
    // Push current state to prevent back navigation
    window.history.pushState(null, null, window.location.href);
    
    // Handle back button press - prevent navigation to dashboard
    window.onpopstate = function(event) {
      // Push forward again to prevent back navigation
      window.history.pushState(null, null, window.location.href);
      
      // If somehow navigating to dashboard, redirect to login immediately
      // Server-side will also check session, but this adds client-side protection
      var currentUrl = window.location.href;
      if (currentUrl.indexOf('action=dashboard') !== -1) {
        window.location.replace('index.php?action=login');
      }
    };
    
    // Handle browser back/forward cache
    window.addEventListener('pageshow', function(event) {
      // If page was loaded from cache, check URL
      if (event.persisted) {
        var currentUrl = window.location.href;
        // If cached page is dashboard, redirect to login (session will be invalid)
        if (currentUrl.indexOf('action=dashboard') !== -1) {
          window.location.replace('index.php?action=login');
        }
      }
    });
  })();
</script>
