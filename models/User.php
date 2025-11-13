<?php
require_once __DIR__ . '/../config/db.php';


class User {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function insertUser($data) {
        try {
            $this->conn->beginTransaction();

            // ✅ Generate new ID and calculate age
            $age = $this->calculateAge($data['birthdate']);
            $id_number = $this->generateIdNumber();

            // ✅ 1. Insert into users
            $sqlUser = "INSERT INTO users 
                        (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash) 
                        VALUES 
                        (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash)";
            $stmt = $this->conn->prepare($sqlUser);
            $stmt->execute([
                ':id_number'     => $id_number,
                ':first_name'    => $data['first_name'],
                ':middle_name'   => $data['middle_name'] ?? null,
                ':last_name'     => $data['last_name'],
                ':extension'     => $data['extension'] ?? null,
                ':birthdate'     => $data['birthdate'],
                ':gender'        => $data['gender'],
                ':age'           => $age,
                ':username'      => $data['username'],
                ':email'         => $data['email'] ?? null,
                ':password_hash' => $data['password']
            ]);

            // ✅ 2. Insert into addresses
            $sqlAddress = "INSERT INTO addresses 
                            (id_number, purok_street, barangay, city_municipality, province, country, zip_code) 
                            VALUES 
                            (:id_number, :street, :barangay, :city, :province, :country, :zip)";
            $stmt = $this->conn->prepare($sqlAddress);
            $stmt->execute([
                ':id_number' => $id_number, // ✅ fixed to use $id_number
                ':street'    => $data['street'],
                ':barangay'  => $data['barangay'],
                ':city'      => $data['city'],
                ':province'  => $data['province'],
                ':country'   => $data['country'],
                ':zip'       => $data['zip']
            ]);

            // ✅ 3. Insert into user_auth_answers
            $sqlAuth = "INSERT INTO user_auth_answers (id_number, question_id, answer_hash) 
                        VALUES (:id_number, :question_id, :answer_hash)";
            $stmt = $this->conn->prepare($sqlAuth);

            foreach ($data['security_answers'] as $entry) {
                $questionId = (int)($entry['question_id'] ?? 0);
                $answer = $entry['answer'] ?? '';

                if ($questionId <= 0 || $answer === '') {
                    throw new InvalidArgumentException('Invalid security question selection or empty answer.');
                }

                $stmt->execute([
                    ':id_number'   => $id_number, // ✅ fixed to use same generated ID
                    ':question_id' => $questionId,
                    ':answer_hash' => password_hash($answer, PASSWORD_BCRYPT)
                ]);
            }

            $this->conn->commit();
            return $id_number; // Return the generated ID number
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Registration failed: " . $e->getMessage());
            return false;
        }
    }


    private function calculateAge($birthdate) {
        $dob = new DateTime($birthdate);
        $today = new DateTime();
        return $today->diff($dob)->y;
    }

    public function generateIdNumber() {
        $year = date("Y");

        // ✅ Get the last inserted ID for the current year only
        $stmt = $this->conn->prepare("
            SELECT id_number 
            FROM users 
            WHERE id_number LIKE :yearPrefix 
            ORDER BY id_number DESC 
            LIMIT 1
        ");
        $stmt->execute([':yearPrefix' => $year . '-%']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && preg_match('/^' . $year . '-(\d{4})$/', $row['id_number'], $matches)) {
            // ✅ Increment the last 4 digits
            $lastNum = (int)$matches[1];
            $nextNum = str_pad($lastNum + 1, 4,'0', STR_PAD_LEFT);
        } else {
            // ✅ Start fresh if no ID exists for this year
            $nextNum = '0001';
        }

        return $year . '-' . $nextNum;
    }

    




    


    /* ========================== ADD LOGIN MODEL ======================== */
    public function findByUsername($username) {
    
        $sql = "SELECT * FROM users WHERE username = :username";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->execute([':username' => $username]);
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    
    }
    

 /* ========================== ADD FORGOT PASSWORD MODEL ======================== */
    public function findById($id_number){
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id_number = :id_number");
        $stmt->execute([':id_number'=>$id_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
        public function getUserAuthAnswers($id_number) {
        $stmt = $this->conn->prepare("
            SELECT ua.question_id, ua.answer_hash, aq.question_text 
            FROM user_auth_answers ua
            JOIN auth_questions aq ON ua.question_id = aq.question_id
            WHERE ua.id_number = :id_number 
            ORDER BY ua.question_id ASC
        ");
        $stmt->execute([':id_number' => $id_number]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserAuthAnswer($id_number, $question_id){
        $stmt = $this->conn->prepare("SELECT * FROM user_auth_answers WHERE id_number = :id_number AND question_id = :question_id");
        $stmt->execute([':id_number'=>$id_number, ':question_id'=>$question_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($id_number, $password_hash){
        $stmt = $this->conn->prepare("UPDATE users SET password_hash=:password WHERE id_number=:id_number");
        return $stmt->execute([':password'=>$password_hash, ':id_number'=>$id_number]);
    }

    /* ========================== CHECK USERNAME AND EMAIL AVAILABILITY ======================== */
    public function usernameExists($username) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    public function emailExists($email) {
        // Check if email column exists in users table
        // If email column doesn't exist, return false (email is available)
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // If email column doesn't exist, return false
            return false;
        }
    }

    
}
