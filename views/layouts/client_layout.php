<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAUT Cinema - Đặt Vé Xem Phim Nhanh Chóng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#E11D48', // Màu hồng đỏ chủ đạo (giống màu logo CGV/Lotte)
                        secondary: '#1F2937', // Màu xám đen cho nền footer/header
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom CSS nhỏ để làm đẹp thanh cuộn */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #E11D48; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #be123c; }
        
        /* Hiệu ứng zoom nhẹ khi hover vào poster phim */
        .movie-card img { transition: transform 0.3s ease; }
        .movie-card:hover img { transform: scale(1.05); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

    <header class="bg-secondary text-white sticky top-0 z-50 shadow-md">
        <nav class="container mx-auto px-4 py-3 flex items-center justify-between">
            <?php
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            // Build a correctly URL-encoded base path from filesystem paths
            $doc_root = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
            $views_dir = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
            $relative = str_replace($doc_root, '', $views_dir); // e.g. /Hệ thống bán vé xem phim/views
            $base_url = implode('/', array_map('rawurlencode', explode('/', trim($relative, '/'))));
            $base_url = '/' . $base_url; // => /H%E1%BB%87%20th%E1%BB%91ng.../views
            ?>
            <a href="<?php echo $base_url; ?>/home.php" class="flex items-center gap-2">
                <span class="text-3xl font-bold text-primary">EAUT</span>
                <span class="text-3xl font-light text-white">Cinema</span>
            </a>

            <div class="hidden md:flex items-center gap-6 font-medium">
                <a href="<?php echo $base_url; ?>/movies.php?status=showing" class="hover:text-primary transition">Phim Đang Chiếu</a>
                <a href="<?php echo $base_url; ?>/movies.php?status=upcoming" class="hover:text-primary transition">Phim Sắp Chiếu</a>
                <a href="<?php echo $base_url; ?>/movies.php" class="hover:text-primary transition">Tra cứu phim</a>
                <a href="#" class="hover:text-primary transition">Tin Tức</a>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden sm:block">
                    <form action="<?php echo $base_url; ?>/movies.php" method="GET">
                        <input type="text" name="search" placeholder="Tìm tên phim..." class="bg-gray-700 text-sm rounded-full px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-primary text-white">
                        <button type="submit" class="absolute right-2 top-1.5 text-gray-400 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                    </form>
                </div>
                
                <?php /* $base_url already set above */ ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-sm font-medium text-gray-300">Xin chào, <b class="text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></b></span>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="text-sm font-medium text-blue-400 hover:text-blue-300 transition">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?php echo $base_url; ?>/auth/logout.php" class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2 rounded-full transition shadow-md">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/auth/login.php" class="text-sm font-medium hover:text-primary transition">Đăng nhập</a>
                    <a href="<?php echo $base_url; ?>/auth/register.php" class="bg-primary hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full transition shadow-md">Đăng ký</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        <?php echo $content; ?>
    </main>

    <footer class="bg-secondary text-gray-400 mt-12 border-t-4 border-primary">
        <div class="container mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <a href="<?php echo $base_url; ?>/home.php" class="flex items-center gap-2 mb-4">
                    <span class="text-2xl font-bold text-primary">EAUT</span>
                    <span class="text-2xl font-light text-white">Cinema</span>
                </a>
                <p class="text-sm">Hệ thống rạp chiếu phim hiện đại hàng đầu. Trải nghiệm điện ảnh đỉnh cao.</p>
            </div>
            <div class="text-sm">
                <h4 class="font-bold text-white mb-4">Chính Sách</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-primary">Điều khoản sử dụng</a></li>
                    <li><a href="#" class="hover:text-primary">Chính sách bảo mật</a></li>
                    <li><a href="#" class="hover:text-primary">Chính sách thanh toán</a></li>
                </ul>
            </div>
            <div class="text-sm">
                <h4 class="font-bold text-white mb-4">Liên Hệ</h4>
                <p>Hotline: 088 86 6363 2</p>
                <p>Email: nhathuy.661.61@gmail.com</p>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4 text-sm">Kết Nối</h4>
                <div class="flex gap-4">
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary cursor-pointer text-white">F</div>
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary cursor-pointer text-white">Y</div>
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary cursor-pointer text-white">I</div>
                </div>
            </div>
        </div>
        <div class="bg-black/30 py-4 text-center text-xs border-t border-gray-700">
            &copy; Dev by Phan Nhat Huy. All rights reserved.
        </div>
    </footer>

</body>
</html>
