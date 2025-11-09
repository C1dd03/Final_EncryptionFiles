<?php
// require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';

// $authController = new AuthController();
$userController = new UserController();

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'register':
        $userController->showRegister();
        break;
    case 'login':
        $userController->showLogin();
        break;
    case 'forgot':
        $userController->showForgotPassword();
        break;
    case 'registerUser':
        $userController->registerUser();
        break;
    case 'loginUser':
        $userController->loginUser();
        break;

    /* ========================== ADD DASHBOARD ======================== */
    case 'dashboard':
        require_once __DIR__ . '/../views/dashboard/dashboard.php';
        break;

    /* ========================== ADD LOGOUT ======================== */
    case 'logout':
        $userController->logout();

    
    case 'verifyId':
        $userController->verifyId();
        break;


    default:
        $userController->showLogin();
        break;
}
