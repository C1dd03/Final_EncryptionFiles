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

    // ✅ Handle Registration (POST)
    public function registerUser() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            // --- NAME VALIDATION ---
            $fields = [
                'First Name'  => $_POST['first_name'] ?? '',
                'Middle Name' => $_POST['middle_name'] ?? '',
                'Last Name'   => $_POST['last_name'] ?? ''
            ];

            foreach ($fields as $label => $value) {
                $errors = array_merge($errors, $this->validateName(trim($value), $label));
            }

            // --- EXTENSION VALIDATION (separate from name validation) ---
            $extension = trim($_POST['extension'] ?? '');
            if ($extension !== '') {
                $normalizedExtension = strtoupper(trim($extension));
                if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                    $cleanExtension = 'JR';
                } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                    $cleanExtension = 'SR';
                } else {
                    $cleanExtension = strtoupper(str_replace('.', '', $extension));
                }

                $validExtensions = ['JR', 'SR', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                if (!in_array($cleanExtension, $validExtensions, true)) {
                    $errors[] = "Extension: Must be Jr., Sr., or a Roman numeral between I and X.";
                }
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

            // --- SECURITY QUESTION SELECTION VALIDATION ---
            $questionSelections = [
                'Question 1' => $_POST['security_question_1'] ?? '',
                'Question 2' => $_POST['security_question_2'] ?? '',
                'Question 3' => $_POST['security_question_3'] ?? ''
            ];

            foreach ($questionSelections as $label => $value) {
                if ($value === '' || !ctype_digit((string)$value)) {
                    $errors[] = "$label: Please select a security question.";
                }
            }

            // --- USERNAME & PASSWORD VALIDATION ---
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $errors[] = "Username cannot contain 3 identical letters  q a row.";
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

            // --- HANDLE EXTENSION ---
            $extension = trim($_POST['extension'] ?? '');
            if ($extension === '') {
                $extension = null;
            } else {
                $normalizedExtension = strtoupper(trim($extension));
                if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                    $extension = 'Jr.';
                } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                    $extension = 'Sr.';
                } else {
                    $extension = strtoupper(str_replace('.', '', $extension));
                }
            }

            // --- SANITIZED DATA ---
            $securityAnswers = [
                [
                    'question_id' => (int)($questionSelections['Question 1'] ?? 0),
                    'answer'      => trim($_POST['security_q1'] ?? '')
                ],
                [
                    'question_id' => (int)($questionSelections['Question 2'] ?? 0),
                    'answer'      => trim($_POST['security_q2'] ?? '')
                ],
                [
                    'question_id' => (int)($questionSelections['Question 3'] ?? 0),
                    'answer'      => trim($_POST['security_q3'] ?? '')
                ],
            ];

            foreach ($securityAnswers as $index => $entry) {
                if ($entry['answer'] === '') {
                    $errors[] = 'Answer ' . ($index + 1) . ': Please provide an answer.';
                }
            }

            if (!empty($errors)) {
                $error = implode("<br>", $errors);
                $formView = "register.php";
                require __DIR__ . '/../views/auth/auth.php';
                return;
            }

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
                'security_answers' => $securityAnswers,
                'username'    => $username,
                'email'       => trim($_POST['email'] ?? ''),
                'password'    => password_hash($password, PASSWORD_DEFAULT) // hashed for security
            ];

            // --- INSERT INTO DATABASE ---
            $result = $this->userModel->insertUser($data);
            if ($result) {
                // Set success flag and ID for modal display
                $registrationSuccess = true;
                $registeredId = $result; // This is the generated ID number
                $formView = "register.php";
                require __DIR__ . '/../views/auth/auth.php';
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

            

            /*++++++++++++++++++++ ADD INVALID USERNAME AND PASSWORD =============================================*/

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
                // Get user's security questions
                $questions = $this->userModel->getUserAuthAnswers($id_number);
                
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id_number' => $user['id_number'],
                        'username' => $user['username']
                    ],
                    'questions' => $questions
                ]);
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

    //========================================== Check Username Availability =====================================
    public function checkUsername() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');

            if (empty($username)) {
                echo json_encode(['available' => false, 'message' => 'Username is required.']);
                exit;
            }

            if ($this->userModel->usernameExists($username)) {
                echo json_encode(['available' => false, 'message' => 'Username is already taken.']);
            } else {
                echo json_encode(['available' => true, 'message' => 'Username is available.']);
            }
        } else {
            echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }

    //========================================== Check Email Availability =====================================
    public function checkEmail() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                echo json_encode(['available' => false, 'message' => 'Email is required.']);
                exit;
            }

            // Basic email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['available' => false, 'message' => 'Invalid email format.']);
                exit;
            }

            if ($this->userModel->emailExists($email)) {
                echo json_encode(['available' => false, 'message' => 'Email is already registered.']);
            } else {
                echo json_encode(['available' => true, 'message' => 'Email is available.']);
            }
        } else {
            echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }


}
