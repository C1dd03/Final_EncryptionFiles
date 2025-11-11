<?php
require_once __DIR__ . '/../controllers/UserController.php';

$controller = new UserController();

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'register':
        $controller->showRegister();
        break;
    case 'login':
        $controller->showLogin();
        break;
    case 'loginUser':
        // Handle AJAX login requests
        $controller->loginUser();
        break;
    case 'forgot':
        $controller->showForgotPassword();
        break;
    case 'verifySecurityAnswer':
        $controller->verifySecurityAnswer();
        break;
    case 'updatePassword':
        $controller->updatePassword();
        break;
    case 'registerUser': // 👈 new route
        $controller->registerUser();
        break;
    case 'checkUsername':
        $controller->checkUsername();
        break;
    case 'checkId':
        $controller->checkId();
        break;
    case 'dashboard':
        // Show dashboard after successful login
        require __DIR__ . '/../views/dashboard/dashboard.php';
        break;
    case 'logout':
        $controller->logout();
        break;

    default:
        $controller->showLogin();
        break;
}
