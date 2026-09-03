<?php
require_once __DIR__ . '/../auth/session_protect.php';
if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/index.php?action=login'); exit(); }
$pageTitle = 'Personal Details'; $activePage = 'personal_details';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Personal Details</title><link rel="stylesheet" href="../../css/admin/admin.css"><link rel="stylesheet" href="../../css/personal_details.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"></head>
<body><div class="admin-app"><?php include __DIR__ . '/includes/admin_sidebar.php'; ?><div class="admin-main"><?php include __DIR__ . '/includes/admin_header.php'; ?>
<main class="admin-content"><?php include __DIR__ . '/../shared/personal_details_content.php'; ?></main></div></div>
<script src="../../js/admin/admin.js"></script><script src="../../js/personal_details.js?v=20260903"></script></body></html>
