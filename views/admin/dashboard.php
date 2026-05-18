<?php
require_once '../../config/admin_guard.php';
require_once '../../config/Database.php';
require_once '../../Models/ReportModel.php';

$db = (new Database())->getConnection();
$reportModel = new ReportModel($db);

// Fetch real data from the database
$stats = $reportModel->getDashboardStats();
$revenueByMovie = $reportModel->getRevenueAndTicketsByMovie();

ob_start();
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tổng quan hệ thống</h1>
        <p class="text-sm text-gray-500 mt-1">Dữ liệu thời gian thực từ cơ sở dữ liệu</p>
    </div>
    <a href="export_dashboard.php" class="bg-green-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-green-700 transition shadow-sm flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        Xuất file Excel
    </a>
</div>

<!-- CSS Grid for Main Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card 1: Tổng doanh thu -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center hover:shadow-md transition">
        <div class="p-4 rounded-full bg-blue-50 text-blue-600 mr-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Tổng doanh thu</p>
            <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
        </div>
    </div>
    
    <!-- Card 2: Tổng lượt đặt vé -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center hover:shadow-md transition">
        <div class="p-4 rounded-full bg-purple-50 text-purple-600 mr-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Tổng lượt đặt vé</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_bookings']); ?></p>
        </div>
    </div>

    <!-- Card 3: Phim đang chiếu -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center hover:shadow-md transition">
        <div class="p-4 rounded-full bg-green-50 text-green-600 mr-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Phim đang chiếu</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_movies']); ?></p>
        </div>
    </div>

    <!-- Card 4: Tổng số lịch chiếu -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center hover:shadow-md transition">
        <div class="p-4 rounded-full bg-yellow-50 text-yellow-600 mr-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Tổng số lịch chiếu</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_showtimes']); ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Col: Revenue by Phim Table -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-white flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Doanh thu theo Phim</h2>
            <span class="text-xs font-medium bg-blue-100 text-blue-700 px-2 py-1 rounded">Dựa trên các đơn đặt vé thành công</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">Tên phim</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">Số vé đã bán</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($revenueByMovie) > 0): ?>
                        <?php foreach ($revenueByMovie as $row): ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($row['movie_title']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo number_format($row['tickets_sold']); ?></td>
                            <td class="px-6 py-4 text-sm text-green-600 font-semibold">$<?php echo number_format($row['total_revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">Chưa có dữ liệu doanh thu. Hãy chờ khách hàng đặt vé.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Col: System Overview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">Tổng quan Hạ tầng</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
                <span class="text-sm font-medium text-gray-600">Số lượng Rạp chiếu</span>
                <span class="text-lg font-bold text-gray-900"><?php echo number_format($stats['total_cinemas']); ?></span>
            </div>
            
            <div class="p-5 bg-blue-50 rounded-lg mt-6 border border-blue-100">
                <div class="flex items-center mb-3">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-sm font-bold text-blue-800">Dynamic Giá vé Active</h3>
                </div>
                <p class="text-xs text-blue-600 leading-relaxed">
                    The pricing matrix applies additional charges automatically based on the Showtime configuration (VIP seats: +20%, Lễidays: +15%, Giờ vàngen Hours: +10%) leading to dynamic revenue scaling in real-time.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/admin_layout.php';
?>







