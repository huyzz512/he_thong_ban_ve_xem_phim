<?php

class MovieController {
    private $movieModel;

    public function __construct($db) {
        $this->movieModel = new MovieModel($db);
    }

    public function getAllMovies() {
        return $this->movieModel->getAllMovies();
    }

    public function getMovie($id) {
        return $this->movieModel->getMovieById($id);
    }

    public function addMovie($title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status) {
        if(empty($title) || empty($duration_minutes)) {
            return ["status" => "error", "message" => "Vui lòng nhập tiêu đề và thời lượng."];
        }
        $success = $this->movieModel->createMovie($title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status);
        if ($success) {
            return ["status" => "success", "message" => "Thêm phim thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể thêm phim."];
    }

    public function updateMovie($id, $title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status) {
        if(empty($title) || empty($duration_minutes)) {
            return ["status" => "error", "message" => "Vui lòng nhập tiêu đề và thời lượng."];
        }
        $success = $this->movieModel->updateMovie($id, $title, $description, $genre, $duration_minutes, $banner_url, $trailer_url, $status);
        if ($success) {
            return ["status" => "success", "message" => "Cập nhật phim thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể cập nhật phim."];
    }

    public function deleteMovie($id) {
        $success = $this->movieModel->deleteMovie($id);
        if ($success) {
            return ["status" => "success", "message" => "Xóa phim thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể xóa phim."];
    }
}
?>
