<?php

class ShowtimeController {
    private $showtimeModel;

    public function __construct($db) {
        $this->showtimeModel = new ShowtimeModel($db);
    }

    public function addShowtime($movie_id, $room_id, $start_time_str, $base_price, $is_holiday = false, $is_golden_hour = false) {
        // 1. Get movie duration
        $duration = $this->showtimeModel->getMovieDuration($movie_id);
        if ($duration == 0) {
            throw new Exception("Không tìm thấy phim hoặc thời lượng không hợp lệ.");
        }

        // 2. Calculate end_time = start_time + duration + 15 mins (cleaning time buffer)
        $start_time = new DateTime($start_time_str);
        $end_time = clone $start_time;
        
        $total_minutes = $duration + 15;
        $end_time->modify("+$total_minutes minutes");

        $start_formatted = $start_time->format('Y-m-d H:i:s');
        $end_formatted = $end_time->format('Y-m-d H:i:s');

        // 3. Overlap Conflict Algorithm Check
        $hasOverlap = $this->showtimeModel->checkOverlap($room_id, $start_formatted, $end_formatted);
        
        if ($hasOverlap) {
            throw new Exception("Lỗi xếp lịch: Phát hiện trùng lặp lịch chiếu trong phòng này vào khoảng thời gian đã chọn.");
        }

        // 4. Save to Database
        $success = $this->showtimeModel->createShowtime(
            $movie_id, $room_id, $start_formatted, $end_formatted, $base_price, $is_holiday, $is_golden_hour
        );

        if ($success) {
            return ["status" => "success", "message" => "Lên lịch chiếu thành công."];
        } else {
            throw new Exception("Lỗi khi lưu lịch chiếu vào CSDL.");
        }
    }

    /**
     * Helper Function: Pricing Matrix Calculation
     * Calculates the final ticket price dynamically.
     */
    public function calculateTicketPrice($base_price, $seat_type, $is_holiday, $is_golden_hour) {
        $final_price = $base_price;

        // Seat Type Modifier
        if ($seat_type === 'vip') {
            $final_price += ($base_price * 0.20); // VIP = +20%
        }

        // Holiday Modifier
        if ($is_holiday) {
            $final_price += ($base_price * 0.15); // Holiday = +15%
        }

        // Golden Hour Modifier
        if ($is_golden_hour) {
            $final_price += ($base_price * 0.10); // Golden Hour = +10%
        }

        return round($final_price, 2);
    }
    public function getAllShowtimes() {
        return $this->showtimeModel->getAllShowtimes();
    }

    public function deleteShowtime($id) {
        $success = $this->showtimeModel->deleteShowtime($id);
        if ($success) {
            return ["status" => "success", "message" => "Xóa lịch chiếu thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể xóa lịch chiếu."];
    }
}
?>

