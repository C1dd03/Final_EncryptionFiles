<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pick&Match | Ecommerce Website Design</title>
  <link rel="stylesheet" href="../../css/styles1.css" />
  <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" /> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body>
  <!-- Navbar -->
  <div class="container">
    <div class="navbar">
      <div class="logo">
        <img src="../../img/images/Agri-Connect.png" width="150px" />
      </div>

      <nav>
        <ul id="MenuItems">
          <li><a href="index.php">Home</a></li>

          <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
          <?php else: ?>
            <?php if (isset($page) && $page === 'login'): ?>
              <li><a href="index.php?action=register" class="nav-register-link">Register</a></li>
            <?php elseif (isset($page) && $page === 'register'): ?>
              <li><a href="index.php?action=login">Login</a></li>
            <?php elseif (isset($page) && $page === 'forgot-password'): ?>
              <li><a href="index.php?action=login">Login</a></li>
              <li><a href="index.php?action=register">Register</a></li>
            <?php else: ?>
              <li><a href="index.php?action=login">Login</a></li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
      </nav>
      <img src="../../img/images/leaf.png" width="40px" height="40px" />

    </div>
  </div>

  <!-- Account Page -->
  <div class="account-page">
    <div class="container">
      <div class="row">
        <div class="column">
          <img src="../../img/images/farmer02.png" width="100%" />
        </div>

        <div class="column">
          <input type="checkbox" id="toggle" hidden />
          <input type="checkbox" id="toggle-Changepassword" hidden />
          <div class="form-container">
            <?php
            // Ensure $formView is defined and the target view exists to avoid undefined variable notices.
            $formView = isset($formView) ? $formView : 'login.php';
            $viewPath = __DIR__ . '/' . $formView;
            if (!file_exists($viewPath)) {
              // If the specified view isn't found, try a common fallback (login.php) then throw.
              $fallback = __DIR__ . '/login.php';
              if (file_exists($fallback)) {
                require $fallback;
              } else {
                throw new RuntimeException("View not found: $viewPath");
              }
            } else {
              require $viewPath;
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="container">
      <div class="container text-center">
        <h5>🌿 ArgiConnect</h5>
        <p>Connecting Farmers and Buyers for a Better Future.</p>

        <small>
          © 2026 ArgiConnect. All Rights Reserved.
        </small>
      </div>
      <hr />
      <p class="copyright">Copyright 2025 - CSUCC sites</p>
    </div>
  </div>

  <script src="../../js/reset-form.js"></script>
  <script src="../../js/forgot_password.js"></script>
  <script src="../../js/validation.js"></script>
  <script src="../../js/login-incorrect-atmp.js"></script>


  <!---------------------------- Added restrict js code --->
  <script src="../../js/reset-form.php"></script>
  <script src="../../js/forgot_password.php"></script>
  <script src="../../js/validation.php"></script>
  <script src="../../js/login-incorrect-atmp.php"></script>


</body>

</html>