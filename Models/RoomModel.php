<?php

class RoomModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createRoom($cinema_id, $name, $total_rows, $total_columns) {
        $query = "INSERT INTO rooms (cinema_id, name, total_rows, total_columns) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cinema_id, $name, $total_rows, $total_columns]);
        return $this->conn->lastInsertId();
    }

    public function createSeat($room_id, $row_name, $col_number, $type) {
        $query = "INSERT INTO seats (room_id, row_name, col_number, type) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$room_id, $row_name, $col_number, $type]);
    }
    public function getRoomsByCinema($cinema_id) {
        $query = "SELECT * FROM rooms WHERE cinema_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cinema_id]);
        return $stmt->fetchAll();
    }

    public function getAllRooms() {
        $query = "
            SELECT r.id, r.name as room_name, c.name as cinema_name 
            FROM rooms r 
            JOIN cinemas c ON r.cinema_id = c.id 
            ORDER BY c.name, r.name
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteRoom($id) {
        $query = "DELETE FROM rooms WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>
