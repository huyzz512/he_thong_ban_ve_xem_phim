<?php
require_once '../../config/Database.php';
require_once '../../Models/ReportModel.php';

$db = (new Database())->getConnection();
$reportModel = new ReportModel($db);

// Lấy dữ liệu phong phú để xuất báo cáo
$stats = $reportModel->getDashboardStats();
$revenueByMovie = $reportModel->getRevenueAndTicketsByMovie();
$revenueByCinema = $reportModel->getRevenueByCinema();
$revenueByTime = $reportModel->getRevenueByTimeFrame();

// Tính toán tỷ lệ phần trăm
$totalTx = $stats['total_bookings'] ?: 1;
$successRate = round(($stats['completed_bookings'] / $totalTx) * 100, 1);
$cancelledRate = round(($stats['cancelled_bookings'] / $totalTx) * 100, 1);
$pendingRate = round(($stats['pending_bookings'] / $totalTx) * 100, 1);

// Thiết lập header tải file Excel (.xls) tương thích tốt
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=baocao_hethong_eaut_" . date('Y_m_d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Xuất BOM UTF-8 để Excel đọc tiếng Việt có dấu chuẩn xác
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        h1, h2 { color: #333333; }
        .table-title { font-size: 16px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; color: #1f2937; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; font-size: 13px; }
        th { background-color: #e11d48; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #d1d5db; text-align: left; }
        td { padding: 8px 10px; border: 1px solid #e5e7eb; text-align: left; }
        .highlight { background-color: #f9fafb; font-weight: bold; }
        .number-col { text-align: right; }
        .success-text { color: #16a34a; font-weight: bold; }
        .warning-text { color: #d97706; font-weight: bold; }
        .danger-text { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>EAUT Cinema - Báo Cáo Thống Kê Tổng Quan</h1>
    <p>Thời gian xuất báo cáo: <b><?= date('d/m/Y H:i:s') ?></b></p>
    
    <!-- ══ BẢNG 1: BÁO CÁO DOANH THU THEO KỲ HẠN ══ -->
    <div class="table-title">1. Báo cáo Doanh thu theo Kỳ hạn</div>
    <table>
        <thead>
            <tr>
                <th>Kỳ hạn báo cáo</th>
                <th class="number-col">Doanh thu (VNĐ)</th>
                <th>Mô tả ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="highlight">Hôm nay (24h qua)</td>
                <td class="number-col highlight"><?= number_format($stats['revenue_today'], 0, ',', '.') ?>đ</td>
                <td>Doanh thu phát sinh trong ngày hôm nay</td>
            </tr>
            <tr>
                <td>Tuần này (7 ngày qua)</td>
                <td class="number-col"><?= number_format($stats['revenue_week'], 0, ',', '.') ?>đ</td>
                <td>Doanh thu phát sinh trong tuần hiện tại</td>
            </tr>
            <tr>
                <td class="highlight">Tháng này</td>
                <td class="number-col highlight"><?= number_format($stats['revenue_month'], 0, ',', '.') ?>đ</td>
                <td>Doanh thu của tháng <?= date('m/Y') ?></td>
            </tr>
            <tr>
                <td>Năm nay</td>
                <td class="number-col"><?= number_format($stats['revenue_year'], 0, ',', '.') ?>đ</td>
                <td>Tổng doanh thu tích lũy cả năm <?= date('Y') ?></td>
            </tr>
            <tr class="highlight" style="background-color: #fef2f2;">
                <td>TỔNG DOANH THU TÍCH LŨY</td>
                <td class="number-col text-rose-600" style="font-size:15px; color:#e11d48;"><?= number_format($stats['total_revenue'], 0, ',', '.') ?>đ</td>
                <td>Tổng toàn bộ doanh thu từ trước đến nay</td>
            </tr>
        </tbody>
    </table>

    <!-- ══ BẢNG 2: CHỈ SỐ VẬN HÀNH & HIỆU SUẤT GIAO DỊCH ══ -->
    <div class="table-title">2. Hiệu suất giao dịch & Tỷ lệ thanh toán</div>
    <table>
        <thead>
            <tr>
                <th>Chỉ số vận hành</th>
                <th class="number-col">Số lượng / Giá trị</th>
                <th>Tỷ lệ phần trăm (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tổng số giao dịch đã tạo</td>
                <td class="number-col"><?= number_format($stats['total_bookings']) ?> đơn</td>
                <td>100%</td>
            </tr>
            <tr>
                <td class="success-text">Giao dịch thành công (Đã nhận vé)</td>
                <td class="number-col success-text"><?= number_format($stats['completed_bookings']) ?> đơn</td>
                <td class="success-text"><?= $successRate ?>%</td>
            </tr>
            <tr>
                <td class="warning-text">Đang chờ xác nhận / Đặt giữ ghế</td>
                <td class="number-col warning-text"><?= number_format($stats['pending_bookings']) ?> đơn</td>
                <td class="warning-text"><?= $pendingRate ?>%</td>
            </tr>
            <tr>
                <td class="danger-text">Giao dịch thất bại / Đã hủy</td>
                <td class="number-col danger-text"><?= number_format($stats['cancelled_bookings']) ?> đơn</td>
                <td class="danger-text"><?= $cancelledRate ?>%</td>
            </tr>
            <tr class="highlight">
                <td>Tỷ lệ đặt vé thành công</td>
                <td class="number-col success-text"><?= $successRate ?>%</td>
                <td>Độ chính xác cao</td>
            </tr>
            <tr class="highlight">
                <td>Tổng số vé đã bán ra</td>
                <td class="number-col" style="color: #e11d48;"><?= number_format($stats['total_tickets_sold']) ?> vé</td>
                <td>Vé đã hoàn tất thanh toán</td>
            </tr>
        </tbody>
    </table>

    <!-- ══ BẢNG 3: DOANH THU THEO PHIM (TOP BÁN CHẠY) ══ -->
    <div class="table-title">3. Doanh thu & Lượt vé bán theo từng Phim</div>
    <table>
        <thead>
            <tr>
                <th>Tên phim</th>
                <th class="number-col" style="text-align: center;">Số lượng vé bán</th>
                <th class="number-col">Doanh thu tích lũy (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($revenueByMovie)): ?>
            <tr><td colspan="3">Chưa có dữ liệu giao dịch.</td></tr>
            <?php else: foreach ($revenueByMovie as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['movie_title']) ?></td>
                <td class="number-col" style="text-align: center;"><?= number_format($row['tickets_sold']) ?> vé</td>
                <td class="number-col font-semibold"><?= number_format($row['total_revenue'], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- ══ BẢNG 4: DOANH THU THEO RẠP CHIẾU ══ -->
    <div class="table-title">4. Phân bổ Doanh thu theo Rạp chiếu</div>
    <table>
        <thead>
            <tr>
                <th>Tên rạp chiếu</th>
                <th class="number-col">Tổng doanh thu phát sinh (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($revenueByCinema)): ?>
            <tr><td colspan="2">Chưa có dữ liệu rạp chiếu.</td></tr>
            <?php else: foreach ($revenueByCinema as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['cinema_name']) ?></td>
                <td class="number-col font-semibold"><?= number_format($row['total_revenue'], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- ══ BẢNG 5: DOANH THU THEO KHUNG GIỜ CHIẾU ══ -->
    <div class="table-title">5. Thống kê Doanh thu theo Khung giờ vàng</div>
    <table>
        <thead>
            <tr>
                <th>Khung giờ chiếu</th>
                <th class="number-col" style="text-align: center;">Lượt đặt vé</th>
                <th class="number-col">Tổng doanh thu (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($revenueByTime)): ?>
            <tr><td colspan="3">Chưa có dữ liệu khung giờ.</td></tr>
            <?php else: foreach ($revenueByTime as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['time_frame']) ?></td>
                <td class="number-col" style="text-align: center;"><?= number_format($row['booking_count']) ?> đơn</td>
                <td class="number-col font-semibold"><?= number_format($row['total_revenue'], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
