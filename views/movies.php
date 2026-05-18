<?php
require_once '../config/Database.php';
require_once '../Models/MovieModel.php';
require_once '../Models/CinemaModel.php';

$db         = (new Database())->getConnection();
$movieModel = new MovieModel($db);
$cinemaModel = new CinemaModel($db);

// ---------- Lấy các tham số lọc ----------
$search    = trim($_GET['search']   ?? '');
$status    = $_GET['status']        ?? '';  // showing | upcoming | ''
$genre     = $_GET['genre']         ?? '';
$cinema_id = (int)($_GET['cinema']  ?? 0);
$date      = $_GET['date']          ?? '';

// ---------- Lấy dữ liệu ----------
$movies  = $movieModel->searchMovies($search, $status, $genre, $cinema_id, $date);
$genres  = $movieModel->getAllGenres();
$cinemas = $cinemaModel->getAllCinemas();

// Đếm theo từng trạng thái để hiển thị badge
$allMovies      = $movieModel->getAllMovies();
$countAll       = count($allMovies);
$countShowing   = count(array_filter($allMovies, fn($m) => $m['status'] === 'showing'));
$countUpcoming  = count(array_filter($allMovies, fn($m) => $m['status'] === 'upcoming'));

ob_start();
?>

<!-- ===== HERO SEARCH BANNER ===== -->
<section class="relative -mx-4 px-4 py-16 mb-10 bg-secondary overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1600'); background-size: cover; background-position: center;"></div>
    <div class="relative z-10 max-w-3xl mx-auto text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-3">Tìm kiếm phim</h1>
        <p class="text-gray-300 text-lg mb-8">Khám phá hàng trăm bộ phim đang chiếu và sắp chiếu tại EAUT Cinema</p>

        <form action="movies.php" method="GET" class="flex gap-2 max-w-2xl mx-auto" id="searchForm">
            <!-- Giữ lại các filter khi submit search -->
            <?php if ($status):   ?><input type="hidden" name="status"  value="<?php echo htmlspecialchars($status); ?>"><?php endif; ?>
            <?php if ($genre):    ?><input type="hidden" name="genre"   value="<?php echo htmlspecialchars($genre); ?>"><?php endif; ?>
            <?php if ($cinema_id): ?><input type="hidden" name="cinema" value="<?php echo $cinema_id; ?>"><?php endif; ?>
            <?php if ($date):     ?><input type="hidden" name="date"    value="<?php echo htmlspecialchars($date); ?>"><?php endif; ?>

            <div class="relative flex-grow">
                <svg class="w-5 h-5 absolute left-4 top-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Nhập tên phim bạn muốn tìm..."
                    class="w-full pl-12 pr-4 py-3.5 rounded-xl text-gray-900 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary shadow-lg">
            </div>
            <button type="submit" class="bg-primary hover:bg-red-700 text-white font-bold px-8 py-3.5 rounded-xl transition shadow-lg flex-shrink-0">
                Tìm kiếm
            </button>
        </form>
    </div>
</section>

<!-- ===== NỘI DUNG CHÍNH ===== -->
<div class="flex flex-col lg:flex-row gap-8">

    <!-- SIDEBAR BỘ LỌC -->
    <aside class="lg:w-72 flex-shrink-0">
        <form action="movies.php" method="GET" id="filterForm">
            <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>

            <!-- TRẠNG THÁI -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L13 10.414V15a1 1 0 01-.553.894l-4 2A1 1 0 017 17v-6.586L3.293 6.707A1 1 0 013 6V4z"></path></svg>
                    Trạng thái
                </h3>
                <div class="space-y-2 text-sm">
                    <?php
                    $statuses = [
                        ''         => ['label' => 'Tất cả phim',    'count' => $countAll,      'color' => 'bg-gray-100 text-gray-600'],
                        'showing'  => ['label' => 'Đang chiếu',     'count' => $countShowing,  'color' => 'bg-green-100 text-green-700'],
                        'upcoming' => ['label' => 'Sắp chiếu',      'count' => $countUpcoming, 'color' => 'bg-blue-100 text-blue-700'],
                    ];
                    foreach ($statuses as $val => $info):
                    ?>
                    <label class="flex items-center justify-between p-2.5 rounded-lg cursor-pointer transition <?php echo $status === $val ? 'bg-primary/10 ring-1 ring-primary' : 'hover:bg-gray-50'; ?>">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="status" value="<?php echo $val; ?>" <?php echo $status === $val ? 'checked' : ''; ?> class="text-primary" onchange="document.getElementById('filterForm').submit()">
                            <span class="font-medium <?php echo $status === $val ? 'text-primary' : 'text-gray-700'; ?>"><?php echo $info['label']; ?></span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $info['color']; ?>"><?php echo $info['count']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- THỂ LOẠI -->
            <?php if (!empty($genres)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"></path></svg>
                    Thể loại
                </h3>
                <div class="space-y-1.5 text-sm">
                    <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-gray-50 transition <?php echo $genre === '' ? 'font-bold text-primary' : 'text-gray-700'; ?>">
                        <input type="radio" name="genre" value="" <?php echo $genre === '' ? 'checked' : ''; ?> class="text-primary" onchange="document.getElementById('filterForm').submit()">
                        Tất cả thể loại
                    </label>
                    <?php foreach ($genres as $g): ?>
                    <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-gray-50 transition <?php echo $genre === $g ? 'font-bold text-primary' : 'text-gray-700'; ?>">
                        <input type="radio" name="genre" value="<?php echo htmlspecialchars($g); ?>" <?php echo $genre === $g ? 'checked' : ''; ?> class="text-primary" onchange="document.getElementById('filterForm').submit()">
                        <?php echo htmlspecialchars($g); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- RẠP CHIẾU -->
            <?php if (!empty($cinemas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Rạp chiếu
                </h3>
                <select name="cinema" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-primary focus:border-primary" onchange="document.getElementById('filterForm').submit()">
                    <option value="0" <?php echo $cinema_id === 0 ? 'selected' : ''; ?>>-- Tất cả rạp --</option>
                    <?php foreach ($cinemas as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $cinema_id === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- NGÀY CHIẾU -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Ngày chiếu
                </h3>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>"
                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-primary focus:border-primary"
                    onchange="document.getElementById('filterForm').submit()">
                <?php if ($date): ?>
                <button type="button" onclick="document.querySelector('[name=date]').value=''; document.getElementById('filterForm').submit();"
                    class="mt-2 text-xs text-red-500 hover:text-red-700 w-full text-center">Xóa bộ lọc ngày</button>
                <?php endif; ?>
            </div>

            <!-- NÚT XÓA TẤT CẢ BỘ LỌC -->
            <?php if ($search || $status || $genre || $cinema_id || $date): ?>
            <a href="movies.php" class="block w-full text-center py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition">
                ✕ Xóa tất cả bộ lọc
            </a>
            <?php endif; ?>
        </form>
    </aside>

    <!-- DANH SÁCH PHIM -->
    <div class="flex-grow min-w-0">

        <!-- Thanh kết quả + Tab nhanh -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <?php if ($search): ?>
                        Kết quả tìm kiếm cho "<span class="text-primary"><?php echo htmlspecialchars($search); ?></span>"
                    <?php elseif ($status === 'showing'): ?>
                        Phim Đang Chiếu
                    <?php elseif ($status === 'upcoming'): ?>
                        Phim Sắp Chiếu
                    <?php else: ?>
                        Tất cả phim
                    <?php endif; ?>
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Tìm thấy <b><?php echo count($movies); ?></b> bộ phim</p>
            </div>

            <!-- Tab nhanh trạng thái -->
            <div class="flex gap-2 text-sm flex-shrink-0">
                <a href="movies.php?<?php echo http_build_query(['search'=>$search,'genre'=>$genre,'cinema'=>$cinema_id,'date'=>$date,'status'=>'']); ?>"
                   class="px-4 py-2 rounded-full font-medium transition <?php echo $status === '' ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                   Tất cả
                </a>
                <a href="movies.php?<?php echo http_build_query(['search'=>$search,'genre'=>$genre,'cinema'=>$cinema_id,'date'=>$date,'status'=>'showing']); ?>"
                   class="px-4 py-2 rounded-full font-medium transition <?php echo $status === 'showing' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                   Đang chiếu
                </a>
                <a href="movies.php?<?php echo http_build_query(['search'=>$search,'genre'=>$genre,'cinema'=>$cinema_id,'date'=>$date,'status'=>'upcoming']); ?>"
                   class="px-4 py-2 rounded-full font-medium transition <?php echo $status === 'upcoming' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                   Sắp chiếu
                </a>
            </div>
        </div>

        <!-- Grid phim -->
        <?php if (!empty($movies)): ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
            <?php foreach ($movies as $movie): ?>
            <div class="movie-card bg-white rounded-xl shadow-md overflow-hidden group border border-gray-100 flex flex-col">
                <div class="relative aspect-[2/3] overflow-hidden bg-gray-200 flex-shrink-0">
                    <!-- Badge trạng thái -->
                    <?php if ($movie['status'] === 'showing'): ?>
                        <span class="absolute top-2 left-2 z-10 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded shadow">ĐANG CHIẾU</span>
                    <?php elseif ($movie['status'] === 'upcoming'): ?>
                        <span class="absolute top-2 left-2 z-10 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded shadow">SẮP CHIẾU</span>
                    <?php endif; ?>

                    <img src="<?php echo htmlspecialchars($movie['banner_url'] ?: 'https://placehold.co/300x450/1F2937/ffffff?text=No+Image'); ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         class="w-full h-full object-cover">

                    <!-- Overlay hover -->
                    <div class="absolute inset-0 bg-black/75 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 gap-3 p-4">
                        <?php if ($movie['status'] === 'showing'): ?>
                        <a href="booking.php?movie_id=<?php echo $movie['id']; ?>"
                           class="w-full bg-primary hover:bg-red-700 text-white text-sm font-bold py-2.5 rounded-lg text-center shadow-lg transition">
                           🎫 Đặt Vé Nhanh
                        </a>
                        <?php endif; ?>
                        <a href="detail.php?id=<?php echo $movie['id']; ?>"
                           class="w-full bg-white/20 hover:bg-white/30 text-white text-sm font-bold py-2.5 rounded-lg text-center border border-white/40 transition">
                           ℹ️ Xem Chi Tiết
                        </a>
                    </div>
                </div>

                <div class="p-4 flex flex-col flex-grow">
                    <a href="detail.php?id=<?php echo $movie['id']; ?>" class="font-bold text-gray-900 mb-1 block hover:text-primary transition line-clamp-2 leading-tight">
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </a>
                    <?php if ($movie['genre']): ?>
                    <span class="text-xs text-primary font-medium mb-2"><?php echo htmlspecialchars($movie['genre']); ?></span>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-3">
                        <span>⏱ <?php echo htmlspecialchars($movie['duration_minutes']); ?> phút</span>
                        <span class="text-yellow-500 font-medium">⭐ 9.0</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Không có kết quả -->
        <div class="text-center py-24 bg-white rounded-2xl border border-dashed border-gray-200">
            <svg class="w-20 h-20 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path></svg>
            <h3 class="text-xl font-bold text-gray-400 mb-2">Không tìm thấy phim nào</h3>
            <p class="text-gray-400 text-sm mb-6">Thử điều chỉnh bộ lọc hoặc tìm kiếm với từ khóa khác.</p>
            <a href="movies.php" class="bg-primary hover:bg-red-700 text-white font-bold px-8 py-3 rounded-xl transition">Xem tất cả phim</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
