<?php
/**
 * Guard file: Bảo vệ trang cho người dùng đăng nhập.
 * Nếu chưa đăng nhập → redirect về trang login.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: /H%E1%BB%87%20th%E1%BB%91ng%20b%C3%A1n%20v%C3%A9%20xem%20phim/views/auth/login.php?redirect=' . $currentUrl);
    exit();
}
?>
