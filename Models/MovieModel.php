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

    public function createMovie($title, $description, $duration_minutes, $banner_url, $trailer_url, $status) {
        $query = "INSERT INTO movies (title, description, duration_minutes, banner_url, trailer_url, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $description, $duration_minutes, $banner_url, $trailer_url, $status]);
    }

    public function updateMovie($id, $title, $description, $duration_minutes, $banner_url, $trailer_url, $status) {
        $query = "UPDATE movies SET title = ?, description = ?, duration_minutes = ?, banner_url = ?, trailer_url = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $description, $duration_minutes, $banner_url, $trailer_url, $status, $id]);
    }

    public function deleteMovie($id) {
        $query = "DELETE FROM movies WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>
