<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $conn;
    private $table = "users";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function register($data) {
        $sql = "INSERT INTO {$this->table} 
                (id_number, first_name, middle_name, last_name, extension, birthdate, age, username, password_hash) 
                VALUES (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :age, :username, :password_hash)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id_number'     => $data['id_number'],
            ':first_name'    => $data['first_name'],
            ':middle_name'   => $data['middle_name'],
            ':last_name'     => $data['last_name'],
            ':extension'     => $data['extension'],
            ':birthdate'     => $data['birthdate'],
            ':age'           => $data['age'],
            ':username'      => $data['username'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
    }

    public function idExists($id_number) {
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id_number = :id_number";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':id_number' => $id_number]);
    return $stmt->fetchColumn() > 0;
    }

    public function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = :username";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

}
