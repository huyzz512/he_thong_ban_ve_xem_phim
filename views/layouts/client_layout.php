<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$doc_root = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$views_dir = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
$relative = str_replace($doc_root, '', $views_dir);
$base_url = '/' . implode('/', array_map('rawurlencode', explode('/', trim($relative, '/'))));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAUT Cinema - Đặt Vé Xem Phim Nhanh Chóng</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>/assets/images/favicon.png">
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
            // $base_url already computed at top of file
            ?>
            <a href="<?php echo $base_url; ?>/home.php" class="flex items-center">
                <img src="<?php echo $base_url; ?>/assets/images/logo.png"
                     alt="EAUT Cinema"
                     class="h-14 w-auto object-contain"
                     style="mix-blend-mode: normal;"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <span class="hidden items-center gap-2">
                    <span class="text-3xl font-bold text-primary">EAUT</span>
                    <span class="text-3xl font-light text-white">Cinema</span>
                </span>
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
                    <!-- Avatar dropdown -->
                    <div class="relative" id="userMenu">
                        <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')" class="flex items-center gap-2 cursor-pointer group">
                            <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center font-extrabold text-white text-sm shadow">
                                <?php echo mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <span class="text-sm font-semibold text-white hidden lg:block"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="userDropdown" class="hidden absolute right-0 top-12 w-52 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 mb-1">
                                <p class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo ucfirst($_SESSION['user_role']); ?></p>
                            </div>
                            <a href="<?php echo $base_url; ?>/profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Hồ sơ cá nhân
                            </a>
                            <a href="<?php echo $base_url; ?>/profile.php?tab=bookings" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                Vé của tôi
                            </a>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-blue-600 hover:bg-blue-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Admin Panel
                            </a>
                            <?php endif; ?>
                            <div class="border-t border-gray-100 mt-1 pt-1">
                                <a href="<?php echo $base_url; ?>/auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Đăng xuất
                                </a>
                            </div>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('click', e => {
                        const menu = document.getElementById('userMenu');
                        const drop = document.getElementById('userDropdown');
                        if (menu && !menu.contains(e.target)) drop?.classList.add('hidden');
                    });
                    </script>
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
                <a href="<?php echo $base_url; ?>/home.php" class="flex items-center mb-4">
                    <img src="<?php echo $base_url; ?>/assets/images/logo.png"
                         alt="EAUT Cinema" class="h-10 w-auto object-contain"
                         style="filter: brightness(0) invert(1) sepia(1) saturate(5) hue-rotate(330deg);"
                         onerror="this.style.display='none'">
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
