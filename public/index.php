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
    case 'forgot':
        $controller->showForgotPassword();
        break;
    case 'registerUser': // 👈 new route
        $controller->registerUser();
        break;
    default:
        $controller->showLogin();
        break;
}
