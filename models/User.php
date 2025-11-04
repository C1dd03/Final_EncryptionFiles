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

            // ✅ 1. Insert into users
            $age = $this->calculateAge($data['birthdate']);
            // Always generate the latest ID directly in the model
            $id_number = $this->generateIdNumber();

            $sqlUser = "INSERT INTO users 
                        (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, password_hash) 
                        VALUES 
                        (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :password_hash)";
            $stmt = $this->conn->prepare($sqlUser);
            $stmt->execute([
                ':id_number'     => $id_number,  // ✅ use the fresh ID here
                ':first_name'    => $data['first_name'],
                ':middle_name'   => $data['middle_name'] ?? null,
                ':last_name'     => $data['last_name'],
                ':extension'     => $data['extension'] ?? null,
                ':birthdate'     => $data['birthdate'],
                ':gender'        => $data['gender'],
                ':age'           => $age,
                ':username'      => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)
            ]);


            // ✅ 2. Insert into addresses
            $sqlAddress = "INSERT INTO addresses 
                          (id_number, purok_street, barangay, city_municipality, province, country, zip_code) 
                          VALUES 
                          (:id_number, :street, :barangay, :city, :province, :country, :zip)";
            $stmt = $this->conn->prepare($sqlAddress);
            $stmt->execute([
                ':id_number' => $data['id_number'],
                ':street'    => $data['street'],
                ':barangay'  => $data['barangay'],
                ':city'      => $data['city'],
                ':province'  => $data['province'],
                ':country'   => $data['country'],
                ':zip'       => $data['zip']
            ]);

            // ✅ 3. Insert into user_auth_answers
            $questions = [
                1 => $data['security_q1'],
                2 => $data['security_q2'],
                3 => $data['security_q3']
            ];

            $sqlAuth = "INSERT INTO user_auth_answers (id_number, question_id, answer_hash) 
                        VALUES (:id_number, :question_id, :answer_hash)";
            $stmt = $this->conn->prepare($sqlAuth);

            foreach ($questions as $qid => $answer) {
                $stmt->execute([
                    ':id_number'   => $data['id_number'],
                    ':question_id' => $qid,
                    ':answer_hash' => password_hash($answer, PASSWORD_BCRYPT)
                ]);
            }

            $this->conn->commit();
            return true;
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


}
