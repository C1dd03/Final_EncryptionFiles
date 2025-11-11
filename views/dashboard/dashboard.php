<?php
/* ========================== ADD PATH SESSION PROTECTION======================== */
require_once __DIR__ . '/../auth/session_protect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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
    // Prevent browser back button navigation and cached dashboard access after logout
    (function() {
        // Push current state to history to prevent back navigation to login
        window.history.pushState(null, null, window.location.href);
        
        // Handle back button press
        window.onpopstate = function(event) {
            // Push forward again to prevent going back
            window.history.pushState(null, null, window.location.href);
            
            // Server-side session protection will redirect if session is invalid
            // But reload the page to ensure we have fresh session data
            window.location.reload();
        };
        
        // Additional protection: handle browser back/forward cache
        window.addEventListener('pageshow', function(event) {
            // If page was loaded from cache (back/forward button), reload from server
            // This ensures session validation happens on server-side
            if (event.persisted) {
                window.location.reload();
            }
        });
    })();
</script>
</body>
</html>
