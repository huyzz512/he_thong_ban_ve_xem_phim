<?php

class CinemaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllCinemas() {
        $stmt = $this->conn->prepare("SELECT * FROM cinemas ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCinemaById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM cinemas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCinema($name, $address, $hotline) {
        $stmt = $this->conn->prepare("INSERT INTO cinemas (name, address, hotline) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $address, $hotline]);
    }

    public function updateCinema($id, $name, $address, $hotline) {
        $stmt = $this->conn->prepare("UPDATE cinemas SET name=?, address=?, hotline=? WHERE id=?");
        return $stmt->execute([$name, $address, $hotline, $id]);
    }

    public function deleteCinema($id) {
        $stmt = $this->conn->prepare("DELETE FROM cinemas WHERE id=?");
        return $stmt->execute([$id]);
    }

    /** Cập nhật thông tin ngân hàng của rạp */
    public function updateBankInfo($id, $bank_id, $account_no, $account_name) {
        $stmt = $this->conn->prepare(
            "UPDATE cinemas SET bank_id=?, bank_account_no=?, bank_account_name=? WHERE id=?"
        );
        return $stmt->execute([$bank_id, $account_no, $account_name, $id]);
    }

    /** Lấy thông tin ngân hàng theo cinema_id (dùng cho trang thanh toán) */
    public function getBankByCinema($cinema_id) {
        $stmt = $this->conn->prepare(
            "SELECT bank_id, bank_account_no, bank_account_name FROM cinemas WHERE id=?"
        );
        $stmt->execute([$cinema_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
