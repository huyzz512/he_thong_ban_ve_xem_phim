<?php

class ShowtimeModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getMovieDuration($movie_id) {
        $query = "SELECT duration_minutes FROM movies WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$movie_id]);
        $result = $stmt->fetch();
        return $result ? $result['duration_minutes'] : 0;
    }

    public function checkOverlap($room_id, $start_time, $end_time) {
        // Strict query to find any overlapping showtimes in the same room
        $query = "SELECT COUNT(*) as conflict_count FROM showtimes WHERE room_id = ? AND (start_time < ?) AND (end_time > ?)";
        $stmt = $this->conn->prepare($query);
        // Bind: room_id, new_end_time, new_start_time
        $stmt->execute([$room_id, $end_time, $start_time]); 
        $row = $stmt->fetch();
        return $row['conflict_count'] > 0;
    }

    public function createShowtime($movie_id, $room_id, $start_time, $end_time, $base_price, $is_holiday, $is_golden_hour) {
        $query = "INSERT INTO showtimes (movie_id, room_id, start_time, end_time, base_price, is_holiday, is_golden_hour) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$movie_id, $room_id, $start_time, $end_time, $base_price, $is_holiday, $is_golden_hour]);
    }
    public function getAllShowtimes() {
        $query = "
            SELECT s.id, s.start_time, s.end_time, s.base_price, s.is_holiday, s.is_golden_hour,
                   m.title as movie_title, r.name as room_name, c.name as cinema_name
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            JOIN cinemas c ON r.cinema_id = c.id
            ORDER BY s.start_time DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteShowtime($id) {
        $query = "DELETE FROM showtimes WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>
