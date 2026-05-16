<?php
require_once '../../config/Database.php';
require_once '../../Models/ShowtimeModel.php';
require_once '../../Controllers/ShowtimeController.php';
require_once '../../Models/MovieModel.php';
require_once '../../Controllers/MovieController.php';
require_once '../../Models/RoomModel.php';
require_once '../../Controllers/RoomController.php';

$db = (new Database())->getConnection();
$showtimeController = new ShowtimeController($db);
$movieController = new MovieController($db);
$roomController = new RoomController($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $movie_id = (int)$_POST['movie_id'];
            $room_id = (int)$_POST['room_id'];
            $start_time = $_POST['start_time'];
            $base_price = (float)$_POST['base_price'];
            $is_holiday = isset($_POST['is_holiday']) ? 1 : 0;
            $is_golden_hour = isset($_POST['is_golden_hour']) ? 1 : 0;
            
            try {
                $result = $showtimeController->addShowtime($movie_id, $room_id, $start_time, $base_price, $is_holiday, $is_golden_hour);
                $message = $result['message'];
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            $result = $showtimeController->deleteShowtime($id);
            $message = $result['message'];
        }
    }
}

$showtimes = $showtimeController->getAllShowtimes();
$movies = $movieController->getAllMovies();
$rooms = $roomController->getAllRooms();

ob_start();
?>
<?php if ($message): ?>
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
    <h1 class="text-2xl font-bold text-gray-800">Quản lý Lịch chiếu</h1>
    <div class="flex items-center space-x-2">
        <div class="relative">
            <input type="text" id="searchInput" onkeyup="filterTable('searchInput', 'dataTable')" placeholder="Tìm kiếm lịch chiếu..." class="pl-9 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm w-64 transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <button onclick="openModal('addModal')" class="bg-blue-600 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Lên lịch chiếu mới
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table id="dataTable" class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Phim</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Phòng chiếu</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Thời gian</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Giá vé</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (count($showtimes) > 0): ?>
                <?php foreach ($showtimes as $st): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($st['movie_title']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($st['cinema_name']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($st['room_name']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 font-medium"><?php echo date('M d, Y', strtotime($st['start_time'])); ?></div>
                        <div class="text-xs text-gray-500 mt-1">
                            <span class="bg-gray-100 px-2 py-1 rounded text-gray-700 border border-gray-200"><?php echo date('H:i', strtotime($st['start_time'])); ?> - <?php echo date('H:i', strtotime($st['end_time'])); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-green-600">$<?php echo number_format($st['base_price'], 2); ?></div>
                        <div class="text-xs space-x-1 mt-1 flex">
                            <?php if ($st['is_holiday']): ?><span class="px-1 bg-red-100 text-red-700 rounded border border-red-200">Lễ</span><?php endif; ?>
                            <?php if ($st['is_golden_hour']): ?><span class="px-1 bg-yellow-100 text-yellow-700 rounded border border-yellow-200">Giờ vàng</span><?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-right space-x-3 flex justify-end items-center h-full">
                        <form method="POST" action="showtimes.php" class="inline mt-2" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lịch chiếu này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $st['id']; ?>">
                            <button type="submit" class="text-red-600 font-medium hover:text-red-800">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No showtimes scheduled. Click "Lên lịch chiếu mới" to get started.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Showtime Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-lg shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Lên lịch chiếu mới</h3>
        <p class="text-xs text-gray-600 mb-4 bg-yellow-50 p-3 rounded border border-yellow-200 leading-relaxed">
            <strong class="text-yellow-800">Thuật toán Check trùng lặp Bật:</strong> The system will auto-calculate the End Time (Phim Thời lượng + 15 mins cleaning buffer) and will explicitly reject the schedule if it overlaps with an existing showtime in the chosen room.
        </p>
        <form method="POST" action="showtimes.php">
            <input type="hidden" name="action" value="add">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Select Phim</label>
                <select name="movie_id" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Choose a Phim --</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?> (<?php echo $m['duration_minutes']; ?> mins)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Chọn Phòng chiếu</label>
                <select name="room_id" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Chọn một phòng --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['cinema_name'] . ' - ' . $r['room_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian Bắt đầu</label>
                    <input type="datetime-local" name="start_time" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vé gốc</label>
                    <input type="number" step="0.01" name="base_price" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mb-6 bg-gray-50 p-3 rounded-lg border border-gray-200">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Giá vé Matrix Rules (Optional)</label>
                <div class="flex space-x-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_holiday" class="rounded text-blue-600 w-4 h-4 mr-2">
                        <span class="text-sm text-gray-700">Lễiday (+15%)</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_golden_hour" class="rounded text-blue-600 w-4 h-4 mr-2">
                        <span class="text-sm text-gray-700">Giờ vàngen Hour (+10%)</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Kiểm tra & Lưu lịch</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>








