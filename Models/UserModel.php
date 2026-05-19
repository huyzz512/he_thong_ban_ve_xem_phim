<?php
class UserModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createUser($fullName, $email, $password, $role = 'customer') {
        if ($this->findByEmail($email)) return false;
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$fullName, $email, $hashed, $role]);
    }

    public function updateProfile($id, $full_name, $phone) {
        $stmt = $this->conn->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
        return $stmt->execute([$full_name, $phone, $id]);
    }

    public function updatePassword($id, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password=? WHERE id=?");
        return $stmt->execute([$hashed, $id]);
    }
}
?>
