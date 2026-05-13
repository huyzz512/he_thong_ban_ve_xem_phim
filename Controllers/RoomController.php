<?php

class RoomController {
    private $roomModel;

    public function __construct($db) {
        $this->roomModel = new RoomModel($db);
    }

    public function addRoom($cinema_id, $name, $total_rows, $total_columns) {
        $conn = $this->roomModel->getConnection();
        
        try {
            // Begin transaction to ensure room and seats are created atomically
            $conn->beginTransaction();

            $room_id = $this->roomModel->createRoom($cinema_id, $name, $total_rows, $total_columns);

            $start_char = 'A';
            
            // Calculate middle rows for VIP seats (e.g., roughly the middle 30-40% of the room)
            $middle_start_index = floor($total_rows * 0.4);
            $middle_end_index = floor($total_rows * 0.7) - 1;

            for ($i = 0; $i < $total_rows; $i++) {
                $row_name = chr(ord($start_char) + $i); // Generates A, B, C, etc.
                
                // Set default type to normal, change to VIP for middle rows
                $type = 'normal';
                if ($i >= $middle_start_index && $i <= $middle_end_index) {
                    $type = 'vip';
                }

                for ($j = 1; $j <= $total_columns; $j++) {
                    $this->roomModel->createSeat($room_id, $row_name, $j, $type);
                }
            }

            $conn->commit();
            return ["status" => "success", "message" => "Room and {$total_rows}x{$total_columns} seats generated successfully."];

        } catch (Exception $e) {
            $conn->rollBack();
            return ["status" => "error", "message" => "Failed to create room: " . $e->getMessage()];
        }
    }
    public function getRoomsByCinema($cinema_id) {
        return $this->roomModel->getRoomsByCinema($cinema_id);
    }

    public function getAllRooms() {
        return $this->roomModel->getAllRooms();
    }

    public function deleteRoom($id) {
        $success = $this->roomModel->deleteRoom($id);
        if ($success) {
            return ["status" => "success", "message" => "Xóa phòng thành công."];
        }
        return ["status" => "error", "message" => "Lỗi: Không thể xóa phòng."];
    }
}
?>

