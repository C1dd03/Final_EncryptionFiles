<?php

require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // Show Login Page
    public function showLogin() {
        $page = 'login';               // ✅ define first
        $formView = "login.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Show Register Page
    public function showRegister() {
        $page = 'register';            // ✅ define first
        $nextId = $this->userModel->generateIdNumber();
        $formView = "register.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    public function logout() {
        $formView = "logout.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Show Forgot Password Page
    public function showForgotPassword() {
        $formView = "forgot_password.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    // ✅ Handle Registration (POST)
    public function registerUser() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            // --- NAME VALIDATION ---
            $fields = [
                'First Name'  => $_POST['first_name'] ?? '',
                'Middle Name' => $_POST['middle_name'] ?? '',
                'Last Name'   => $_POST['last_name'] ?? '',
                'Extension'   => $_POST['extension'] ?? ''
            ];

            foreach ($fields as $label => $value) {
                $errors = array_merge($errors, $this->validateName(trim($value), $label));
            }

            // --- AGE VALIDATION ---
            if (!empty($_POST['birthdate'])) {
                $birthDate = new DateTime($_POST['birthdate']);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;

                if ($age < 18) {
                    $errors[] = "You must be 18 or older to register.";
                }
            }

            // --- USERNAME & PASSWORD VALIDATION ---
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $errors[] = "Username cannot contain 3 identical letters in a row.";
            }

            if (preg_match('/([a-zA-Z])\1\1/i', $password)) {
                $errors[] = "Password cannot contain 3 identical letters in a row.";
            }

            // --- IF ERRORS EXIST ---
            if (!empty($errors)) {
                $error = implode("<br>", $errors);
                $formView = "register.php";
                require __DIR__ . '/../views/auth/auth.php';
                return;
            }

            // --- SANITIZED DATA ---
            $data = [
                'first_name'  => trim($_POST['first_name'] ?? ''),
                'middle_name' => trim($_POST['middle_name'] ?? null),
                'last_name'   => trim($_POST['last_name'] ?? ''),
                'extension'   => trim($_POST['extension'] ?? null),
                'birthdate'   => $_POST['birthdate'] ?? '',
                'gender'      => $_POST['gender'] ?? '',
                'street'      => trim($_POST['street'] ?? ''),
                'barangay'    => trim($_POST['barangay'] ?? ''),
                'city'        => trim($_POST['city'] ?? ''),
                'province'    => trim($_POST['province'] ?? ''),
                'country'     => trim($_POST['country'] ?? ''),
                'zip'         => $_POST['zip'] ?? '',
                'security_q1' => trim($_POST['security_q1'] ?? ''),
                'security_q2' => trim($_POST['security_q2'] ?? ''),
                'security_q3' => trim($_POST['security_q3'] ?? ''),
                'username'    => $username,
                'password'    => password_hash($password, PASSWORD_DEFAULT) // hashed for security
            ];

            // --- INSERT INTO DATABASE ---
            if ($this->userModel->insertUser($data)) {
                header("Location: index.php?action=login&registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }

        // --- DEFAULT SHOW REGISTER FORM ---
        $formView = "register.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    // --- PRIVATE VALIDATION METHODS ---
    private function validateName($value, $field) {
        $errors = [];

        if ($value === '') return $errors; // skip optional empty

        // Only letters and spaces allowed
        if (!preg_match('/^[A-Za-z\s]+$/', $value)) {
            $errors[] = "$field: No special characters allowed.";
        }

        // Numbers cannot precede letters
        if (preg_match('/^\d+[A-Za-z]/', $value)) {
            $errors[] = "$field: Numbers cannot precede letters.";
        }

        // No double spaces
        if (preg_match('/\s{2,}/', $value)) {
            $errors[] = "$field: Double spaces not allowed.";
        }

        // No all caps
        if ($value === strtoupper($value) && strlen($value) > 1) {
            $errors[] = "$field: All capital letters not allowed.";
        }

        // No 3 identical letters in a row
        if (preg_match('/(.)\1\1/', strtolower($value))) {
            $errors[] = "$field: 3 identical letters in a row not allowed.";
        }

        // Must start with a capital letter
        if (isset($value[0]) && $value[0] !== strtoupper($value[0])) {
            $errors[] = "$field: Must start with a capital letter.";
        }

        return $errors;
    }
    public function loginUser() {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
                return;
            } else if (empty($username)){
                echo json_encode(['success' => false, 'message' => 'Username are required.']);
                return;
            }

            $user = $this->userModel->findByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id_number'];
                $_SESSION['username'] = $user['username'];
                echo json_encode(['success' => true, 'redirect' => 'index.php?action=dashboard']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }

   
}
