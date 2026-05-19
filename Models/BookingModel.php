<?php

class BookingModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tính giá vé dựa trên base_price, loại ghế và phụ phí
     */
    public function calcSeatPrice($base_price, $seat_type, $is_holiday, $is_golden_hour) {
        $price = (float)$base_price;
        if ($seat_type === 'vip')    $price *= 1.30;  // VIP +30%
        if ($is_holiday)             $price *= 1.15;  // Lễ +15%
        if ($is_golden_hour)         $price *= 1.10;  // Giờ vàng +10%
        return round($price, 0);
    }

    /**
     * Tạo booking: insert vào bookings + booking_details
     * Trả về booking_id nếu thành công, false nếu thất bại
     */
    public function createBooking($user_id, $showtime_id, array $seat_ids, array $seat_prices) {
        try {
            $this->conn->beginTransaction();

            $total = array_sum($seat_prices);

            // Insert booking
            $stmt = $this->conn->prepare(
                "INSERT INTO bookings (user_id, showtime_id, total_amount, status) VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$user_id, $showtime_id, $total]);
            $booking_id = $this->conn->lastInsertId();

            // Insert booking_details for each seat
            $stmtDetail = $this->conn->prepare(
                "INSERT INTO booking_details (booking_id, seat_id, price_at_booking) VALUES (?, ?, ?)"
            );
            foreach ($seat_ids as $i => $seat_id) {
                $stmtDetail->execute([$booking_id, $seat_id, $seat_prices[$i]]);
            }

            $this->conn->commit();
            return $booking_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /** Lấy booking theo ID kèm đầy đủ thông tin */
    public function getBookingById($booking_id) {
        $stmt = $this->conn->prepare("
            SELECT b.*, u.full_name as user_name, u.email,
                   m.title as movie_title, m.banner_url,
                   st.start_time, st.end_time, st.is_holiday, st.is_golden_hour,
                   r.name as room_name, c.name as cinema_name, c.address as cinema_address
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN showtimes st ON st.id = b.showtime_id
            JOIN movies m ON m.id = st.movie_id
            JOIN rooms r ON r.id = st.room_id
            JOIN cinemas c ON c.id = r.cinema_id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Lấy chi tiết ghế của một booking */
    public function getBookingSeats($booking_id) {
        $stmt = $this->conn->prepare("
            SELECT bd.price_at_booking, s.row_name, s.col_number, s.type
            FROM booking_details bd
            JOIN seats s ON s.id = bd.seat_id
            WHERE bd.booking_id = ?
            ORDER BY s.row_name, s.col_number
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Xác nhận booking (pending → completed) */
    public function confirmBooking($booking_id) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status='completed', paid_at=NOW() WHERE id=?");
        return $stmt->execute([$booking_id]);
    }

    /** Lấy tất cả bookings của một user */
    public function getBookingsByUser($user_id) {
        $stmt = $this->conn->prepare("
            SELECT b.id, b.total_amount, b.status, b.payment_method, b.payment_ref, b.created_at, b.paid_at,
                   m.title as movie_title, m.banner_url,
                   st.start_time, st.end_time,
                   c.name as cinema_name, r.name as room_name
            FROM bookings b
            JOIN showtimes st ON st.id = b.showtime_id
            JOIN movies m ON m.id = st.movie_id
            JOIN rooms r ON r.id = st.room_id
            JOIN cinemas c ON c.id = r.cinema_id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
