<?php
// require_once __DIR__ . '/../models/User.php';

// class AuthController {
    // private $userModel;

    // public function __construct() {
    //     $this->userModel = new User();
    // }

    // public function showLogin() {
    //     $formView = "login.php";
    //     require __DIR__ . '/../views/auth/auth.php';
    // }
    

    
    // public function loginUser() {
    //     session_start();

    //     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //         $username = $_POST['username'] ?? '';
    //         $password = $_POST['password'] ?? '';

    //         if (empty($username) || empty($password)) {
    //             echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    //             return;
    //         }

    //         $user = $this->userModel->findByUsername($username);

    //         if ($user && password_verify($password, $user['password_hash'])) {
    //             $_SESSION['user_id'] = $user['id_number'];
    //             $_SESSION['username'] = $user['username'];
    //             echo json_encode(['success' => true, 'redirect' => 'index.php?action=dashboard']);
    //         } else {
    //             echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    //         }
    //     } else {
    //         echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    //     }
    // }

    // public function showRegister() {
    //     require_once __DIR__ . '/../views/auth/register.php';
    // }

    // public function registerUser() {
    //     // Registration logic is in UserController, but could be moved here.
    // }

    // public function showForgotPassword() {
    //     require_once __DIR__ . '/../views/auth/forgot_password.php';
    // }
// }
