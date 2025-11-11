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
        $page = 'register';            // ✅ define first
        $nextId = $this->userModel->generateIdNumber();
        $formView = "register.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    // Show Register Page
    // public function showRegister() {
    //     $nextId = $this->userModel->generateIdNumber();
    //     $formView = "register.php";
    //     $page = 'register';
    //     require __DIR__ . '/../views/auth/auth.php';
    // }

    // Show Forgot Password Page
    // public function showForgotPassword() {
    //     $formView = "forgot_password.php";
    //     $page = 'forgot';
    //     require __DIR__ . '/../views/auth/auth.php';
    // }



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
        // ✅ Show popup alert and redirect after user clicks OK
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Registration Successful</title>
        </head>
        <body>
            <script>
                alert("🎉 Registration successful! Please log in.");
                window.location.href = "index.php?action=login";
            </script>
        </body>
        </html>';
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
        $formView = "logout.php";
        require __DIR__ . '/../views/auth/auth.php';
    }


    // Show Forgot Password Page
    public function showForgotPassword() {
        $page = 'forgot-password'; 
        $formView = "forgot_password.php";
        require __DIR__ . '/../views/auth/auth.php';
    }

    
      /* ========================== ADD LOGIN CONTROLLER ======================== */
    public function loginUser() {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Both empty
            if (empty($username) && empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required.', 'errorType' => 'bothEmpty']);
                return;
            }

            // Username empty
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username is required.', 'errorType' => 'usernameEmpty']);
                return;
            }

            // Password empty
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required.', 'errorType' => 'passwordEmpty']);
                return;
            }

            // Check if username exists
            $user = $this->userModel->findByUsername($username);

            // Both wrong: username does not exist AND password entered
            if (!$user && !empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Username and password .', 'errorType' => 'bothWrong']);
                return;
            }

            // Username wrong
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Username not found.', 'errorType' => 'usernameWrong']);
                return;
            }

            // Password wrong
            if (!password_verify($password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid password.', 'errorType' => 'passwordWrong']);
                return;
            }

            // Success
            $_SESSION['user_id'] = $user['id_number'];
            $_SESSION['username'] = $user['username'];

            echo json_encode([
                'success' => true,
                'redirect' => 'index.php?action=dashboard'
            ]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid request method.', 'errorType' => 'invalidMethod']);
    }





    
    //========================================== Verify ID =====================================
    public function verifyId() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_number = trim($_POST['id_number'] ?? '');
            $user = $this->userModel->findById($id_number);

            if($user){
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID Number']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        exit; // Important to stop any extra output
    }


    //========================================== Verify Security Answers =====================================
public function verifySecurityAnswers() {
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_number = trim($_POST['id_number'] ?? '');
        
        // Validate ID number is provided
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $answers = [
            1 => trim($_POST['security_answer_1'] ?? ''),
            2 => trim($_POST['security_answer_2'] ?? ''),
            3 => trim($_POST['security_answer_3'] ?? '')
        ];

        // Validate all answers are provided
        $emptyAnswers = [];
        foreach ($answers as $qid => $ans) {
            if (empty($ans)) {
                $emptyAnswers[] = $qid;
            }
        }

        if (!empty($emptyAnswers)) {
            echo json_encode(['success' => false, 'message' => 'Please answer all security questions.']);
            exit;
        }

        $correctCount = 0;

        foreach ($answers as $qid => $ans) {
            $record = $this->userModel->getUserAuthAnswer($id_number, $qid);
            if ($record && password_verify($ans, $record['answer_hash'])) {
                $correctCount++;
            }
        }

        // User must answer at least 2 out of 3 questions correctly
        if ($correctCount >= 2) {
            echo json_encode(['success' => true, 'message' => 'Verification successful! You answered at least 2 questions correctly.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Verification failed. You must answer at least 2 out of 3 questions correctly.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
    exit;
}



    //=========================================== Reset Password =======================================
    public function resetPassword() {
        $id_number = $_POST['id_number'] ?? '';
        $question_id = $_POST['security_question'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if($new_password !== $confirm_password){
            echo json_encode(['susccess'=>false, 'message'=>'Passwords do not match']);
            return;
        }

        $record = $this->userModel->getUserAuthAnswer($id_number, $question_id);

        if(!$record || !password_verify($answer, $record['answer_hash'])){
            echo json_encode(['success'=>false, 'message'=>'Incorrect security answer']);
            return;
        }

        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        if($this->userModel->updatePassword($id_number, $hashed)){
            echo json_encode(['success'=>true, 'message'=>'Password reset successfully']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Failed to reset password']);
        }
    }


}