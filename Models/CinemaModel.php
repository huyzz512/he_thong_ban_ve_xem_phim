<?php

class CinemaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllCinemas() {
        $query = "SELECT * FROM cinemas ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCinemaById($id) {
        $query = "SELECT * FROM cinemas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCinema($name, $address, $hotline) {
        $query = "INSERT INTO cinemas (name, address, hotline) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $address, $hotline]);
    }

    public function updateCinema($id, $name, $address, $hotline) {
        $query = "UPDATE cinemas SET name = ?, address = ?, hotline = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $address, $hotline, $id]);
    }

    public function deleteCinema($id) {
        $query = "DELETE FROM cinemas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>
