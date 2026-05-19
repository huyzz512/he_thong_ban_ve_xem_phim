<?php
require_once '../../config/admin_guard.php';
require_once '../../config/Database.php';
require_once '../../Models/CinemaModel.php';

$db          = (new Database())->getConnection();
$cinemaModel = new CinemaModel($db);
$message     = '';

// ── XỬ LÝ ACTIONS ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Cập nhật thông tin ngân hàng của rạp
    if ($action === 'update_bank') {
        $cinema_id   = (int)$_POST['cinema_id'];
        $bank_id     = trim($_POST['bank_id'] ?? '');
        $account_no  = trim($_POST['bank_account_no'] ?? '');
        $account_name = trim($_POST['bank_account_name'] ?? '');
        $cinemaModel->updateBankInfo($cinema_id, $bank_id, $account_no, $account_name);
        $message = 'Đã cập nhật thông tin ngân hàng.';
    }

    // Xác nhận đặt vé (pending → completed)
    elseif ($action === 'confirm_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $stmt = $db->prepare("UPDATE bookings SET status='completed', paid_at=NOW() WHERE id=? AND status='pending'");
        $stmt->execute([$booking_id]);
        $message = 'Đã xác nhận vé #' . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . ' thành công.';
    }

    // Từ chối / Hủy đặt vé
    elseif ($action === 'cancel_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $stmt = $db->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND status='pending'");
        $stmt->execute([$booking_id]);
        $message = 'Đã hủy đơn đặt vé #' . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . '.';
    }

    if ($message) {
        $_SESSION['message'] = $message;
        header('Location: payments.php' . (isset($_GET['cinema_id']) ? '?cinema_id=' . (int)$_GET['cinema_id'] : ''));
        exit();
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// ── LỌC DỮ LIỆU ──────────────────────────────────────────────────────────
$cinemas         = $cinemaModel->getAllCinemas();
$selectedCinema  = (int)($_GET['cinema_id'] ?? 0);
$statusFilter    = $_GET['status'] ?? '';
$search          = trim($_GET['search'] ?? '');
$dateFrom        = $_GET['date_from'] ?? '';
$dateTo          = $_GET['date_to'] ?? '';

// Query giao dịch
$where  = ['1=1'];
$params = [];

if ($selectedCinema) {
    $where[]  = 'c.id = ?';
    $params[] = $selectedCinema;
}
if ($statusFilter) {
    $where[]  = 'b.status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where[]  = '(u.full_name LIKE ? OR u.email LIKE ? OR m.title LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom) {
    $where[]  = 'DATE(b.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[]  = 'DATE(b.created_at) <= ?';
    $params[] = $dateTo;
}

$whereStr = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT b.id, b.total_amount, b.status, b.payment_method, b.payment_ref, b.created_at, b.paid_at,
           u.full_name as user_name, u.email as user_email, u.phone as user_phone,
           m.title as movie_title,
           st.start_time, st.end_time,
           r.name as room_name,
           c.id as cinema_id, c.name as cinema_name,
           c.bank_id, c.bank_account_no, c.bank_account_name,
           COUNT(bd.id) as seat_count
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN showtimes st ON st.id = b.showtime_id
    JOIN movies m ON m.id = st.movie_id
    JOIN rooms r ON r.id = st.room_id
    JOIN cinemas c ON c.id = r.cinema_id
    LEFT JOIN booking_details bd ON bd.booking_id = b.id
    WHERE $whereStr
    GROUP BY b.id
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng
$totalRevenue  = array_sum(array_column(array_filter($bookings, fn($b) => $b['status'] === 'completed'), 'total_amount'));
$countPending  = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$countDone     = count(array_filter($bookings, fn($b) => $b['status'] === 'completed'));
$countCancelled = count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled'));

// Thông tin rạp đang chọn
$currentCinema = $selectedCinema ? $cinemaModel->getCinemaById($selectedCinema) : null;

// Danh sách ngân hàng phổ biến Việt Nam
$bankList = [
    'MB'  => 'MB Bank',
    'VCB' => 'Vietcombank',
    'TCB' => 'Techcombank',
    'ACB' => 'ACB',
    'VPB' => 'VPBank',
    'BID' => 'BIDV',
    'CTG' => 'VietinBank',
    'STB' => 'Sacombank',
    'TPB' => 'TPBank',
    'MSB' => 'MSB',
    'SHB' => 'SHB',
    'VIB' => 'VIB',
    'HDB' => 'HDBank',
    'OCB' => 'OCB',
    'NAB' => 'Nam A Bank',
    'CAKE' => 'CAKE by VPBank',
    'MOM' => 'Viettel Money',
];

ob_start();
?>

<!-- Tiêu đề -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý Thanh toán</h1>
        <p class="text-sm text-gray-500 mt-1">Giao dịch đặt vé & xác nhận thanh toán</p>
    </div>
</div>

<!-- Stats tổng -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Doanh thu <?= $selectedCinema ? '' : 'toàn hệ thống' ?></p>
        <p class="text-xl font-extrabold text-green-600"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Chờ xác nhận</p>
        <p class="text-xl font-extrabold text-yellow-600"><?= $countPending ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Đã xác nhận</p>
        <p class="text-xl font-extrabold text-green-600"><?= $countDone ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 mb-1">Đã hủy</p>
        <p class="text-xl font-extrabold text-gray-500"><?= $countCancelled ?></p>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-6">
    <!-- SIDEBAR: Chọn rạp -->
    <aside class="lg:w-64 flex-shrink-0">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
            <div class="bg-gray-800 text-white px-4 py-3 text-sm font-bold">Chọn rạp chiếu</div>
            <nav class="py-2">
                <a href="payments.php" class="flex items-center justify-between px-4 py-2.5 text-sm transition <?= !$selectedCinema ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <span>🏢 Tất cả rạp</span>
                </a>
                <?php foreach ($cinemas as $c): ?>
                <a href="payments.php?cinema_id=<?= $c['id'] ?>" class="flex items-center justify-between px-4 py-2.5 text-sm transition <?= $selectedCinema === (int)$c['id'] ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <span class="truncate"><?= htmlspecialchars($c['name']) ?></span>
                    <?php if (!empty($c['bank_account_no'])): ?>
                    <span class="text-green-400 text-xs ml-1 flex-shrink-0" title="Đã cấu hình ngân hàng">✓</span>
                    <?php else: ?>
                    <span class="text-red-400 text-xs ml-1 flex-shrink-0" title="Chưa cấu hình ngân hàng">!</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Cấu hình ngân hàng -->
        <?php if ($currentCinema): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2">
                🏦 Ngân hàng — <?= htmlspecialchars($currentCinema['name']) ?>
            </h3>
            <form method="POST" action="payments.php?cinema_id=<?= $selectedCinema ?>" class="space-y-2.5">
                <input type="hidden" name="action" value="update_bank">
                <input type="hidden" name="cinema_id" value="<?= $selectedCinema ?>">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Ngân hàng</label>
                    <select name="bank_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Chọn ngân hàng --</option>
                        <?php foreach ($bankList as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $currentCinema['bank_id'] === $code ? 'selected' : '' ?>><?= $name ?> (<?= $code ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Số tài khoản</label>
                    <input type="text" name="bank_account_no" value="<?= htmlspecialchars($currentCinema['bank_account_no'] ?? '') ?>"
                        placeholder="VD: 0987654321"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tên tài khoản</label>
                    <input type="text" name="bank_account_name" value="<?= htmlspecialchars($currentCinema['bank_account_name'] ?? '') ?>"
                        placeholder="VD: EAUT CINEMA"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg text-sm transition">
                    Lưu thông tin
                </button>
            </form>
            <?php if (!empty($currentCinema['bank_account_no'])): ?>
            <!-- Preview QR -->
            <div class="mt-3 pt-3 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400 mb-2">Preview QR ngân hàng</p>
                <img src="https://img.vietqr.io/image/<?= urlencode($currentCinema['bank_id']) ?>-<?= urlencode($currentCinema['bank_account_no']) ?>-compact2.png?accountName=<?= urlencode($currentCinema['bank_account_name']) ?>"
                     class="w-28 h-28 mx-auto rounded-lg border border-gray-200"
                     onerror="this.style.display='none'"
                     alt="QR Preview">
                <p class="text-xs text-gray-500 mt-1 font-medium"><?= htmlspecialchars($currentCinema['bank_account_name'] ?? '') ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($currentCinema['bank_account_no'] ?? '') ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <div class="flex-grow min-w-0">
        <!-- Bộ lọc -->
        <form method="GET" action="payments.php" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-end">
            <?php if ($selectedCinema): ?>
            <input type="hidden" name="cinema_id" value="<?= $selectedCinema ?>">
            <?php endif; ?>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tìm kiếm</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Tên KH, email, phim..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-52 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Trạng thái</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tất cả</option>
                    <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Chờ xác nhận</option>
                    <option value="completed" <?= $statusFilter==='completed'?'selected':'' ?>>Đã xác nhận</option>
                    <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Đã hủy</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="<?= $dateFrom ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="<?= $dateTo ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Lọc</button>
            <a href="payments.php<?= $selectedCinema ? '?cinema_id='.$selectedCinema : '' ?>" class="text-sm text-gray-500 hover:text-gray-700 py-2">Xóa lọc</a>
        </form>

        <!-- Bảng giao dịch -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Mã đơn</th>
                        <th class="px-4 py-3">Khách hàng</th>
                        <th class="px-4 py-3">Phim / Suất</th>
                        <?php if (!$selectedCinema): ?><th class="px-4 py-3">Rạp</th><?php endif; ?>
                        <th class="px-4 py-3 text-right">Số tiền</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="8" class="text-center py-12 text-gray-400 text-sm">Không có giao dịch nào.</td></tr>
                <?php else: ?>
                <?php foreach ($bookings as $b):
                    // Kiểm tra payment_ref có CLAIMED hay không
                    $isClaimed = ($b['status'] === 'pending' && !empty($b['payment_ref']) && str_contains($b['payment_ref'], 'CLAIMED'));
                    if ($isClaimed) {
                        $statusInfo = ['Khách báo đã CK', 'bg-blue-100 text-blue-700'];
                    } else {
                        $statusInfo = [
                            'pending'   => ['Chờ thanh toán', 'bg-yellow-100 text-yellow-700'],
                            'completed' => ['Xác nhận',         'bg-green-100  text-green-700'],
                            'cancelled' => ['Đã hủy',          'bg-gray-100   text-gray-500'],
                        ][$b['status']] ?? ['Không rõ', 'bg-gray-100 text-gray-500'];
                    }
                ?>
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-sm font-bold text-gray-700">#<?= str_pad($b['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        <div class="text-xs text-gray-400"><?= $b['seat_count'] ?> ghế</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($b['user_name']) ?></div>
                        <div class="text-xs text-gray-400"><?= htmlspecialchars($b['user_email']) ?></div>
                        <?php if (!empty($b['user_phone'])): ?>
                        <div class="text-xs text-gray-400">📞 <?= htmlspecialchars($b['user_phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 max-w-[180px]">
                        <div class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($b['movie_title']) ?></div>
                        <div class="text-xs text-gray-400"><?= date('H:i d/m/Y', strtotime($b['start_time'])) ?></div>
                        <div class="text-xs text-gray-400"><?= htmlspecialchars($b['room_name']) ?></div>
                    </td>
                    <?php if (!$selectedCinema): ?>
                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($b['cinema_name']) ?></td>
                    <?php endif; ?>
                    <td class="px-4 py-3 text-right">
                        <span class="font-extrabold text-green-600 text-sm"><?= number_format($b['total_amount'], 0, ',', '.') ?>đ</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold <?= $statusInfo[1] ?>"><?= $statusInfo[0] ?></span>
                        <?php if ($b['paid_at']): ?>
                        <div class="text-xs text-gray-400 mt-0.5">Lúc <?= date('H:i d/m', strtotime($b['paid_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">
                        <?= date('H:i<br>d/m/Y', strtotime($b['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($b['status'] === 'pending'): ?>
                        <div class="flex flex-col gap-1.5 items-center">
                            <form method="POST" action="payments.php<?= $selectedCinema ? '?cinema_id='.$selectedCinema : '' ?>">
                                <input type="hidden" name="action" value="confirm_booking">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <button type="submit" onclick="return confirm('Xác nhận đặt vé #<?= str_pad($b['id'],6,'0',STR_PAD_LEFT) ?> đã thanh toán?')"
                                    class="w-full bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Xác nhận
                                </button>
                            </form>
                            <form method="POST" action="payments.php<?= $selectedCinema ? '?cinema_id='.$selectedCinema : '' ?>">
                                <input type="hidden" name="action" value="cancel_booking">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <button type="submit" onclick="return confirm('Hủy đơn này?')"
                                    class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                    Từ chối
                                </button>
                            </form>
                        </div>
                        <?php elseif ($b['status'] === 'completed'): ?>
                        <span class="text-green-500 text-lg">✓</span>
                        <?php else: ?>
                        <span class="text-gray-400 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tổng kết doanh thu theo rạp (chỉ hiện khi chọn "Tất cả") -->
        <?php if (!$selectedCinema && !empty($bookings)): ?>
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Doanh thu theo rạp</h3>
            <div class="space-y-3">
            <?php
            $byC = [];
            foreach ($bookings as $b) {
                $key = $b['cinema_name'];
                if (!isset($byC[$key])) $byC[$key] = ['revenue' => 0, 'count' => 0, 'pending' => 0];
                if ($b['status'] === 'completed') { $byC[$key]['revenue'] += $b['total_amount']; $byC[$key]['count']++; }
                if ($b['status'] === 'pending') $byC[$key]['pending']++;
            }
            $maxRev = max(array_column($byC, 'revenue')) ?: 1;
            foreach ($byC as $cName => $cData):
            ?>
            <div class="flex items-center gap-3 text-sm">
                <div class="w-36 truncate text-gray-700 font-medium flex-shrink-0"><?= htmlspecialchars($cName) ?></div>
                <div class="flex-grow bg-gray-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-green-500 h-full rounded-full transition-all" style="width:<?= $maxRev > 0 ? round($cData['revenue']/$maxRev*100) : 0 ?>%"></div>
                </div>
                <div class="text-right flex-shrink-0 w-36">
                    <span class="font-bold text-green-600"><?= number_format($cData['revenue'], 0, ',', '.') ?>đ</span>
                    <?php if ($cData['pending']): ?>
                    <span class="text-xs text-yellow-600 ml-1">(<?= $cData['pending'] ?> chờ)</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>
