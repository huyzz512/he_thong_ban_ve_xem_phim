<?php
require_once '../config/Database.php';
require_once '../Models/UserModel.php';
require_once '../Models/BookingModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$db           = (new Database())->getConnection();
$userModel    = new UserModel($db);
$bookingModel = new BookingModel($db);

$user     = $userModel->findById($_SESSION['user_id']);
$bookings = $bookingModel->getBookingsByUser($_SESSION['user_id']);

$message     = '';
$messageType = '';

// ── CẬP NHẬT HỒ SƠ ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        if (empty($full_name)) {
            $message = 'Vui lòng nhập họ tên.';
            $messageType = 'error';
        } else {
            $userModel->updateProfile($_SESSION['user_id'], $full_name, $phone);
            $_SESSION['user_name'] = $full_name;
            $message = 'Cập nhật hồ sơ thành công!';
            $messageType = 'success';
            $user = $userModel->findById($_SESSION['user_id']);
        }
    } elseif ($action === 'change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($oldPass, $user['password'])) {
            $message = 'Mật khẩu hiện tại không đúng.';
            $messageType = 'error';
        } elseif (strlen($newPass) < 6) {
            $message = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            $messageType = 'error';
        } elseif ($newPass !== $confirm) {
            $message = 'Xác nhận mật khẩu không khớp.';
            $messageType = 'error';
        } else {
            $userModel->updatePassword($_SESSION['user_id'], $newPass);
            $message = 'Đổi mật khẩu thành công!';
            $messageType = 'success';
        }
    }
}

$activeTab = $_GET['tab'] ?? 'profile';

ob_start();
?>
<div class="max-w-5xl mx-auto">

<!-- HEADER HỒ SƠ -->
<div class="bg-gradient-to-r from-gray-900 to-gray-700 rounded-2xl p-6 mb-6 flex items-center gap-5 text-white">
    <div class="w-16 h-16 rounded-full bg-primary flex items-center justify-center text-3xl font-extrabold flex-shrink-0">
        <?= mb_substr($user['full_name'], 0, 1, 'UTF-8') ?>
    </div>
    <div>
        <h1 class="text-2xl font-extrabold"><?= htmlspecialchars($user['full_name']) ?></h1>
        <p class="text-gray-400 text-sm"><?= htmlspecialchars($user['email']) ?></p>
        <?php if (!empty($user['phone'])): ?>
        <p class="text-gray-400 text-sm">📞 <?= htmlspecialchars($user['phone']) ?></p>
        <?php endif; ?>
    </div>
    <div class="ml-auto text-right hidden sm:block">
        <div class="text-3xl font-extrabold text-primary"><?= count($bookings) ?></div>
        <div class="text-xs text-gray-400">vé đã đặt</div>
    </div>
</div>

<!-- TABS -->
<div class="flex gap-1 bg-white rounded-xl border border-gray-100 shadow-sm p-1 mb-6">
    <a href="?tab=profile" class="flex-1 text-center py-2.5 rounded-lg text-sm font-semibold transition <?= $activeTab==='profile'?'bg-primary text-white shadow':'text-gray-500 hover:text-gray-800' ?>">
        👤 Hồ sơ cá nhân
    </a>
    <a href="?tab=bookings" class="flex-1 text-center py-2.5 rounded-lg text-sm font-semibold transition <?= $activeTab==='bookings'?'bg-primary text-white shadow':'text-gray-500 hover:text-gray-800' ?>">
        🎟 Lịch sử đặt vé
        <?php if (count($bookings) > 0): ?>
        <span class="ml-1 <?= $activeTab==='bookings'?'bg-white text-primary':'bg-gray-100 text-gray-500' ?> rounded-full px-1.5 py-0.5 text-xs"><?= count($bookings) ?></span>
        <?php endif; ?>
    </a>
    <a href="?tab=password" class="flex-1 text-center py-2.5 rounded-lg text-sm font-semibold transition <?= $activeTab==='password'?'bg-primary text-white shadow':'text-gray-500 hover:text-gray-800' ?>">
        🔑 Đổi mật khẩu
    </a>
</div>

<!-- TOAST -->
<?php if ($message): ?>
<div class="mb-5 px-4 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 <?= $messageType==='success'?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-600 border border-red-200' ?>">
    <?= $messageType==='success'?'✅':'❌' ?> <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- ══ TAB: HỒ SƠ ══ -->
<?php if ($activeTab === 'profile'): ?>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold text-gray-800 text-lg mb-5">Thông tin cá nhân</h2>
    <form method="POST" action="?tab=profile" class="space-y-4 max-w-lg">
        <input type="hidden" name="action" value="update_profile">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Họ và tên *</label>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>"
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition text-sm">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 text-gray-400 text-sm cursor-not-allowed">
            <p class="text-xs text-gray-400 mt-1">Email không thể thay đổi.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Số điện thoại</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                placeholder="VD: 0901234567"
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition text-sm">
        </div>
        <button type="submit" class="bg-primary hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl transition">
            Lưu thay đổi
        </button>
    </form>
</div>

<!-- ══ TAB: LỊCH SỬ ĐẶT VÉ ══ -->
<?php elseif ($activeTab === 'bookings'): ?>
<div class="space-y-4">
<?php if (empty($bookings)): ?>
<div class="text-center py-20 bg-white rounded-2xl border border-dashed border-gray-200">
    <div class="text-5xl mb-3">🎫</div>
    <h3 class="text-lg font-bold text-gray-400 mb-2">Chưa có vé nào</h3>
    <p class="text-gray-400 text-sm mb-4">Hãy đặt vé phim đầu tiên của bạn!</p>
    <a href="movies.php" class="inline-block bg-primary text-white font-bold px-6 py-2.5 rounded-xl hover:bg-red-700 transition">Xem phim</a>
</div>
<?php else: ?>
<?php foreach ($bookings as $b):
    $isClaimed = ($b['status'] === 'pending' && !empty($b['payment_ref']));
    if ($isClaimed) {
        $statusLabel = 'Đang chờ duyệt';
        $statusClass = 'bg-blue-100 text-blue-700';
    } else {
        $statusMap = [
            'pending'   => ['Đặt vé mới',       'bg-yellow-100 text-yellow-700'],
            'completed' => ['Đã xác nhận',    'bg-green-100  text-green-700'],
            'cancelled' => ['Đã hủy',         'bg-gray-100   text-gray-500'],
        ];
        [$statusLabel, $statusClass] = $statusMap[$b['status']] ?? ['Không rõ', 'bg-gray-100 text-gray-500'];
    }
?>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col sm:flex-row">
    <!-- Ảnh phim -->
    <div class="sm:w-24 h-32 sm:h-auto flex-shrink-0 bg-gray-100 overflow-hidden">
        <img src="<?= htmlspecialchars($b['banner_url'] ?: 'https://placehold.co/96x128/1f2937/ffffff?text=🎬') ?>"
             class="w-full h-full object-cover" alt="">
    </div>
    <!-- Thông tin -->
    <div class="flex-grow p-4 flex flex-col sm:flex-row gap-3">
        <div class="flex-grow">
            <div class="flex items-start gap-3 mb-2">
                <h3 class="font-bold text-gray-900 text-base"><?= htmlspecialchars($b['movie_title']) ?></h3>
                <span class="<?= $statusClass ?> text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0"><?= $statusLabel ?></span>
            </div>
            <div class="text-sm text-gray-500 space-y-0.5">
                <div>📍 <?= htmlspecialchars($b['cinema_name']) ?> – <?= htmlspecialchars($b['room_name']) ?></div>
                <div>🕐 <?= date('H:i d/m/Y', strtotime($b['start_time'])) ?></div>
                <div>🗓 Đặt lúc <?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></div>
            </div>
        </div>
        <div class="flex-shrink-0 flex flex-col items-end justify-between">
            <div class="text-lg font-extrabold text-primary"><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</div>
            <div class="flex gap-2 mt-2">
                <?php if ($b['status'] === 'pending'): ?>
                <a href="payment.php?id=<?= $b['id'] ?>"
                   class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-bold px-3 py-1.5 rounded-lg transition">
                   Thanh toán
                </a>
                <?php elseif ($b['status'] === 'completed'): ?>
                <a href="ticket.php?id=<?= $b['id'] ?>"
                   class="text-xs bg-primary hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                   <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16.97 16.97l2.83 2.83M6 20l14-14"></path></svg>
                   Xem vé
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ══ TAB: ĐỔI MẬT KHẨU ══ -->
<?php elseif ($activeTab === 'password'): ?>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold text-gray-800 text-lg mb-5">Đổi mật khẩu</h2>
    <form method="POST" action="?tab=password" class="space-y-4 max-w-lg">
        <input type="hidden" name="action" value="change_password">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mật khẩu hiện tại</label>
            <input type="password" name="old_password" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition text-sm" placeholder="••••••••">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mật khẩu mới</label>
            <input type="password" name="new_password" required minlength="6"
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition text-sm" placeholder="Ít nhất 6 ký tự">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition text-sm" placeholder="Nhập lại mật khẩu mới">
        </div>
        <button type="submit" class="bg-primary hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl transition">
            Đổi mật khẩu
        </button>
    </form>
</div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
