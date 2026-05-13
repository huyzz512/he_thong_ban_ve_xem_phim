<?php
require_once '../../config/Database.php';
require_once '../../Models/MovieModel.php';
require_once '../../Controllers/MovieController.php';

$db = (new Database())->getConnection();
$movieController = new MovieController($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
        $banner_url = $_POST['banner_url'] ?? '';
        $trailer_url = $_POST['trailer_url'] ?? '';
        $status = $_POST['status'] ?? 'showing';
        $id = $_POST['id'] ?? null;

        if ($action === 'add') {
            $result = $movieController->addMovie($title, $description, $duration_minutes, $banner_url, $trailer_url, $status);
            $message = $result['message'];
        } elseif ($action === 'edit' && $id) {
            $result = $movieController->updateMovie($id, $title, $description, $duration_minutes, $banner_url, $trailer_url, $status);
            $message = $result['message'];
        } elseif ($action === 'delete' && $id) {
            $result = $movieController->deleteMovie($id);
            $message = $result['message'];
        }
    }
}

$movies = $movieController->getAllMovies();

ob_start();
?>
<?php if ($message): ?>
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Phims Management</h1>
    <button onclick="openModal('addModal')" class="bg-blue-600 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition shadow-sm flex items-center">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Add New Phim
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-16">Ảnh bìa</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tiêu đề phim</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Thời lượng</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (count($movies) > 0): ?>
                <?php foreach ($movies as $movie): ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4">
                        <?php if ($movie['banner_url']): ?>
                            <img src="<?php echo htmlspecialchars($movie['banner_url']); ?>" alt="Ảnh bìa" class="w-12 h-16 object-cover rounded shadow-sm border border-gray-200">
                        <?php else: ?>
                            <div class="w-12 h-16 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-xs border border-gray-200">Không có ảnh</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($movie['title']); ?></div>
                        <div class="text-xs text-gray-500 truncate w-48 mt-1"><?php echo htmlspecialchars($movie['description']); ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 font-medium"><?php echo $movie['duration_minutes']; ?> mins</td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($movie['status'] === 'showing'): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-700 border border-green-200 rounded font-medium text-xs">Đang chiếu</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 border border-gray-200 rounded font-medium text-xs">Ngừng chiếu</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-right space-x-3">
                        <button onclick="openSửaModal('<?php echo $movie['id']; ?>', '<?php echo addslashes($movie['title']); ?>', '<?php echo addslashes($movie['description']); ?>', '<?php echo $movie['duration_minutes']; ?>', '<?php echo addslashes($movie['banner_url']); ?>', '<?php echo addslashes($movie['trailer_url']); ?>', '<?php echo $movie['status']; ?>')" class="text-blue-600 font-medium hover:text-blue-800">Sửa</button>
                        <form method="POST" action="movies.php" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa phim này? Các lịch chiếu và vé liên quan sẽ bị xóa theo.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $movie['id']; ?>">
                            <button type="submit" class="text-red-600 font-medium hover:text-red-800">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Không có dữ liệu phim. Bấm "Thêm phim mới" để bắt đầu.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Phim Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-lg shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Add New Phim</h3>
        <form method="POST" action="movies.php">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tiêu đề phim</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả nội dung</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Thời lượng (minutes)</label>
                    <input type="number" name="duration_minutes" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="showing">Đang chiếu</option>
                        <option value="stopped">Ngừng chiếu</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn Ảnh bìa</label>
                <input type="url" name="banner_url" placeholder="https://..." class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn Trailer</label>
                <input type="url" name="trailer_url" placeholder="https://..." class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Lưu phim</button>
            </div>
        </form>
    </div>
</div>

<!-- Sửa Phim Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 transition-opacity">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-lg shadow-2xl rounded-xl bg-white">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Sửa Phim</h3>
        <form method="POST" action="movies.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tiêu đề phim</label>
                <input type="text" name="title" id="edit_title" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả nội dung</label>
                <textarea name="description" id="edit_description" rows="3" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Thời lượng (minutes)</label>
                    <input type="number" name="duration_minutes" id="edit_duration" required class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="showing">Đang chiếu</option>
                        <option value="stopped">Ngừng chiếu</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn Ảnh bìa</label>
                <input type="url" name="banner_url" id="edit_banner_url" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn Trailer</label>
                <input type="url" name="trailer_url" id="edit_trailer_url" class="w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Cập nhật Phim</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSửaModal(id, title, description, duration, banner, trailer, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_duration').value = duration;
    document.getElementById('edit_banner_url').value = banner;
    document.getElementById('edit_trailer_url').value = trailer;
    document.getElementById('edit_status').value = status;
    openModal('editModal');
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>








