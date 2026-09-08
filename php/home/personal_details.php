<?php
require_once __DIR__ . '/../auth/session_protect.php';
if (strtolower($_SESSION['role'] ?? '') !== 'user') { header('Location: ../auth/index.php?action=dashboard'); exit(); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Personal Details</title><link rel="stylesheet" href="../../css/personal_details.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"><style>body{margin:0;background:#e7ebdd;padding:28px 16px}.user-details-nav{max-width:980px;margin:0 auto 14px;display:flex;justify-content:space-between}.user-details-nav a{color:#365a37;font-weight:700;text-decoration:none}</style></head>
<body><nav class="user-details-nav"><a href="../auth/index.php?action=dashboard">&larr; Back to Portal</a><a href="../auth/logout.php">Logout</a></nav>
<?php include __DIR__ . '/../shared/personal_details_content.php'; ?><script src="../../js/shared_validator.js?v=20260908i"></script><script src="../../js/personal_details.js?v=20260904"></script></body></html>

