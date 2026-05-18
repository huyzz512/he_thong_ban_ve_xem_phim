<?php
require_once '../../config/admin_guard.php';
require_once '../../config/Database.php';
require_once '../../Models/CinemaModel.php';
require_once '../../Controllers/CinemaController.php';

$db = (new Database())->getConnection();
$cinemaController = new CinemaController($db);

$message = '';

// Handle form submissions for Add, Sửa, Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $hotline = $_POST['hotline'] ?? '';
        $id = $_POST['id'] ?? null;

        if ($action === 'add') {
            $result = $cinemaController->addCinema($name, $address, $hotline);
            $message = $result['message'];
        } elseif ($action === 'edit' && $id) {
            $result = $cinemaController->updateCinema($id, $name, $address, $hotline);
            $message = $result['message'];
        } elseif ($action === 'delete' && $id) {
            $result = $cinemaController->deleteCinema($id);
            $message = $result['message'];
        }
    }
}

// Fetch real data from DB
$cinemas = $cinemaController->getAllCinemas();

ob_start();
?>
<?php if ($message): ?>
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
    <h1 class="text-2xl font-bold text-gray-800">Quản lý Rạp chiếu</h1>
    <div class="flex items-center space-x-2">
        <div class="relative">
            <input type="text" id="searchInput" onkeyup="filterTable('searchInput', 'dataTable')" placeholder="Tìm kiếm rạp..." class="pl-9 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm w-64 transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <button onclick="openModal('addModal')" class="bg-blue-600 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm Rạp chiếu
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table id="dataTable" class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tên rạp</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Địa chỉ</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Đường dây nóng</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (count($cinemas) > 0): ?>
                <?php foreach ($cinemas as $cinema): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm text-gray-500">#<?php echo $cinema['id']; ?></td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cinema['name']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($cinema['address']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($cinema['hotline']); ?></td>
                    <td class="px-6 py-4 text-sm text-right space-x-3 flex justify-end">
                        <a href="rooms.php?cinema_id=<?php echo $cinema['id']; ?>" class="text-indigo-600 font-medium hover:text-indigo-800">Rooms</a>
                        <button onclick="openSửaModal('<?php echo $cinema['id']; ?>', '<?php echo addslashes($cinema['name']); ?>', '<?php echo addslashes($cinema['address']); ?>', '<?php echo addslashes($cinema['hotline']); ?>')" class="text-blue-600 font-medium hover:text-blue-800">Sửa</button>
                        <form method="POST" action="cinemas.php" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa rạp này không?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $cinema['id']; ?>">
                            <button type="submit" class="text-red-600 font-medium hover:text-red-800">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No cinemas found. Click "Thêm Rạp chiếu" to get started.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Thuộc rạp Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Thêm Rạp chiếu</h3>
        <form method="POST" action="cinemas.php">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên rạp</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ</label>
                <input type="text" name="address" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dây nóng</label>
                <input type="text" name="hotline" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Lưu Rạp</button>
            </div>
        </form>
    </div>
</div>

<!-- Sửa Rạp chiếu Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Sửa Rạp chiếu</h3>
        <form method="POST" action="cinemas.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên rạp</label>
                <input type="text" name="name" id="edit_name" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ</label>
                <input type="text" name="address" id="edit_address" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dây nóng</label>
                <input type="text" name="hotline" id="edit_hotline" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Cập nhật Rạp</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSửaModal(id, name, address, hotline) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_address').value = address;
    document.getElementById('edit_hotline').value = hotline;
    openModal('editModal');
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>








