<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Admin - CineAdmin</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in-up': 'fadeInUp 0.4s ease-out forwards',
                        'slide-in-right': 'slideInRight 0.4s ease-out forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(15px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(100%)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <!-- Fixed Dark Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-full shrink-0">
        <div class="p-6 border-b border-gray-800">
            <div class="text-2xl font-bold tracking-wider text-white">EAUT Cinema</div>
            <div class="text-xs text-gray-400 mt-1 font-medium uppercase tracking-widest">Admin Panel</div>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Tổng quan
            </a>
            <a href="cinemas.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Rạp chiếu
            </a>
            <a href="movies.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path></svg>
                Phim
            </a>
            <a href="showtimes.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Lịch chiếu
            </a>
            <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Người dùng
            </a>
        </nav>
        <!-- Nút về trang chủ ở cuối sidebar -->
        <div class="p-4 border-t border-gray-800">
            <a href="../home.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-green-800/40 hover:text-green-300 transition w-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Về Trang Chủ
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- Clean Top Navbar -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="text-xl font-semibold text-gray-800">Hệ thống Quản lý</h1>
            <div class="flex items-center space-x-4">
                <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
                <div class="flex items-center gap-3 border-r border-gray-200 pr-4">
                    <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-white text-sm font-bold">
                        <?php echo isset($_SESSION['user_name']) ? mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8') : 'A'; ?>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                        <p class="text-xs text-gray-400">Quản trị viên</p>
                    </div>
                </div>
                <a href="../home.php" class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Trang chủ
                </a>
                <a href="../auth/logout.php" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Đăng xuất
                </a>
            </div>
        </header>

        <!-- Dynamic Main Content (bg-gray-100) -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8 animate-fade-in-up">
            <?php echo $content ?? ''; ?>
        </main>
    </div>

    <!-- Global Toast Notification -->
    <?php if (!empty($message)): ?>
    <div id="toastNotification" class="fixed top-20 right-5 z-50 flex items-center w-full max-w-sm p-4 space-x-3 text-gray-800 bg-white rounded-xl shadow-2xl border-l-4 <?php echo (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'fail') !== false || strpos(strtolower($message), 'lỗi') !== false) ? 'border-red-500' : 'border-green-500'; ?> animate-slide-in-right">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 <?php echo (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'fail') !== false || strpos(strtolower($message), 'lỗi') !== false) ? 'text-red-500 bg-red-100' : 'text-green-500 bg-green-100'; ?> rounded-lg">
            <?php if (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'fail') !== false || strpos(strtolower($message), 'lỗi') !== false): ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <?php else: ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <?php endif; ?>
        </div>
        <div class="ml-3 text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
        <button type="button" onclick="document.getElementById('toastNotification').remove()" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 transition">
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
        </button>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toastNotification');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.5s ease';
                setTimeout(() => toast.remove(), 500);
            }
        }, 3500);
    </script>
    <?php endif; ?>

    <!-- Global Modal Scripts -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                modal.classList.remove('hidden');
                const inner = modal.firstElementChild;
                if(inner) {
                    inner.classList.remove('animate-fade-in-up');
                    void inner.offsetWidth; // trigger reflow
                    inner.classList.add('animate-fade-in-up');
                }
            }
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) modal.classList.add('hidden');
        }

        // Search/Filter table rows
        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            if (!table) return;
            const tbody = table.querySelector("tbody");
            if (!tbody) return;
            const rows = tbody.querySelectorAll("tr");

            rows.forEach(row => {
                // Skip rows that span multiple columns (usually 'No data found' rows)
                const cells = row.querySelectorAll("td");
                if (cells.length === 1 && cells[0].hasAttribute("colspan")) return;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }


        // Move all modals to body to prevent 'transform' or 'overflow' containing block issues
        document.addEventListener('DOMContentLoaded', () => {
            const modals = document.querySelectorAll('[id$="Modal"]');
            modals.forEach(modal => {
                document.body.appendChild(modal);
            });
        });
    </script>
</body>
</html>
