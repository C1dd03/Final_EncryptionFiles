<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: index.php?action=login');
//     exit();
// }
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Pragma: no-cache");
// header("Expires: 0");
require_once __DIR__ . '/../auth/session_protect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Welcome to your Dashboard!</h2>
        <p>You are successfully logged in.</p>
        <!-- <a href="index.php?action=logout">Logout</a> -->
        <h1>Welcome to your Dashboard, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
        <a href="index.php?action=logout">Logout</a>
    </div>

    <script>
  // Prevent going back to login after logout
    if (window.history && window.history.pushState) {
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function () {
        window.history.pushState(null, "", window.location.href);
    };
    }
</script>
</body>
</html>
