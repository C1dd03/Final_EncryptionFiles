<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // ✅ Moved helper as private method
    private function validateName($name, $fieldName, &$errors) {
        if (!preg_match("/^[A-Z][a-z]*( [A-Z][a-z]*)*$/", $name)) {
            $errors[] = "$fieldName must start with uppercase and follow proper format.";
        }
        if (preg_match("/[^a-zA-Z ]/", $name)) {
            $errors[] = "$fieldName must not contain special characters.";
        }
        if (preg_match("/\s{2,}/", $name)) {
            $errors[] = "$fieldName must not contain double spaces.";
        }
        if (preg_match("/(.)\\1{2,}/i", $name)) {
            $errors[] = "$fieldName must not contain 3 consecutive repeating letters.";
        }
    }

    public function registerUser() {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 1. Primary Key Validation
            if ($this->userModel->idExists($_POST['id_number'] ?? '')) {
                $errors[] = "ID Number already exists.";
            }

            // 2. Name Validation
            $this->validateName($_POST['first_name'] ?? '', "First Name", $errors);
            if (!empty($_POST['middle_name'])) {
                $this->validateName($_POST['middle_name'], "Middle Name", $errors);
            }
            $this->validateName($_POST['last_name'] ?? '', "Last Name", $errors);

            // 3. Extension Validation (Optional)
            if (!empty($_POST['extension']) && preg_match("/[^a-zA-Z0-9.]/", $_POST['extension'])) {
                $errors[] = "Extension contains invalid characters.";
            }

            // 4. Birthdate & Age Validation
            if (!empty($_POST['birthdate'])) {
                $birthdate = new DateTime($_POST['birthdate']);
                $today = new DateTime();
                $age = $today->diff($birthdate)->y;
                if ($age < 18) {
                    $errors[] = "You must be at least 18 years old.";
                }
            } else {
                $errors[] = "Birthdate is required.";
            }

            // 5. Username Validation
            if ($this->userModel->usernameExists($_POST['username'] ?? '')) {
                $errors[] = "Username already exists.";
            }

            // 6. Password Validation
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($password !== $confirm) {
                $errors[] = "Passwords do not match.";
            }

            $passwordStrength = 0;
            if (preg_match("/[a-z]/", $password)) $passwordStrength++;
            if (preg_match("/[A-Z]/", $password)) $passwordStrength++;
            if (preg_match("/[0-9]/", $password)) $passwordStrength++;
            if (preg_match("/[^a-zA-Z0-9]/", $password)) $passwordStrength++;
            if ($passwordStrength < 3) {
                $errors[] = "Password is too weak.";
            }

            // If errors → reload form with errors
            if (!empty($errors)) {
                require __DIR__ . '/../views/register.php';
                return;
            }

            // If valid → save
            $data = [
                'id_number'   => $_POST['id_number'],
                'first_name'  => $_POST['first_name'],
                'middle_name' => $_POST['middle_name'] ?? null,
                'last_name'   => $_POST['last_name'],
                'extension'   => $_POST['extension'] ?? null,
                'birthdate'   => $_POST['birthdate'],
                'age'         => $age ?? null,
                'username'    => $_POST['username'],
                'password'    => $password
            ];

            if ($this->userModel->register($data)) {
                echo "<p style='color:green;'>User registered successfully!</p>";
            } else {
                echo "<p style='color:red;'>Error: Could not register user.</p>";
            }

        } else {
            require __DIR__ . '/../views/register.php';
        }
    }

    
}
