<?php
require_once '../../config/Database.php';
require_once '../../Models/ReportModel.php';

$db = (new Database())->getConnection();
$reportModel = new ReportModel($db);

$stats = $reportModel->getDashboardStats();
$revenueByMovie = $reportModel->getRevenueAndTicketsByMovie();

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=dashboard_report_" . date('Y_m_d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output BOM so Excel recognizes UTF-8 properly
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2>Phim Ticket Booking System - Tổng quan hệ thống (Generated: <?php echo date('Y-m-d H:i:s'); ?>)</h2>
    <table border="1">
        <tr>
            <th style="background-color: #f2f2f2;">Metric</th>
            <th style="background-color: #f2f2f2;">Value</th>
        </tr>
        <tr>
            <td>Tổng doanh thu ($)</td>
            <td><?php echo number_format($stats['total_revenue'], 2, '.', ''); ?></td>
        </tr>
        <tr>
            <td>Tổng lượt đặt vé</td>
            <td><?php echo $stats['total_bookings']; ?></td>
        </tr>
        <tr>
            <td>Phim đang chiếu</td>
            <td><?php echo $stats['active_movies']; ?></td>
        </tr>
        <tr>
            <td>Tổng số lịch chiếu</td>
            <td><?php echo $stats['total_showtimes']; ?></td>
        </tr>
        <tr>
            <td>Tổng số Rạp chiếu</td>
            <td><?php echo $stats['total_cinemas']; ?></td>
        </tr>
    </table>

    <br><br>
    
    <h2>Doanh thu theo Phim</h2>
    <table border="1">
        <tr>
            <th style="background-color: #f2f2f2;">Tên phim</th>
            <th style="background-color: #f2f2f2;">Số vé đã bán</th>
            <th style="background-color: #f2f2f2;">Tổng doanh thu ($)</th>
        </tr>
        <?php foreach ($revenueByMovie as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['movie_title']); ?></td>
            <td><?php echo $row['tickets_sold']; ?></td>
            <td><?php echo number_format($row['total_revenue'], 2, '.', ''); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>






