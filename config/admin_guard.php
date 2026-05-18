<?php
/**
 * Guard file: Bảo vệ tất cả trang Admin.
 * Include file này ở ĐẦU mỗi trang admin.
 * Nếu chưa đăng nhập hoặc không phải admin → redirect về login.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Chưa đăng nhập hoặc không có quyền admin
    header('Location: /H%E1%BB%87%20th%E1%BB%91ng%20b%C3%A1n%20v%C3%A9%20xem%20phim/views/auth/login.php?error=unauthorized');
    exit();
}
?>
