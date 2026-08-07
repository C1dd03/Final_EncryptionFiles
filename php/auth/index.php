<?php
// require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/UserController.php';

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = strtolower($_SESSION['role'] ?? 'user');
        if ($role === 'superadmin' || $role === 'admin') {
            header("Location: ../super_admin/dashboard.php");
            exit();
        } else {
            require_once __DIR__ . '/../../php/home/homepage.php';
        }
        break;

    case 'superadmin_dashboard':
        header("Location: ../super_admin/dashboard.php");
        exit();


    /* ========================== ADD LOGOUT ======================== */
    case 'logout':
        $userController->logout();


    case 'verifyId':
        $userController->verifyId();
        break;


    case 'verifySecurityAnswers':
        $userController->verifySecurityAnswers();
        break;

    case 'validateSecurityAnswer':
        $userController->validateSecurityAnswer();
        break;

    case 'resetPassword':
        $userController->resetPassword();
        break;

    case 'checkUsername':
        $userController->checkUsername();
        break;

    case 'checkEmail':
        $userController->checkEmail();
        break;

    /* ========================== MANAGE ADMINS ROUTES ======================== */
    case 'getAdmins':
        $userController->getAdmins();
        break;
    case 'getAdminDetail':
        $userController->getAdminDetail();
        break;
    case 'addAdmin':
        $userController->addAdmin();
        break;
    case 'updateAdmin':
        $userController->updateAdmin();
        break;
    case 'toggleBlockAdmin':
        $userController->toggleBlockAdmin();
        break;
    case 'deleteAdmin':
        $userController->deleteAdmin();
        break;

    /* ========================== MANAGE USERS ROUTES ======================== */
    case 'getUsers':
        $userController->getUsers();
        break;
    case 'getUserDetail':
        $userController->getUserDetail();
        break;
    case 'addStandardUser':
        $userController->addStandardUser();
        break;
    case 'updateStandardUser':
        $userController->updateStandardUser();
        break;
    case 'toggleBlockUser':
        $userController->toggleBlockUser();
        break;
    case 'deleteStandardUser':
        $userController->deleteStandardUser();
        break;

    /* ========================== BLOCK LIST ROUTES ======================== */
    case 'getBlockList':
        $userController->getBlockList();
        break;
    case 'getBlockDetail':
        $userController->getBlockDetail();
        break;
    case 'unblockAccount':
        $userController->unblockAccount();
        break;

    /* ========================== AUDIT LOGS ROUTES ======================== */
    case 'getAuditLogs':
        $userController->getAuditLogs();
        break;

    default:
        $userController->showLogin();
        break;
}

