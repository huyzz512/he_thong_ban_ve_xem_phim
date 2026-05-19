<?php
require_once '../config/Database.php';
require_once '../Models/BookingModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$db           = (new Database())->getConnection();
$bookingModel = new BookingModel($db);

$booking_id = (int)($_GET['id'] ?? 0);
$booking    = $bookingModel->getBookingById($booking_id);

if (!$booking || (int)$booking['user_id'] !== (int)$_SESSION['user_id'] || $booking['status'] !== 'completed') {
    header('Location: profile.php?tab=bookings');
    exit();
}

$bookedSeats = $bookingModel->getBookingSeats($booking_id);

// Tạo dữ liệu QR check-in: mã xác thực đơn giản
$qrData     = 'EAUT|BK' . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . '|' . md5($booking_id . 'eaut_cinema_secret');
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrData) . '&color=1f2937&bgcolor=ffffff';
$ticketCode = 'EAUT' . strtoupper(substr(md5($booking_id . 'eaut'), 0, 8));

ob_start();
?>

<style>
@media print {
    nav, header, footer, .no-print { display: none !important; }
    body { background: white !important; }
    .ticket-wrap { box-shadow: none !important; }
}
</style>

<div class="max-w-lg mx-auto">
    <!-- Nút actions -->
    <div class="flex justify-between items-center mb-5 no-print">
        <a href="profile.php?tab=bookings" class="text-sm text-gray-500 hover:text-primary flex items-center gap-1">
            ← Lịch sử đặt vé
        </a>
        <button onclick="window.print()" class="flex items-center gap-2 text-sm bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            In vé
        </button>
    </div>

    <!-- VÉ ĐIỆN TỬ -->
    <div class="ticket-wrap bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
        <!-- Header vé -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-700 p-6 text-white text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-5 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMiIgZmlsbD0id2hpdGUiLz48L3N2Zz4=')]"></div>
            <div class="text-xs font-bold tracking-[0.3em] text-gray-400 uppercase mb-2">EAUT Cinema · Vé Điện Tử</div>
            <h1 class="text-2xl font-extrabold leading-tight"><?= htmlspecialchars($booking['movie_title']) ?></h1>
        </div>

        <!-- Thông tin chính -->
        <div class="px-6 py-5 grid grid-cols-2 gap-4 text-sm border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Rạp chiếu</p>
                <p class="font-bold text-gray-800"><?= htmlspecialchars($booking['cinema_name']) ?></p>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($booking['cinema_address']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Phòng chiếu</p>
                <p class="font-bold text-gray-800"><?= htmlspecialchars($booking['room_name']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Ngày chiếu</p>
                <p class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($booking['start_time'])) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Giờ chiếu</p>
                <p class="font-bold text-gray-800"><?= date('H:i', strtotime($booking['start_time'])) ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1.5">Ghế ngồi</p>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($bookedSeats as $s): ?>
                    <span class="px-2.5 py-1 rounded-lg font-bold text-sm <?= $s['type']==='vip'?'bg-yellow-100 text-yellow-800 border border-yellow-300':'bg-gray-100 text-gray-700' ?>">
                        <?= $s['row_name'].$s['col_number'] ?>
                        <?php if ($s['type']==='vip'): ?><sup class="text-yellow-500 text-[9px] ml-0.5 font-black">VIP</sup><?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Đường cắt vé -->
        <div class="flex items-center relative py-0">
            <div class="w-8 h-8 rounded-full bg-gray-100 -ml-4 shadow-inner flex-shrink-0"></div>
            <div class="flex-grow border-t-2 border-dashed border-gray-200"></div>
            <div class="w-8 h-8 rounded-full bg-gray-100 -mr-4 shadow-inner flex-shrink-0"></div>
        </div>

        <!-- QR Code -->
        <div class="px-6 py-6 flex flex-col items-center">
            <div class="border-4 border-gray-800 rounded-2xl p-2 mb-4 shadow-lg">
                <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Check-in" class="w-52 h-52 rounded-lg">
            </div>
            <p class="text-xs text-gray-400 mb-1">Mã vé</p>
            <p class="font-mono font-extrabold text-lg text-gray-800 tracking-widest"><?= $ticketCode ?></p>
            <p class="text-xs text-gray-400 mt-3 text-center max-w-xs">
                Xuất trình mã QR này khi check-in tại quầy rạp. Mỗi mã chỉ dùng một lần.
            </p>
        </div>

        <!-- Footer vé -->
        <div class="bg-gray-50 px-6 py-3 flex justify-between items-center text-xs text-gray-400">
            <span>Đặt lúc <?= date('H:i d/m/Y', strtotime($booking['created_at'])) ?></span>
            <span class="font-bold text-primary"><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</span>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-5 no-print">
        Vé điện tử hợp lệ — không cần in giấy. Chúc bạn xem phim vui! 🎬
    </p>
</div>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
