<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/Database.php';
require_once '../../Models/RoomModel.php';
require_once '../../Controllers/RoomController.php';

$db = (new Database())->getConnection();
$roomController = new RoomController($db);

$cinema_id = $_GET['cinema_id'] ?? null;
if (!$cinema_id) {
    die("<div style='padding:20px; font-family:sans-serif;'>Không tìm thấy mã rạp. <a href='cinemas.php' style='color:blue;'>Quay lại danh sách rạp</a></div>");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'add') {
            $name = $_POST['name'] ?? '';
            $total_rows = (int)$_POST['total_rows'];
            $total_columns = (int)$_POST['total_columns'];
            $result = $roomController->addRoom($cinema_id, $name, $total_rows, $total_columns);
            $message = $result['message'];
        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            $result = $roomController->deleteRoom($id);
            $message = $result['message'];
        } elseif ($action === 'import') {
            if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['import_file']['tmp_name'];
                $file_ext = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
                if (strtolower($file_ext) === 'csv') {
                    $handle = fopen($file_tmp, "r");
                    // Read header row
                    $header = fgetcsv($handle, 1000, ",");
                    $imported = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 3) {
                            $name = trim($data[0]);
                            $rows = (int)$data[1];
                            $cols = (int)$data[2];
                            if ($name && $rows > 0 && $cols > 0) {
                                $roomController->addRoom($cinema_id, $name, $rows, $cols);
                                $imported++;
                            }
                        }
                    }
                    fclose($handle);
                    $message = "Đã import thành công $imported phòng chiếu.";
                } else {
                    $message = "Chỉ hỗ trợ file CSV.";
                }
            } else {
                $message = "Lỗi upload file.";
            }
        }
        
        if ($message !== '') {
            $_SESSION['message'] = $message;
            header("Location: rooms.php?cinema_id=" . urlencode($cinema_id));
            exit();
        }
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch real rooms for this cinema
$rooms = $roomController->getRoomsByCinema($cinema_id);

ob_start();
?>

<div class="mb-4">
    <a href="cinemas.php" class="text-blue-600 hover:text-blue-800 transition font-medium text-sm flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Quay lại danh sách rạp
    </a>
</div>

<?php if ($message): ?>
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Quản lý Phòng chiếu (Thuộc rạp #<?php echo htmlspecialchars($cinema_id); ?>)</h1>
    <div class="flex space-x-2">
        <button onclick="openModal('importRoomModal')" class="bg-green-600 text-white px-4 py-2 rounded-md font-medium hover:bg-green-700 transition shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            Import CSV
        </button>
        <button onclick="openModal('addRoomModal')" class="bg-blue-600 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm phòng chiếu
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tên phòng</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Cấu trúc</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (count($rooms) > 0): ?>
                <?php foreach ($rooms as $room): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm text-gray-500">#<?php echo $room['id']; ?></td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($room['name']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span class="px-2 py-1 bg-gray-100 rounded text-gray-700"><?php echo $room['total_rows']; ?> Số hàng ghế</span> 
                        &times; 
                        <span class="px-2 py-1 bg-gray-100 rounded text-gray-700"><?php echo $room['total_columns']; ?> Cột</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-right space-x-3 flex justify-end">
                        <form method="POST" action="rooms.php?cinema_id=<?php echo $cinema_id; ?>" class="inline" onsubmit="return confirm('Xóa phòng chiếu sẽ xóa luôn tất cả ghế và lịch chiếu liên quan. Bạn có chắc không?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                            <button type="submit" class="text-red-600 font-medium hover:text-red-800">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Chưa có phòng chiếu nào. Bấm "Thêm phòng chiếu" để tạo.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Tạo phòng chiếu & ghế</h3>
        <p class="text-sm text-gray-500 mb-4">Hệ thống sẽ TỰ ĐỘNG sinh ra sơ đồ ghế theo cấu hình. Các hàng giữa sẽ là ghế VIP.</p>
        <form method="POST" action="rooms.php?cinema_id=<?php echo $cinema_id; ?>">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên phòng</label>
                <input type="text" name="name" placeholder="e.g. IMAX 1, Room 3D" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex space-x-4 mb-5">
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tổng số hàng ghế</label>
                    <input type="number" name="total_rows" min="1" max="26" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tổng số cột ghế</label>
                    <input type="number" name="total_columns" min="1" max="50" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addRoomModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Lưu & Tạo phòng</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Room Modal -->
<div id="importRoomModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Import phòng chiếu</h3>
        <p class="text-sm text-gray-500 mb-4">Tải lên file CSV chứa danh sách phòng chiếu. File CSV cần có 3 cột: Tên phòng, Số hàng ghế, Số cột ghế (Cột đầu tiên là tiêu đề sẽ bị bỏ qua).</p>
        <p class="text-sm text-gray-500 mb-4">Ví dụ:<br/><code>Tên phòng, Số hàng ghế, Số cột ghế<br/>IMAX 1, 10, 15<br/>Room 2, 8, 12</code></p>
        <form method="POST" action="rooms.php?cinema_id=<?php echo $cinema_id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">File CSV</label>
                <input type="file" name="import_file" accept=".csv" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('importRoomModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">Tải lên & Import</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>








