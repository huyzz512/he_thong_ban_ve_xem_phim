<?php
require_once '../../config/admin_guard.php';
require_once '../../config/Database.php';
require_once '../../Models/UserModel.php';

$db = (new Database())->getConnection();
$userModel = new UserModel($db);

$message = '';

// === XỬ LÝ ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'change_role' && $id) {
        $newRole = $_POST['role'] ?? 'customer';
        if (in_array($newRole, ['admin', 'customer'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $id]);
            $message = "Đã cập nhật quyền cho người dùng.";
        }
    } elseif ($action === 'delete' && $id) {
        // Không cho xóa chính mình
        if ($id === (int)$_SESSION['user_id']) {
            $message = "Lỗi: Không thể xóa tài khoản của chính mình.";
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Đã xóa người dùng thành công.";
        }
    } elseif ($action === 'reset_password' && $id) {
        $newPassword = $_POST['new_password'] ?? '';
        if (strlen($newPassword) >= 6) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
            $message = "Đã đặt lại mật khẩu thành công.";
        } else {
            $message = "Lỗi: Mật khẩu mới phải có ít nhất 6 ký tự.";
        }
    }

    if ($message) {
        $_SESSION['message'] = $message;
        header("Location: users.php");
        exit();
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// === LẤY DỮ LIỆU ===
$search    = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$params = [];
$where  = [];
if ($search !== '') {
    $where[]  = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter !== '') {
    $where[]  = "role = ?";
    $params[] = $roleFilter;
}

$whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$stmt = $db->prepare("SELECT * FROM users $whereClause ORDER BY id DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$countAdmin    = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$countCustomer = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$countTotal    = $countAdmin + $countCustomer;

ob_start();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý Người dùng</h1>
        <p class="text-sm text-gray-500 mt-1">Quản lý tài khoản khách hàng và quản trị viên</p>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Tổng người dùng</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $countTotal; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Quản trị viên</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $countAdmin; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Khách hàng</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $countCustomer; ?></p>
        </div>
    </div>
</div>

<!-- Thanh tìm kiếm & lọc -->
<form method="GET" action="users.php" class="flex gap-3 mb-6">
    <div class="relative flex-grow max-w-sm">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Tìm theo tên hoặc email..."
            id="searchInput" onkeyup="filterTable('searchInput', 'dataTable')"
            class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm w-full">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </div>
    <select name="role" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
        <option value="" <?php echo $roleFilter === '' ? 'selected' : ''; ?>>Tất cả vai trò</option>
        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
        <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
    </select>
    <?php if ($search || $roleFilter): ?>
    <a href="users.php" class="px-3 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Xóa lọc</a>
    <?php endif; ?>
</form>

<!-- Bảng người dùng -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table id="dataTable" class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người dùng</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Vai trò</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50/50 transition <?php echo (int)$user['id'] === (int)$_SESSION['user_id'] ? 'bg-blue-50/30' : ''; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600 text-sm flex-shrink-0">
                                <?php echo mb_substr($user['full_name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                        <span class="text-xs font-normal text-blue-500 ml-1">(Bạn)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-400">#<?php echo $user['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-6 py-4">
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 border border-red-200 rounded-full font-semibold text-xs">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                Admin
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-700 border border-green-200 rounded-full font-semibold text-xs">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Khách hàng
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?php echo isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—'; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-right">
                        <div class="flex items-center justify-end gap-3">
                            <!-- Đổi role -->
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                            <button onclick="openRoleModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo $user['role']; ?>')"
                                class="text-blue-600 font-medium hover:text-blue-800 text-xs">
                                Đổi quyền
                            </button>
                            <!-- Đặt lại mật khẩu -->
                            <button onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                class="text-yellow-600 font-medium hover:text-yellow-800 text-xs">
                                Đặt lại MK
                            </button>
                            <!-- Xóa -->
                            <form method="POST" action="users.php" class="inline" onsubmit="return confirm('Xóa người dùng <?php echo addslashes($user['full_name']); ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="text-red-600 font-medium hover:text-red-800 text-xs">Xóa</button>
                            </form>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs italic">Tài khoản hiện tại</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Không tìm thấy người dùng nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Đổi quyền -->
<div id="roleModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="relative w-full max-w-md p-6 bg-white border rounded-xl shadow-2xl">
            <h3 class="text-xl font-bold text-gray-900 mb-1">Đổi quyền người dùng</h3>
            <p class="text-sm text-gray-500 mb-5">Thay đổi vai trò cho: <b id="roleModalName"></b></p>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="id" id="roleModalId">
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Vai trò mới</label>
                    <select name="role" id="roleModalSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="customer">Khách hàng</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                    <p class="text-xs text-red-500 mt-1">⚠ Cẩn thận khi cấp quyền Admin.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('roleModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Đặt lại mật khẩu -->
<div id="passwordModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="relative w-full max-w-md p-6 bg-white border rounded-xl shadow-2xl">
            <h3 class="text-xl font-bold text-gray-900 mb-1">Đặt lại mật khẩu</h3>
            <p class="text-sm text-gray-500 mb-5">Đặt mật khẩu mới cho: <b id="passwordModalName"></b></p>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="passwordModalId">
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu mới</label>
                    <input type="password" name="new_password" required minlength="6"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ít nhất 6 ký tự">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('passwordModal')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition">Đặt lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRoleModal(id, name, currentRole) {
    document.getElementById('roleModalId').value = id;
    document.getElementById('roleModalName').textContent = name;
    document.getElementById('roleModalSelect').value = currentRole;
    openModal('roleModal');
}
function openPasswordModal(id, name) {
    document.getElementById('passwordModalId').value = id;
    document.getElementById('passwordModalName').textContent = name;
    openModal('passwordModal');
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>
