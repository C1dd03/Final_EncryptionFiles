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
    <p class="flash-message" style="
        color: green; 
        font-weight: bold; 
        text-align: center; 
        margin-bottom: 15px;
        background-color: #e9fbe9;
        border: 1px solid #a7d7a7;
        padding: 10px;
        border-radius: 3px;
        transition: opacity 0.6s ease;">
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
        <input type="text" id="username" name="username" class="login-email" placeholder=" " required />
        <label>Username</label>
    </div>

    <div class="input-field pass-input-field" style="position: relative;">
        <input type="password" id="password" name="password" class="login-password" placeholder=" " required />
        <label>Password</label>
        <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('password', this)"></i>
    </div>

    <button type="submit" class="btn_submit">Login</button>

    <p class="toggle-link login-forgot-password" id="forgot-link">
        <a href="index.php?action=forgot">Forgot Password?</a>
    </p>

    <p class="toggle-link register-link">
        Don’t have an account?
        <a href="index.php?action=register"><b>Register</b></a>
    </p>

    <!-- Login message area -->
    <div class="msg" id="login-message" role="status" aria-live="polite" style="text-align: center;"></div>
</form>

<!-- ==========================================================
     JS SECTION
========================================================== -->
<script>
/* ==========================================================
   Prevent Back Navigation After Login
========================================================== */
if (window.history && window.history.pushState) {
    window.history.pushState(null, "", window.location.href);
    window.onpopstate = function () {
        window.history.pushState(null, "", window.location.href);
    };
}

/* ==========================================================
   Password Visibility Toggle
========================================================== */
function toggleVisibility(id, icon) {
    const input = document.getElementById(id);
    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";
    icon.classList.toggle("bi-eye", !isHidden);
    icon.classList.toggle("bi-eye-slash", isHidden);
}

/* ==========================================================
   Eye Icon Fade-In Logic
========================================================== */
document.addEventListener("DOMContentLoaded", () => {
    const inputFields = document.querySelectorAll(".input-field input, .pass-input-field input");

    inputFields.forEach(input => {
        const icon = input.parentElement.querySelector(".toggle-eye");
        if (!icon) return;

        // Hide eye icon initially
        icon.classList.remove("visible");

        input.addEventListener("input", () => {
            if (input.value.trim() !== "") {
                icon.classList.add("visible");
            } else {
                icon.classList.remove("visible");
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
                input.type = "password";
            }
        });

        input.addEventListener("blur", () => {
            if (input.value.trim() === "") {
                icon.classList.remove("visible");
            }
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const flash = document.querySelector(".flash-message");
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = "0";
      setTimeout(() => flash.remove(), 600);
    }, 3000);
  }
});
</script>


<!-- ==========================================================
     Recommended CSS
========================================================== -->
<style>
.flash-message {
  color: green;
  font-weight: bold;
  text-align: center;
  margin-bottom: 15px;
  background-color: #e9fbe9;
  border: 1px solid #a7d7a7;
  padding: 10px;
  border-radius: 3px;
  transition: opacity 0.6s ease;
}

.input-field, .pass-input-field {
  position: relative;
}

.toggle-eye {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #555;
  font-size: 18px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
}

.toggle-eye.visible {
  opacity: 1;
  pointer-events: auto;
}

.toggle-eye:hover {
  color: #111; /* Slight darken for feedback */
}
</style>
