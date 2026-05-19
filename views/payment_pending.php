<?php
require_once '../config/Database.php';
require_once '../Models/BookingModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit(); }

$db           = (new Database())->getConnection();
$bookingModel = new BookingModel($db);

$booking_id = (int)($_GET['id'] ?? 0);
$booking    = $bookingModel->getBookingById($booking_id);

if (!$booking || (int)$booking['user_id'] !== (int)$_SESSION['user_id']) {
    header('Location: home.php'); exit();
}

// Nếu admin đã xác nhận rồi → chuyển sang trang success
if ($booking['status'] === 'completed') {
    header("Location: booking_success.php?id=$booking_id"); exit();
}

ob_start();
?>

<div class="max-w-lg mx-auto py-10 text-center">
    <!-- Icon đồng hồ chờ -->
    <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
        <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>

    <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Đang chờ xác nhận</h1>
    <p class="text-gray-500 mb-1">
        Chúng tôi đã nhận được thông báo chuyển khoản của bạn cho đơn
        <span class="font-bold text-primary">#<?= str_pad($booking_id, 6, '0', STR_PAD_LEFT) ?></span>.
    </p>
    <p class="text-gray-500 mb-8">
        Nhân viên sẽ kiểm tra và xác nhận giao dịch trong vòng <b class="text-gray-700">15–30 phút</b>.
        Vé điện tử sẽ xuất hiện trong <a href="profile.php?tab=bookings" class="text-primary hover:underline font-semibold">lịch sử đặt vé</a> của bạn ngay sau khi được duyệt.
    </p>

    <!-- Thông tin đơn -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-left mb-6">
        <h2 class="font-bold text-gray-800 mb-3">Chi tiết đơn hàng</h2>
        <div class="text-sm space-y-2 text-gray-600">
            <div class="flex justify-between">
                <span>Phim</span>
                <span class="font-semibold text-gray-800"><?= htmlspecialchars($booking['movie_title']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Rạp</span>
                <span class="font-semibold text-gray-800"><?= htmlspecialchars($booking['cinema_name']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Suất chiếu</span>
                <span class="font-semibold text-gray-800"><?= date('H:i d/m/Y', strtotime($booking['start_time'])) ?></span>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-2 mt-2">
                <span>Tổng tiền</span>
                <span class="font-extrabold text-primary text-base"><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</span>
            </div>
        </div>
    </div>

    <!-- Trạng thái badge -->
    <div class="inline-flex items-center gap-2 bg-yellow-50 border border-yellow-200 text-yellow-700 font-semibold px-4 py-2.5 rounded-full text-sm mb-8">
        <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
        Chờ nhân viên xác nhận thanh toán
    </div>

    <!-- Actions -->
    <div class="flex gap-3 justify-center">
        <a href="profile.php?tab=bookings"
           class="bg-primary hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl transition">
            Xem lịch sử đặt vé
        </a>
        <a href="home.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-6 py-3 rounded-xl transition">
            Về trang chủ
        </a>
    </div>

    <p class="text-xs text-gray-400 mt-6">
        Cần hỗ trợ? Liên hệ hotline hoặc fanpage của EAUT Cinema.
    </p>
</div>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
