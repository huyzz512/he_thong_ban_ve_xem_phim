<?php
require_once '../../config/admin_guard.php';
require_once '../../config/Database.php';
require_once '../../Models/ReportModel.php';

$db = (new Database())->getConnection();
$reportModel = new ReportModel($db);

// Lấy dữ liệu thống kê phong phú từ database
$stats = $reportModel->getDashboardStats();
$revenueByMovie = $reportModel->getRevenueAndTicketsByMovie();
$revenueByCinema = $reportModel->getRevenueByCinema();
$revenueByTime = $reportModel->getRevenueByTimeFrame();

// Tính toán tỷ lệ phần trăm
$totalTx = $stats['total_bookings'] ?: 1;
$successRate = round(($stats['completed_bookings'] / $totalTx) * 100, 1);
$cancelledRate = round(($stats['cancelled_bookings'] / $totalTx) * 100, 1);
$pendingRate = round(($stats['pending_bookings'] / $totalTx) * 100, 1);

ob_start();
?>

<!-- HEADER VÀ NÚT TÁC VỤ -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Báo cáo & Thống kê hệ thống</h1>
        <p class="text-sm text-gray-500 mt-1">Dữ liệu doanh thu, vé bán và tỷ lệ giao dịch thời gian thực</p>
    </div>
    <a href="export_dashboard.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-md flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        Xuất báo cáo Excel
    </a>
</div>

<!-- ══ PHẦN 1: BÁO CÁO DOANH THU THEO KỲ HẠN ══ -->
<h2 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
    📅 Doanh thu theo chu kỳ thời gian
</h2>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Doanh thu Hôm nay -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:scale-[1.02] transition">
        <div>
            <p class="text-xs text-blue-100 font-semibold uppercase tracking-wider mb-1">Hôm nay</p>
            <p class="text-2xl font-black"><?= number_format($stats['revenue_today'], 0, ',', '.') ?>đ</p>
        </div>
        <div class="text-[10px] text-blue-100 mt-4 flex items-center justify-between">
            <span>Doanh thu ngày hôm nay</span>
            <span class="bg-blue-400/40 px-2 py-0.5 rounded-full font-bold">24H</span>
        </div>
    </div>

    <!-- Doanh thu Tuần này -->
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:scale-[1.02] transition">
        <div>
            <p class="text-xs text-indigo-100 font-semibold uppercase tracking-wider mb-1">Tuần này</p>
            <p class="text-2xl font-black"><?= number_format($stats['revenue_week'], 0, ',', '.') ?>đ</p>
        </div>
        <div class="text-[10px] text-indigo-100 mt-4 flex items-center justify-between">
            <span>Doanh thu tuần hiện tại</span>
            <span class="bg-indigo-400/40 px-2 py-0.5 rounded-full font-bold">7 Ngày</span>
        </div>
    </div>

    <!-- Doanh thu Tháng này -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:scale-[1.02] transition">
        <div>
            <p class="text-xs text-purple-100 font-semibold uppercase tracking-wider mb-1">Tháng này</p>
            <p class="text-2xl font-black"><?= number_format($stats['revenue_month'], 0, ',', '.') ?>đ</p>
        </div>
        <div class="text-[10px] text-purple-100 mt-4 flex items-center justify-between">
            <span>Tháng <?= date('m/Y') ?></span>
            <span class="bg-purple-400/40 px-2 py-0.5 rounded-full font-bold">Tháng</span>
        </div>
    </div>

    <!-- Doanh thu Năm nay -->
    <div class="bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-2xl shadow-sm p-5 flex flex-col justify-between hover:scale-[1.02] transition">
        <div>
            <p class="text-xs text-pink-100 font-semibold uppercase tracking-wider mb-1">Năm nay</p>
            <p class="text-2xl font-black"><?= number_format($stats['revenue_year'], 0, ',', '.') ?>đ</p>
        </div>
        <div class="text-[10px] text-pink-100 mt-4 flex items-center justify-between">
            <span>Năm <?= date('Y') ?></span>
            <span class="bg-pink-400/40 px-2 py-0.5 rounded-full font-bold">Năm</span>
        </div>
    </div>
</div>

<!-- ══ PHẦN 2: CHỈ SỐ VẬN HÀNH & HIỆU SUẤT GIAO DỊCH ══ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Cột trái: Tỷ lệ giao dịch & Thanh toán thành công/thất bại -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-gray-800 text-base mb-4">Tỷ lệ thanh toán & Giao dịch</h3>
            <div class="space-y-4">
                <!-- Thành công -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-600">Thành công (Đã nhận vé)</span>
                        <span class="font-bold text-green-600"><?= $stats['completed_bookings'] ?> (<?= $successRate ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-green-500 h-full rounded-full transition-all" style="width: <?= $successRate ?>%"></div>
                    </div>
                </div>

                <!-- Đang chờ duyệt -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-600">Đang chờ xác nhận / Đặt vé</span>
                        <span class="font-bold text-amber-600"><?= $stats['pending_bookings'] ?> (<?= $pendingRate ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-amber-400 h-full rounded-full transition-all" style="width: <?= $pendingRate ?>%"></div>
                    </div>
                </div>

                <!-- Đã hủy -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-600">Thất bại / Đã hủy</span>
                        <span class="font-bold text-red-500"><?= $stats['cancelled_bookings'] ?> (<?= $cancelledRate ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-red-500 h-full rounded-full transition-all" style="width: <?= $cancelledRate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 grid grid-cols-2 text-center gap-2">
            <div>
                <p class="text-xs text-gray-400">Tổng số giao dịch</p>
                <p class="text-lg font-bold text-gray-800"><?= number_format($stats['total_bookings']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Tỷ lệ đặt vé thành công</p>
                <p class="text-lg font-bold text-green-600"><?= $successRate ?>%</p>
            </div>
        </div>
    </div>

    <!-- Cột giữa: Thống kê số vé bán -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-gray-800 text-base mb-2">Thống kê số lượng vé bán</h3>
            <p class="text-xs text-gray-400 mb-4">Tổng số lượng vé điện tử đã in và check-in thành công</p>
            
            <div class="flex items-center gap-5 bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 text-white mb-4 shadow-inner">
                <div class="text-4xl">🎟</div>
                <div>
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Tổng số vé đã bán</p>
                    <p class="text-3xl font-black text-rose-500"><?= number_format($stats['total_tickets_sold']) ?> <span class="text-sm font-medium text-white">vé</span></p>
                </div>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex justify-between text-sm py-2 border-b border-gray-50">
                <span class="text-gray-500">Phim đang chiếu</span>
                <span class="font-bold text-gray-800"><?= $stats['active_movies'] ?></span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-50">
                <span class="text-gray-500">Tổng số rạp chiếu</span>
                <span class="font-bold text-gray-800"><?= $stats['total_cinemas'] ?></span>
            </div>
            <div class="flex justify-between text-sm py-2">
                <span class="text-gray-500">Tổng số lịch chiếu</span>
                <span class="font-bold text-gray-800"><?= $stats['total_showtimes'] ?></span>
            </div>
        </div>
    </div>

    <!-- Cột phải: Doanh thu theo Khung Giờ (Khung giờ vàng / sáng / tối) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 text-base mb-4">Doanh thu theo Khung giờ</h3>
        <div class="space-y-4">
            <?php if (empty($revenueByTime)): ?>
                <p class="text-center text-gray-400 py-10 text-sm">Chưa có dữ liệu theo khung giờ.</p>
            <?php else:
                $maxTimeRev = max(array_column($revenueByTime, 'total_revenue')) ?: 1;
                foreach ($revenueByTime as $t):
                    $pct = round(($t['total_revenue'] / $maxTimeRev) * 100);
            ?>
            <div>
                <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                    <span><?= htmlspecialchars($t['time_frame']) ?></span>
                    <span><?= number_format($t['total_revenue'], 0, ',', '.') ?>đ</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-indigo-500 h-full rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- ══ PHẦN 3: CHI TIẾT DOANH THU THEO PHIM & RẠP CHIẾU ══ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Doanh thu theo phim (Top phim bán chạy nhất) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-white flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-base">Top phim bán chạy nhất & số vé bán</h3>
            <span class="text-xs bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full font-bold">Xếp theo doanh thu</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Tên phim</th>
                        <th class="px-6 py-4 text-center">Số vé bán được</th>
                        <th class="px-6 py-4 text-right">Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                <?php if (empty($revenueByMovie)): ?>
                    <tr><td colspan="3" class="text-center py-12 text-gray-400">Không có dữ liệu phim nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($revenueByMovie as $row): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['movie_title']) ?></td>
                        <td class="px-6 py-4 text-center text-gray-600 font-bold"><?= number_format($row['tickets_sold']) ?> vé</td>
                        <td class="px-6 py-4 text-right text-emerald-600 font-extrabold"><?= number_format($row['total_revenue'], 0, ',', '.') ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Doanh thu theo Rạp chiếu -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-white flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-base">Doanh thu phân bổ theo Rạp</h3>
            <span class="text-xs bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full font-bold">Tổng doanh thu</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Tên rạp chiếu</th>
                        <th class="px-6 py-4 text-right">Doanh thu tích lũy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                <?php if (empty($revenueByCinema)): ?>
                    <tr><td colspan="2" class="text-center py-12 text-gray-400">Không có dữ liệu rạp chiếu nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($revenueByCinema as $row): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['cinema_name']) ?></td>
                        <td class="px-6 py-4 text-right text-indigo-600 font-extrabold"><?= number_format($row['total_revenue'], 0, ',', '.') ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>
