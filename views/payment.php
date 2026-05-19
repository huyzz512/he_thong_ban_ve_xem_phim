<?php
require_once '../config/Database.php';
require_once '../Models/BookingModel.php';
require_once '../Models/CinemaModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$db           = (new Database())->getConnection();
$bookingModel = new BookingModel($db);
$cinemaModel  = new CinemaModel($db);

$booking_id = (int)($_GET['id'] ?? 0);
$booking    = $bookingModel->getBookingById($booking_id);

// Bảo vệ: chỉ chủ booking mới được xem
if (!$booking || (int)$booking['user_id'] !== (int)$_SESSION['user_id']) {
    header('Location: home.php');
    exit();
}

// Nếu đã thanh toán rồi thì redirect thẳng
if ($booking['status'] === 'completed') {
    header("Location: booking_success.php?id=$booking_id");
    exit();
}

// ── XỬ LÝ: Khách báo đã chuyển khoản ───────────────────────────────────────────────────
// KHÔNG tự xác nhận — chỉ đánh dấu để admin xác nhận sau
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Đánh dấu payment_ref là 'CLAIMED' — giữ nguyên status = 'pending' để admin duyệt
    $stmt = $db->prepare("UPDATE bookings SET payment_ref='CLAIMED' WHERE id=? AND user_id=? AND status='pending'");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    // Redirect sang trang chờ admin xác nhận
    header("Location: payment_pending.php?id=$booking_id");
    exit();
}

// ── XỬ LÝ: Hủy đơn ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $stmt = $db->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    header('Location: profile.php');
    exit();
}

$bookedSeats = $bookingModel->getBookingSeats($booking_id);

// ── Lấy thông tin ngân hàng theo rạp ──────────────────────────────────────
$cinemaStmt = $db->prepare("
    SELECT c.bank_id, c.bank_account_no, c.bank_account_name
    FROM bookings b
    JOIN showtimes st ON st.id = b.showtime_id
    JOIN rooms r ON r.id = st.room_id
    JOIN cinemas c ON c.id = r.cinema_id
    WHERE b.id = ?
");
$cinemaStmt->execute([$booking_id]);
$cinemaBank  = $cinemaStmt->fetch(PDO::FETCH_ASSOC);

$transferRef  = 'EAUT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
$amount       = (int)$booking['total_amount'];
$bankId       = !empty($cinemaBank['bank_id'])           ? $cinemaBank['bank_id']           : 'MB';
$accountNo    = !empty($cinemaBank['bank_account_no'])   ? $cinemaBank['bank_account_no']   : '0000000000';
$accountName  = !empty($cinemaBank['bank_account_name']) ? $cinemaBank['bank_account_name'] : 'EAUT CINEMA';
$hasBankConfig = !empty($cinemaBank['bank_account_no']);

$vietQrUrl    = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-compact2.png"
              . "?amount={$amount}&addInfo=" . urlencode($transferRef)
              . "&accountName=" . urlencode($accountName);

// Đếm ngược 15 phút (tính từ created_at)
$expireAt     = strtotime($booking['created_at']) + 15 * 60;
$remaining    = $expireAt - time();


ob_start();
?>

<!-- ĐƯỜNG DẪN -->
<div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
    <a href="home.php" class="hover:text-primary">Trang chủ</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Thanh toán đơn #<?= str_pad($booking_id, 6, '0', STR_PAD_LEFT) ?></span>
</div>

<!-- STEPPER -->
<div class="flex items-center justify-center max-w-md mx-auto mb-10">
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">✓</div>
        <span class="text-sm font-medium text-green-600">Chọn ghế</span>
    </div>
    <div class="h-px w-12 bg-primary mx-2"></div>
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">3</div>
        <span class="text-sm font-medium text-primary">Thanh toán</span>
    </div>
    <div class="h-px w-12 bg-gray-300 mx-2"></div>
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-bold">4</div>
        <span class="text-sm font-medium text-gray-400">Hoàn tất</span>
    </div>
</div>

<!-- COUNTDOWN -->
<?php if ($remaining > 0): ?>
<div class="max-w-3xl mx-auto bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 mb-6 flex items-center gap-3">
    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <p class="text-sm text-amber-700">Vui lòng hoàn tất thanh toán trong <b id="countdown" class="text-amber-900 font-bold text-base">--:--</b> — đơn hàng sẽ tự hủy nếu quá thời gian.</p>
</div>
<script>
let remaining = <?= max(0, $remaining) ?>;
const el = document.getElementById('countdown');
const t = setInterval(() => {
    if (remaining <= 0) { clearInterval(t); el.textContent = 'Hết giờ!'; return; }
    const m = Math.floor(remaining/60), s = remaining%60;
    el.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    remaining--;
}, 1000);
</script>
<?php else: ?>
<div class="max-w-3xl mx-auto bg-red-50 border border-red-200 rounded-xl px-5 py-3 mb-6 text-sm text-red-600 font-medium">
    ⚠ Đơn hàng đã hết hạn thanh toán (15 phút). Vui lòng đặt lại.
</div>
<?php endif; ?>

<div class="max-w-3xl mx-auto grid md:grid-cols-2 gap-6">
    <!-- CỘT TRÁI: QR + Hướng dẫn -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Chuyển khoản ngân hàng</h2>
        <p class="text-xs text-gray-500 mb-4 text-center">Quét mã QR hoặc chuyển khoản thủ công. Không cần nhắn nội dung — mã đã nhúng trong QR.</p>

        <!-- VietQR -->
        <div class="border-4 border-primary rounded-2xl p-1 mb-4 shadow-lg">
            <img src="<?= htmlspecialchars($vietQrUrl) ?>" alt="VietQR" class="w-52 h-52 rounded-xl" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode("EAUT|$booking_id|$amount") ?>'">
        </div>

        <!-- Thông tin tài khoản -->
        <div class="w-full space-y-2 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500">Ngân hàng</span>
                <span class="font-bold text-gray-800"><?= $bankId ?> Bank</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500">Số tài khoản</span>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-800" id="accNo"><?= $accountNo ?></span>
                    <button onclick="copyText('<?= $accountNo ?>')" class="text-xs text-primary hover:underline">Sao chép</button>
                </div>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500">Chủ tài khoản</span>
                <span class="font-bold text-gray-800"><?= $accountName ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500">Số tiền</span>
                <span class="font-extrabold text-primary text-lg"><?= number_format($amount, 0, ',', '.') ?>đ</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-500">Nội dung CK</span>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-800 bg-yellow-50 px-2 py-0.5 rounded border border-yellow-200" id="ref"><?= $transferRef ?></span>
                    <button onclick="copyText('<?= $transferRef ?>')" class="text-xs text-primary hover:underline">Sao chép</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: Chi tiết đơn + Xác nhận -->
    <div class="flex flex-col gap-4">
        <!-- Chi tiết đơn -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Chi tiết đơn hàng
            </h3>
            <div class="text-sm space-y-2">
                <div class="font-bold text-gray-900 truncate"><?= htmlspecialchars($booking['movie_title']) ?></div>
                <div class="text-gray-500">📍 <?= htmlspecialchars($booking['cinema_name']) ?> – <?= htmlspecialchars($booking['room_name']) ?></div>
                <div class="text-gray-500">🕐 <?= date('H:i d/m/Y', strtotime($booking['start_time'])) ?></div>
                <div class="mt-2 pt-2 border-t border-gray-50">
                    <div class="text-gray-500 mb-1">Ghế đã chọn:</div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($bookedSeats as $s): ?>
                        <span class="px-2 py-1 rounded text-xs font-bold <?= $s['type']==='vip'?'bg-yellow-100 text-yellow-700':'bg-gray-100 text-gray-700' ?>">
                            <?= $s['row_name'].$s['col_number'] ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form xác nhận -->
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
            <h3 class="font-bold text-green-800 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Sau khi chuyển khoản
            </h3>
            <p class="text-sm text-green-700 mb-4">Nhấn nút bên dưới để xác nhận bạn đã hoàn tất chuyển khoản. Vé điện tử sẽ được cấp ngay lập tức.</p>
            <form method="POST" action="payment.php?id=<?= $booking_id ?>">
                <button type="submit" name="confirm_payment"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Tôi đã chuyển khoản xong
                </button>
            </form>
        </div>

        <!-- Hủy đơn -->
        <form method="POST" action="payment.php?id=<?= $booking_id ?>" onsubmit="return confirm('Hủy đơn đặt vé này?')">
            <button type="submit" name="cancel_booking"
                class="w-full text-sm text-red-500 hover:text-red-700 font-medium py-2 transition">
                Hủy đơn hàng
            </button>
        </form>
    </div>
</div>

<script>
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        const el = document.createElement('div');
        el.textContent = '✓ Đã sao chép!';
        el.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1f2937;color:#fff;padding:10px 18px;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;animation:fadeInUp .3s ease';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
