<?php

class CinemaController {
    private $cinemaModel;

    public function __construct($db) {
        $this->cinemaModel = new CinemaModel($db);
    }

    public function getAllCinemas() {
        return $this->cinemaModel->getAllCinemas();
    }

    public function getCinema($id) {
        return $this->cinemaModel->getCinemaById($id);
    }

    public function addCinema($name, $address, $hotline) {
        if(empty($name) || empty($address) || empty($hotline)) {
            return ["status" => "error", "message" => "Vui lòng nhập đầy đủ thông tin."];
        }
        $success = $this->cinemaModel->createCinema($name, $address, $hotline);
        if ($success) {
            return ["status" => "success", "message" => "Thêm rạp thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể thêm rạp."];
    }

    public function updateCinema($id, $name, $address, $hotline) {
        if(empty($name) || empty($address) || empty($hotline)) {
            return ["status" => "error", "message" => "Vui lòng nhập đầy đủ thông tin."];
        }
        $success = $this->cinemaModel->updateCinema($id, $name, $address, $hotline);
        if ($success) {
            return ["status" => "success", "message" => "Cập nhật rạp thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể cập nhật rạp."];
    }

    public function deleteCinema($id) {
        $success = $this->cinemaModel->deleteCinema($id);
        if ($success) {
            return ["status" => "success", "message" => "Xóa rạp thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể xóa rạp."];
    }
}
?>

