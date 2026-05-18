<?php

class SeatModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Lấy tất cả ghế của một phòng, kèm trạng thái booked/available cho suất chiếu cụ thể.
     * Trạng thái: 'available' | 'booked'
     */
    public function getSeatsWithStatus($room_id, $showtime_id) {
        $query = "
            SELECT s.id, s.row_name, s.col_number, s.type,
                   CASE 
                     WHEN bd.id IS NOT NULL THEN 'booked'
                     ELSE 'available'
                   END AS status
            FROM seats s
            LEFT JOIN booking_details bd ON bd.seat_id = s.id
            LEFT JOIN bookings b ON b.id = bd.booking_id 
                                 AND b.showtime_id = :showtime_id
                                 AND b.status != 'cancelled'
            WHERE s.room_id = :room_id
            ORDER BY s.row_name ASC, s.col_number ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':room_id' => $room_id, ':showtime_id' => $showtime_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Lấy thông tin ghế theo mảng id */
    public function getSeatsByIds(array $ids) {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("SELECT * FROM seats WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Kiểm tra ghế còn trống không (tránh race-condition) */
    public function areSeatsAvailable(array $seat_ids, $showtime_id) {
        if (empty($seat_ids)) return false;
        $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
        $query = "
            SELECT COUNT(*) FROM booking_details bd
            JOIN bookings b ON b.id = bd.booking_id
            WHERE b.showtime_id = ?
              AND b.status != 'cancelled'
              AND bd.seat_id IN ($placeholders)
        ";
        $params = array_merge([$showtime_id], $seat_ids);
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }
}
?>
