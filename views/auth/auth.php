<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pick&Match | Ecommerce Website Design</title>
  <link rel="stylesheet" href="/encryption/public/css/style.css" />
  <link rel="stylesheet" href="/encryption/public/css/style.php">

  <?php if (isset($formView) && $formView === 'forgot_password.php'): ?>
    <script defer src="/../encryption/public/js/forgot.js"></script>
  <?php endif; ?>
  <?php if (isset($formView) && $formView === 'register.php'): ?>
    <script defer src="/../encryption/public/js/reset-form.js"></script>
  <?php endif; ?>
  <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" /> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>
  <?php /* Ensure $page is defined to avoid warnings */ $page = $page ?? 'login'; ?>
  <!-- Navbar -->
  <div class="container">
    <div class="navbar">
      <div class="logo">
        <img src="/../encryption/public/img/images/dowlogo2.png" width="125px" />
      </div>

      <nav>
        <ul id="MenuItems">
    <li><a href="#">Home</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="index.php?action=dashboard">Dashboard</a></li>
      <li><a href="index.php?action=logout">Logout</a></li>
    <?php else: ?>
      <?php if ($page === 'login'): ?>
        <li><a href="index.php?action=register" class="nav-register-link">Register</a></li>
      <?php elseif ($page === 'register'): ?>
        <li><a href="index.php?action=login">Login</a></li>
      <?php else: ?>
        <li><a href="index.php?action=login">Login</a></li>
      <?php endif; ?>
    <?php endif; ?>
  </ul>
      </nav>
      <img src="/../encryption/public/img/images/cart.png" width="30px" height="30px" />
    </div>
  </div>

  <!-- Account Page -->
  <div class="account-page">
    <div class="container">
      <div class="row">
        <div class="column">
          <img src="/../encryption/public/img/images/image1.png" width="100%" />
        </div>

        <div class="column">
          <input type="checkbox" id="toggle" hidden />
          <input type="checkbox" id="toggle-Changepassword" hidden />
          <div class="form-container">
            <?php require __DIR__ . '/' . $formView; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="container">
      <div class="row">
        <div class="footer-col-1">
          <h3>Download Our App</h3>
          <p>Download App for Android and ios mobile phone.</p>
          <div class="app-logo">
            <img src="/../encryption/public/img/images/play-store.png" />
            <img src="/../encryption/public/img/images/app-store.png" />
          </div>
        </div>
        <div class="footer-col-2">
          <img src="/../encryption/public/img/images/dowlogoWhite.png" />
          <p>Our Purpose Is To Sustainably Make the Pleasure and Benefits of Sports Accessible to the Many.</p>
        </div>
        <div class="footer-col-3">
          <h3>Useful Links</h3>
          <ul>
            <li>Coupons</li>
            <li>Blog Post</li>
            <li>Return Policy</li>
            <li>Join Affliate</li>
          </ul>
        </div>
        <div class="footer-col-4">
          <h3>Follow Us</h3>
          <ul>
            <li>Facebook</li>
            <li>Twitter</li>
            <li>Instagram</li>
            <li>Youtube</li>
          </ul>
        </div>
      </div>
      <hr />
      <p class="copyright">Copyright 2025 - CSUCC sites</p>
    </div>
  </div>

  <?php if (isset($page) && $page === 'login'): ?>
    
    <script src="/../encryption/public/js/login-incorrect-atmp.js"></script>
    <script src="/../encryption/public/js/login-incorrect-atmp.php"></script>
  <?php else: ?>
     <script src="/../encryption/public/js/reset-form.js"></script>
     <script src="/encryption/public/js/reset-form.php"></script>
    <script src="/../encryption/public/js/validation.js"></script>
    <script src="/../encryption/public/js/validation.php"></script>s
    <script src="/../encryption/public/js/forgot.php"></script>
    <script src="/../encryption/public/js/forgot.js"></script>
    
  <?php endif; ?>





</body>
</html>


