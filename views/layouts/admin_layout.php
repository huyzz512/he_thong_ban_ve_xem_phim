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
        <div class="p-6 text-2xl font-bold border-b border-gray-800 tracking-wider">
            EAUT Cinema 
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="dashboard.php" class="block px-4 py-3 rounded text-gray-300 hover:bg-gray-800 hover:text-white transition">Tổng quan</a>
            <a href="cinemas.php" class="block px-4 py-3 rounded text-gray-300 hover:bg-gray-800 hover:text-white transition">Rạp chiếu</a>
            <a href="movies.php" class="block px-4 py-3 rounded text-gray-300 hover:bg-gray-800 hover:text-white transition">Phim</a>
            <a href="showtimes.php" class="block px-4 py-3 rounded text-gray-300 hover:bg-gray-800 hover:text-white transition">Lịch chiếu</a>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- Clean Top Navbar -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="text-xl font-semibold text-gray-800">Hệ thống Quản lý</h1>
            <div class="flex items-center space-x-5">
                <span class="text-sm font-medium text-gray-600 border-r border-gray-200 pr-5">Xin chào, Quản trị viên</span>
                <a href="../home.html" class="px-4 py-2 bg-green-50 text-green-600 rounded-md hover:bg-green-100 transition font-medium text-sm">Trang chủ</a>
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
