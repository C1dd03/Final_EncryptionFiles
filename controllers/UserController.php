<?php

require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // Show Login Page
    public function showLogin() {
        $formView = "login.php";
        $page = 'login';
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Show Register Page
    public function showRegister() {
        $nextId = $this->userModel->generateIdNumber();
        $formView = "register.php";
        $page = 'register';
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Show Forgot Password Page
    public function showForgotPassword() {
        $formView = "forgot_password.php";
        $page = 'forgot';
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Verify security answer
    public function verifySecurityAnswer() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }
        $idNumber = trim($_POST['id_number'] ?? '');
        $questionId = intval($_POST['question_id'] ?? 0);
        $answer = trim($_POST['answer'] ?? '');

        if ($idNumber === '' || $questionId < 1 || $questionId > 3 || $answer === '') {
            echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
            return;
        }

        $user = $this->userModel->findByIdNumber($idNumber);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            return;
        }

        $hash = $this->userModel->getAuthAnswerHash($idNumber, $questionId);
        if (!$hash) {
            echo json_encode(['success' => false, 'message' => 'Security answer not set.']);
            return;
        }

        $ok = password_verify($answer, $hash);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Answer verified.' : 'Incorrect answer.']);
    }

    // Update password after successful verification
    public function updatePassword() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }
        $idNumber = trim($_POST['id_number'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if ($idNumber === '' || $newPassword === '') {
            echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
            return;
        }

        // Validate password strength
        $pwdRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/';
        if (!preg_match($pwdRegex, $newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Password does not meet strength requirements.']);
            return;
        }

        $ok = $this->userModel->updatePasswordById($idNumber, $newPassword);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Password updated.' : 'Failed to update password.']);
    }

// ✅ Handle Registration (POST)
public function registerUser() {
    session_start(); // start session for flash message

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Default show register form
        $formView = "register.php";
        require __DIR__ . '/../views/auth/auth.php';
        return;
    }

    $errors = [];

    // --- NAME VALIDATION ---
    $extension = trim($_POST['extension'] ?? '');
    $otherExtension = trim($_POST['other_extension'] ?? '');
    if ($extension === 'Other' && !empty($otherExtension)) {
        $extension = $otherExtension;
    }

    $fields = [
        'First Name'  => trim($_POST['first_name'] ?? ''),
        'Middle Name' => trim($_POST['middle_name'] ?? ''),
        'Last Name'   => trim($_POST['last_name'] ?? ''),
        'Extension'   => $extension
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

    if ($this->userModel->usernameExists($username)) {
        $errors[] = "Username already taken. Please choose another.";
    }

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
        'extension'   => $extension,
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
    $insertResult = $this->userModel->insertUser($data);

    if ($insertResult) {
        // ✅ Render the register page layout, show alert, then redirect to login
        // This prevents a blank white page and keeps styling consistent
        $successAlert = "Registration successful! Please log in.";
        $formView = "register.php";
        $page = 'register';
        require __DIR__ . '/../views/auth/auth.php';
        exit;
    } else {
        $error = "Registration failed. Please try again.";
        $formView = "register.php";
        require __DIR__ . '/../views/auth/auth.php';
    }
}

    // ✅ AJAX Username Check
    public function checkUsername() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
            $username = trim($_POST['username']);
            $exists = $this->userModel->usernameExists($username);
            echo $exists ? "taken" : "available";
        }
        exit;
    }

    // ✅ AJAX ID Check
    public function checkId() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_number'])) {
            $id = trim($_POST['id_number']);
            $exists = $this->userModel->idExists($id);
            echo $exists ? "exists" : "missing";
        }
        exit;
    }

    // --- PRIVATE VALIDATION METHODS ---
private function validateName($value, $field) {
    $errors = [];

    if ($value === '') return $errors; // skip optional empty

    // ✅ Allow Roman numeral (I - X) or up to 3 uppercase letters for Extension
    if ($field === 'Extension') {
        if (!preg_match('/^(I|II|III|IV|V|VI|VII|VIII|IX|X|[A-Z]{1,3})$/', strtoupper($value))) {
            $errors[] = "$field: Must be a valid Roman numeral (I–X) or up to 3 uppercase letters.";
        }
        return $errors;
    }

    // --- Regular validation for other name fields ---

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


   /* ========================== ADD LOGOUT ======================== */
    public function logout() {
        // Proper logout: destroy session and redirect to login
        session_start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }



    
    /* ========================== ADD LOGIN CONTROLLER ======================== */
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
