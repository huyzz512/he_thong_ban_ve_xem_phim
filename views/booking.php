<?php
require_once '../config/Database.php';
require_once '../Models/ShowtimeModel.php';
require_once '../Models/SeatModel.php';
require_once '../Models/BookingModel.php';
require_once '../Models/MovieModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$db            = (new Database())->getConnection();
$showtimeModel = new ShowtimeModel($db);
$seatModel     = new SeatModel($db);
$bookingModel  = new BookingModel($db);
$movieModel    = new MovieModel($db);

// ── XỬ LÝ POST: Tạo booking ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    $showtime_id = (int)$_POST['showtime_id'];
    $seat_ids    = array_map('intval', $_POST['seat_ids'] ?? []);

    if (empty($seat_ids)) {
        $bookingError = 'Vui lòng chọn ít nhất 1 ghế.';
    } elseif (!$seatModel->areSeatsAvailable($seat_ids, $showtime_id)) {
        $bookingError = 'Một số ghế bạn chọn vừa được người khác đặt. Vui lòng chọn lại.';
    } else {
        $st     = $showtimeModel->getShowtimeById($showtime_id);
        $prices = [];
        foreach ($seatModel->getSeatsByIds($seat_ids) as $s) {
            $prices[] = $bookingModel->calcSeatPrice($st['base_price'], $s['type'], $st['is_holiday'], $st['is_golden_hour']);
        }
        $booking_id = $bookingModel->createBooking($_SESSION['user_id'], $showtime_id, $seat_ids, $prices);
        if ($booking_id) {
            $bookingModel->confirmBooking($booking_id);
            header("Location: booking_success.php?id=$booking_id");
            exit();
        }
        $bookingError = 'Đã xảy ra lỗi. Vui lòng thử lại.';
    }
}

// ── XÁC ĐỊNH BƯỚC ─────────────────────────────────────────────────────────
$showtime_id = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);
$movie_id    = (int)($_GET['movie_id']    ?? 0);
$step        = $showtime_id ? 2 : 1;

$movie    = null;
$showtime = null;
$seats    = [];

if ($step === 2 && $showtime_id) {
    $showtime = $showtimeModel->getShowtimeById($showtime_id);
    if (!$showtime) { header('Location: home.php'); exit(); }
    $movie_id = $showtime['movie_id'];
    $seats    = $seatModel->getSeatsWithStatus($showtime['room_id'], $showtime_id);
}

if ($movie_id) {
    $movie = $movieModel->getMovieById($movie_id);
}

// Nhóm suất chiếu theo rạp → ngày
$groupedShowtimes = [];
if ($step === 1 && $movie_id) {
    foreach ($showtimeModel->getShowtimesByMovie($movie_id) as $st) {
        $cinemaKey = $st['cinema_id'] . '|' . $st['cinema_name'];
        $dateKey   = date('Y-m-d', strtotime($st['start_time']));
        $groupedShowtimes[$cinemaKey][$dateKey][] = $st;
    }
}

// Nhóm ghế theo hàng
$seatsByRow = [];
foreach ($seats as $s) {
    $seatsByRow[$s['row_name']][] = $s;
}

ob_start();
?>
<style>
.seat { cursor:pointer; transition: all .2s; }
.seat.available { background:#e5e7eb; color:#374151; }
.seat.available:hover { background:#E11D48; color:#fff; transform:scale(1.12); }
.seat.selected { background:#E11D48; color:#fff; transform:scale(1.08); box-shadow:0 0 0 3px rgba(225,29,72,0.3); }
.seat.booked { background:#374151; color:#6b7280; cursor:not-allowed; opacity:.6; }
.seat.vip.available { background:#fef3c7; color:#92400e; border:2px solid #f59e0b; }
.seat.vip.available:hover { background:#f59e0b; color:#fff; }
.seat.vip.selected { background:#f59e0b; color:#fff; box-shadow:0 0 0 3px rgba(245,158,11,.35); }
</style>

<!-- BREADCRUMB -->
<div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
    <a href="home.php" class="hover:text-primary">Trang chủ</a>
    <span>/</span>
    <?php if ($movie): ?>
    <a href="detail.php?id=<?= $movie['id'] ?>" class="hover:text-primary"><?= htmlspecialchars($movie['title']) ?></a>
    <span>/</span>
    <?php endif; ?>
    <span class="text-gray-800 font-medium">Đặt vé</span>
</div>

<!-- STEPPER -->
<div class="flex items-center justify-center gap-0 mb-10 max-w-md mx-auto">
    <div class="flex items-center gap-2 flex-1">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step>=1?'bg-primary text-white':'bg-gray-200 text-gray-500' ?>">1</div>
        <span class="text-sm font-medium <?= $step>=1?'text-primary':'text-gray-400' ?>">Chọn suất chiếu</span>
    </div>
    <div class="h-px w-8 bg-gray-300 mx-2"></div>
    <div class="flex items-center gap-2 flex-1">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step>=2?'bg-primary text-white':'bg-gray-200 text-gray-500' ?>">2</div>
        <span class="text-sm font-medium <?= $step>=2?'text-primary':'text-gray-400' ?>">Chọn ghế</span>
    </div>
    <div class="h-px w-8 bg-gray-300 mx-2"></div>
    <div class="flex items-center gap-2 flex-1">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $step>=3?'bg-primary text-white':'bg-gray-200 text-gray-500' ?>">3</div>
        <span class="text-sm font-medium <?= $step>=3?'text-primary':'text-gray-400' ?>">Xác nhận</span>
    </div>
</div>

<?php if ($step === 1): ?>
<!-- ═══════════════════ BƯỚC 1: CHỌN SUẤT CHIẾU ═══════════════════ -->
<div class="max-w-4xl mx-auto">
    <?php if ($movie): ?>
    <div class="flex gap-5 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-8">
        <img src="<?= htmlspecialchars($movie['banner_url'] ?: 'https://placehold.co/80x120') ?>"
             class="w-20 h-28 object-cover rounded-xl flex-shrink-0" alt="">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-1"><?= htmlspecialchars($movie['title']) ?></h1>
            <p class="text-sm text-primary font-medium mb-1"><?= htmlspecialchars($movie['genre'] ?: '') ?></p>
            <span class="text-sm text-gray-500">⏱ <?= $movie['duration_minutes'] ?> phút</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($groupedShowtimes)): ?>
    <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-gray-200">
        <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <h3 class="text-lg font-bold text-gray-400 mb-2">Chưa có suất chiếu</h3>
        <p class="text-gray-400 text-sm">Phim này hiện chưa có lịch chiếu nào sắp tới.</p>
    </div>
    <?php else: ?>
    <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-primary pl-4">Chọn suất chiếu</h2>
    <div class="space-y-6">
    <?php foreach ($groupedShowtimes as $cinemaKey => $dateGroups):
        [, $cinemaName] = explode('|', $cinemaKey);
        $firstDate = array_key_first($dateGroups);
        $allSt = $dateGroups[$firstDate];
        $cinemaAddr = $allSt[0]['cinema_address'] ?? '';
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-800 text-white px-6 py-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
            <div>
                <div class="font-bold"><?= htmlspecialchars($cinemaName) ?></div>
                <div class="text-xs text-gray-400"><?= htmlspecialchars($cinemaAddr) ?></div>
            </div>
        </div>

        <!-- Tab ngày -->
        <div class="border-b border-gray-100 px-6 py-3 flex gap-3 overflow-x-auto hide-scrollbar" id="tabs-<?= md5($cinemaKey) ?>">
        <?php $isFirst = true; foreach ($dateGroups as $date => $sts): ?>
            <button class="date-tab flex-shrink-0 px-5 py-2 rounded-lg font-medium text-sm transition <?= $isFirst?'bg-primary text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>"
                    data-cinema="<?= md5($cinemaKey) ?>" data-date="<?= $date ?>"
                    onclick="switchDate('<?= md5($cinemaKey) ?>', '<?= $date ?>', this)">
                <?= date('d/m', strtotime($date)) ?> (<?= ['CN','T2','T3','T4','T5','T6','T7'][date('w', strtotime($date))] ?>)
            </button>
        <?php $isFirst = false; endforeach; ?>
        </div>

        <!-- Panel giờ chiếu cho từng ngày -->
        <?php $isFirst = true; foreach ($dateGroups as $date => $sts): ?>
        <div class="date-panel p-6 <?= $isFirst?'':'hidden' ?>" data-cinema="<?= md5($cinemaKey) ?>" data-date="<?= $date ?>">
            <div class="flex flex-wrap gap-3">
            <?php foreach ($sts as $st):
                $isSoldOut = false; // có thể thêm logic check sau
            ?>
                <a href="booking.php?showtime_id=<?= $st['id'] ?>"
                   class="group flex flex-col items-center border-2 <?= $isSoldOut ? 'border-gray-200 opacity-50 cursor-not-allowed pointer-events-none' : 'border-gray-200 hover:border-primary' ?> rounded-xl px-5 py-3 transition">
                    <span class="text-lg font-extrabold text-gray-800 group-hover:text-primary"><?= date('H:i', strtotime($st['start_time'])) ?></span>
                    <span class="text-xs text-gray-400">~ <?= date('H:i', strtotime($st['end_time'])) ?></span>
                    <span class="mt-1 text-xs font-semibold text-primary"><?= number_format($st['base_price'], 0, ',', '.') ?>đ</span>
                    <span class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($st['room_name']) ?></span>
                    <?php if ($st['is_holiday'] || $st['is_golden_hour']): ?>
                    <div class="flex gap-1 mt-1">
                        <?php if ($st['is_holiday']): ?><span class="text-[10px] bg-red-100 text-red-600 px-1.5 rounded font-bold">LỄ</span><?php endif; ?>
                        <?php if ($st['is_golden_hour']): ?><span class="text-[10px] bg-yellow-100 text-yellow-600 px-1.5 rounded font-bold">VÀNG</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            </div>
        </div>
        <?php $isFirst = false; endforeach; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($step === 2 && $showtime): ?>
<!-- ═══════════════════ BƯỚC 2: SƠ ĐỒ GHẾ ═══════════════════ -->
<?php if (isset($bookingError)): ?>
<div class="max-w-4xl mx-auto bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-4 text-sm font-medium">
    ⚠ <?= htmlspecialchars($bookingError) ?>
</div>
<?php endif; ?>

<div class="max-w-6xl mx-auto flex flex-col lg:flex-row gap-8">
    <!-- SƠ ĐỒ GHẾ -->
    <div class="flex-grow min-w-0">
        <!-- Thông tin suất -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 flex gap-4">
            <img src="<?= htmlspecialchars($showtime['banner_url'] ?: 'https://placehold.co/60x90') ?>"
                 class="w-14 h-20 object-cover rounded-lg flex-shrink-0" alt="">
            <div class="flex-grow min-w-0">
                <h2 class="font-extrabold text-gray-900 text-lg mb-1 truncate"><?= htmlspecialchars($showtime['movie_title']) ?></h2>
                <div class="text-sm text-gray-500 space-y-0.5">
                    <div>📍 <?= htmlspecialchars($showtime['cinema_name']) ?> – <?= htmlspecialchars($showtime['room_name']) ?></div>
                    <div>🕐 <?= date('H:i d/m/Y', strtotime($showtime['start_time'])) ?> → <?= date('H:i', strtotime($showtime['end_time'])) ?></div>
                    <div>💰 Giá từ <b class="text-primary"><?= number_format($showtime['base_price'], 0, ',', '.') ?>đ</b>
                        <?php if ($showtime['is_holiday']): ?><span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold ml-1">+15% LỄ</span><?php endif; ?>
                        <?php if ($showtime['is_golden_hour']): ?><span class="text-xs bg-yellow-100 text-yellow-600 px-1.5 py-0.5 rounded font-bold ml-1">+10% GIỜ VÀNG</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="booking.php?movie_id=<?= $showtime['movie_id'] ?>" class="text-sm text-gray-400 hover:text-primary flex-shrink-0">← Đổi suất</a>
        </div>

        <!-- Màn hình -->
        <div class="text-center mb-6">
            <div class="inline-block bg-gradient-to-b from-gray-300 to-gray-100 rounded-lg w-3/4 py-2 text-xs font-bold text-gray-500 shadow tracking-widest uppercase mb-1">MÀN HÌNH</div>
        </div>

        <!-- Sơ đồ ghế -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-x-auto">
            <form id="seatForm" method="POST" action="booking.php">
                <input type="hidden" name="confirm_booking" value="1">
                <input type="hidden" name="showtime_id" value="<?= $showtime_id ?>">

                <div class="inline-block min-w-max mx-auto">
                <?php foreach ($seatsByRow as $row => $rowSeats): ?>
                <div class="flex items-center gap-1.5 mb-1.5">
                    <span class="w-6 text-xs font-bold text-gray-400 text-right flex-shrink-0"><?= $row ?></span>
                    <?php foreach ($rowSeats as $s):
                        $cls = $s['status'] === 'booked' ? 'booked' : 'available';
                        $isVip = $s['type'] === 'vip';
                        $price = $bookingModel->calcSeatPrice($showtime['base_price'], $s['type'], $showtime['is_holiday'], $showtime['is_golden_hour']);
                    ?>
                    <button type="button"
                        class="seat <?= $cls ?> <?= $isVip?'vip':'' ?> w-8 h-8 rounded text-xs font-bold flex items-center justify-center"
                        <?= $s['status']==='booked' ? 'disabled' : '' ?>
                        data-seat-id="<?= $s['id'] ?>"
                        data-price="<?= $price ?>"
                        data-label="<?= $row.$s['col_number'] ?>"
                        data-type="<?= $s['type'] ?>"
                        title="<?= $row.$s['col_number'] ?> (<?= $s['type'] === 'vip'?'VIP':'Thường' ?>) - <?= number_format($price,0,',','.') ?>đ"
                        onclick="toggleSeat(this)">
                        <?= $s['col_number'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Hidden inputs cho seat_ids được chèn bởi JS -->
                <div id="selectedSeatInputs"></div>
            </form>
        </div>

        <!-- Chú thích -->
        <div class="flex justify-center gap-6 mt-4 text-xs text-gray-600">
            <div class="flex items-center gap-2"><div class="w-5 h-5 rounded bg-gray-200"></div>Trống</div>
            <div class="flex items-center gap-2"><div class="w-5 h-5 rounded bg-primary"></div>Đang chọn</div>
            <div class="flex items-center gap-2"><div class="w-5 h-5 rounded bg-gray-700"></div>Đã bán</div>
            <div class="flex items-center gap-2"><div class="w-5 h-5 rounded bg-yellow-200 border-2 border-yellow-400"></div>VIP</div>
        </div>
    </div>

    <!-- PANEL XÁC NHẬN -->
    <div class="lg:w-80 flex-shrink-0">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
            <h3 class="font-bold text-gray-800 text-lg mb-4 border-b border-gray-100 pb-3">Đơn đặt vé</h3>

            <div id="noSeatMsg" class="text-center py-6 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                <p class="text-sm">Chưa chọn ghế nào</p>
            </div>

            <div id="seatSummary" class="hidden">
                <div id="seatList" class="space-y-2 mb-4 text-sm max-h-48 overflow-y-auto"></div>
                <div class="border-t border-gray-100 pt-3 mb-5">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>Số ghế:</span><span id="seatCount" class="font-medium">0</span>
                    </div>
                    <div class="flex justify-between text-lg font-extrabold text-gray-900">
                        <span>Tổng cộng:</span>
                        <span id="totalPrice" class="text-primary">0đ</span>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <button type="button" onclick="submitBooking()"
                    class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Xác nhận đặt vé
                </button>
                <?php else: ?>
                <a href="../auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                    class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    Đăng nhập để đặt vé
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>.hide-scrollbar::-webkit-scrollbar{display:none;}.hide-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}</style>

<script>
// ── Tab ngày ──────────────────────────────────────────────────────────────
function switchDate(cinemaId, date, btn) {
    document.querySelectorAll(`.date-panel[data-cinema="${cinemaId}"]`).forEach(p => p.classList.add('hidden'));
    document.querySelectorAll(`.date-tab[data-cinema="${cinemaId}"]`).forEach(b => {
        b.classList.remove('bg-primary','text-white');
        b.classList.add('bg-gray-100','text-gray-600');
    });
    document.querySelector(`.date-panel[data-cinema="${cinemaId}"][data-date="${date}"]`).classList.remove('hidden');
    btn.classList.remove('bg-gray-100','text-gray-600');
    btn.classList.add('bg-primary','text-white');
}

// ── Chọn ghế ────────────────────────────────────────────────────────────
const selectedSeats = {};

function toggleSeat(btn) {
    const id    = btn.dataset.seatId;
    const price = parseInt(btn.dataset.price);
    const label = btn.dataset.label;
    const type  = btn.dataset.type;

    if (selectedSeats[id]) {
        delete selectedSeats[id];
        btn.classList.remove('selected');
        btn.classList.add('available');
    } else {
        selectedSeats[id] = { price, label, type };
        btn.classList.remove('available');
        btn.classList.add('selected');
    }
    updateSummary();
}

function updateSummary() {
    const keys = Object.keys(selectedSeats);
    const total = keys.reduce((s, k) => s + selectedSeats[k].price, 0);

    document.getElementById('seatCount').textContent = keys.length;
    document.getElementById('totalPrice').textContent = total.toLocaleString('vi-VN') + 'đ';

    const list = document.getElementById('seatList');
    list.innerHTML = '';
    keys.forEach(id => {
        const s = selectedSeats[id];
        list.innerHTML += `<div class="flex justify-between items-center py-1.5 border-b border-gray-50">
            <div>
                <span class="font-bold text-gray-800">Ghế ${s.label}</span>
                <span class="text-xs ml-1 ${s.type==='vip'?'text-yellow-600 font-semibold':'text-gray-400'}">${s.type==='vip'?'VIP':'Thường'}</span>
            </div>
            <span class="font-semibold text-primary text-sm">${s.price.toLocaleString('vi-VN')}đ</span>
        </div>`;
    });

    // Toggle visibility
    const hasSeats = keys.length > 0;
    document.getElementById('noSeatMsg').classList.toggle('hidden', hasSeats);
    document.getElementById('seatSummary').classList.toggle('hidden', !hasSeats);

    // Hidden inputs
    const container = document.getElementById('selectedSeatInputs');
    container.innerHTML = keys.map(id => `<input type="hidden" name="seat_ids[]" value="${id}">`).join('');
}

function submitBooking() {
    if (Object.keys(selectedSeats).length === 0) {
        alert('Vui lòng chọn ít nhất 1 ghế!');
        return;
    }
    document.getElementById('seatForm').submit();
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
