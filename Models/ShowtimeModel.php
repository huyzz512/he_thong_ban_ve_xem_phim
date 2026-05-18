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
        $query = "SELECT COUNT(*) as conflict_count FROM showtimes WHERE room_id = ? AND (start_time < ?) AND (end_time > ?)";
        $stmt = $this->conn->prepare($query);
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

    /** Lấy tất cả suất chiếu của một phim, kèm info rạp và phòng, sắp xếp theo thời gian */
    public function getShowtimesByMovie($movie_id) {
        $query = "
            SELECT st.id, st.start_time, st.end_time, st.base_price, st.is_holiday, st.is_golden_hour,
                   st.room_id, r.name as room_name, r.total_rows, r.total_columns,
                   c.id as cinema_id, c.name as cinema_name, c.address as cinema_address
            FROM showtimes st
            JOIN rooms r ON r.id = st.room_id
            JOIN cinemas c ON c.id = r.cinema_id
            WHERE st.movie_id = ? AND st.start_time > NOW()
            ORDER BY c.name ASC, st.start_time ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$movie_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Lấy chi tiết một suất chiếu cụ thể */
    public function getShowtimeById($id) {
        $query = "
            SELECT st.*, m.id as movie_id, m.title as movie_title, m.banner_url, m.duration_minutes, m.genre,
                   r.name as room_name, r.total_rows, r.total_columns,
                   c.name as cinema_name, c.address as cinema_address, c.id as cinema_id
            FROM showtimes st
            JOIN movies m ON m.id = st.movie_id
            JOIN rooms r ON r.id = st.room_id
            JOIN cinemas c ON c.id = r.cinema_id
            WHERE st.id = ?
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
