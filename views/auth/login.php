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

    <p class="empty-mgs"></p>

    <div class="input-field">
        <input type="text" name="username" class="login-email" placeholder=" " required />
        <label>Username</label>
    </div>

    <div class="input-field">
        <input type="password" name="password" class="login-password" placeholder=" " required />
        <label>Password</label>
    </div>

    <button type="submit" class="btn_submit">Login</button>

    <p class="toggle-link login-forgot-password">
        <a href="index.php?action=forgot">Forgot Password?</a>
    </p>

    <p class="toggle-link register-link">
        Don't have an account?
        <a href="index.php?action=register"><b>Register</b></a>
    </p>

    <!-- Login message area -->
    <div class="msg" id="login-message" role="status" aria-live="polite" style="text-align: center;"></div>
</form>

<!-- ==========================================================
     JS: Prevent back navigation after login
========================================================== -->
<script>
    if (window.history && window.history.pushState) {
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, "", window.location.href);
        };
    }
</script>
