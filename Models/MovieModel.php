<?php

class MovieModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllMovies() {
        $query = "SELECT * FROM movies ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getMovieById($id) {
        $query = "SELECT * FROM movies WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Lấy danh sách phim theo trạng thái: showing / upcoming / stopped */
    public function getMoviesByStatus($status) {
        $query = "SELECT * FROM movies WHERE status = ? ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    /** Lấy tất cả thể loại phim duy nhất */
    public function getAllGenres() {
        $query = "SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Tìm kiếm và lọc phim
     * @param string $search     Từ khóa tìm tên phim
     * @param string $status     'showing' | 'upcoming' | 'stopped' | '' (tất cả)
     * @param string $genre      Thể loại phim
     * @param int    $cinema_id  ID rạp chiếu
     * @param string $date       Ngày chiếu (YYYY-MM-DD)
     */
    public function searchMovies($search = '', $status = '', $genre = '', $cinema_id = 0, $date = '') {
        $params = [];
        $joins  = '';
        $where  = [];

        // Lọc theo rạp / ngày -> cần join với showtimes
        if ($cinema_id > 0 || $date !== '') {
            $joins .= " INNER JOIN showtimes st ON st.movie_id = m.id
                        INNER JOIN rooms r ON r.id = st.room_id";
            if ($cinema_id > 0) {
                $where[] = "r.cinema_id = ?";
                $params[] = $cinema_id;
            }
            if ($date !== '') {
                $where[] = "DATE(st.start_time) = ?";
                $params[] = $date;
            }
        }

        if ($search !== '') {
            $where[] = "m.title LIKE ?";
            $params[] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[] = "m.status = ?";
            $params[] = $status;
        }
        if ($genre !== '') {
            $where[] = "m.genre = ?";
            $params[] = $genre;
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
        $distinct    = ($cinema_id > 0 || $date !== '') ? 'DISTINCT' : '';

        $query = "SELECT $distinct m.* FROM movies m $joins $whereClause ORDER BY m.id DESC";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createMovie($title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status) {
        $query = "INSERT INTO movies (title, description, genre, duration_minutes, banner_url, trailer_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status]);
    }

    public function updateMovie($id, $title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status) {
        $query = "UPDATE movies SET title = ?, description = ?, genre = ?, duration_minutes = ?, banner_url = ?, trailer_url = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status, $id]);
    }

    public function deleteMovie($id) {
        $query = "DELETE FROM movies WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>
