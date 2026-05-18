<?php
require_once '../config/Database.php';
require_once '../Models/MovieModel.php';

$db         = (new Database())->getConnection();
$movieModel = new MovieModel($db);

$showingMovies  = $movieModel->getMoviesByStatus('showing');
$upcomingMovies = $movieModel->getMoviesByStatus('upcoming');

// Lấy 1 phim làm banner (phim đầu tiên đang chiếu)
$bannerMovie = !empty($showingMovies) ? reset($showingMovies) : null;

ob_start();
?>

<?php if ($bannerMovie): ?>
<section class="mb-12 relative rounded-2xl overflow-hidden shadow-xl bg-black aspect-[16/6]">
    <img src="<?php echo htmlspecialchars($bannerMovie['banner_url']); ?>" alt="Banner Phim" class="w-full h-full object-cover opacity-70">
    <div class="absolute bottom-0 left-0 p-8 md:p-12 w-full bg-gradient-to-t from-black to-transparent text-white">
        <span class="inline-block bg-primary text-xs font-bold px-3 py-1 rounded-full mb-3">HOT</span>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-3"><?php echo htmlspecialchars($bannerMovie['title']); ?></h1>
        <p class="text-gray-200 max-w-2xl text-sm md:text-base mb-6 hidden md:block line-clamp-2"><?php echo htmlspecialchars($bannerMovie['description']); ?></p>
        <div class="flex gap-4">
            <?php if (!empty($bannerMovie['trailer_url'])): ?>
            <a href="<?php echo htmlspecialchars($bannerMovie['trailer_url']); ?>" target="_blank" class="bg-primary hover:bg-red-700 text-white font-bold px-8 py-3 rounded-lg transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path></svg>
                Xem Trailer
            </a>
            <?php endif; ?>
            <a href="detail.php?id=<?php echo $bannerMovie['id']; ?>" class="bg-white hover:bg-gray-200 text-secondary font-bold px-8 py-3 rounded-lg transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Đặt Vé Ngay
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ====== PHIM ĐANG CHIẾU ====== -->
<section class="mb-12">
    <div class="flex items-center justify-between mb-6 border-l-4 border-primary pl-4">
        <h2 class="text-2xl font-bold text-secondary">PHIM ĐANG CHIẾU</h2>
        <a href="movies.php?status=showing" class="text-sm font-medium text-primary hover:underline flex items-center gap-1">
            Xem tất cả 
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
        <?php if (!empty($showingMovies)): ?>
            <?php foreach (array_slice($showingMovies, 0, 5) as $movie): ?>
            <div class="movie-card bg-white rounded-xl shadow-md overflow-hidden group border border-gray-100">
                <div class="relative aspect-[2/3] overflow-hidden bg-gray-200">
                    <span class="absolute top-2 left-2 z-10 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded shadow">ĐANG CHIẾU</span>
                    <img src="<?php echo htmlspecialchars($movie['banner_url'] ?: 'https://placehold.co/300x450/1F2937/ffffff?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 p-4">
                        <a href="booking.php?movie_id=<?php echo $movie['id']; ?>" class="w-full bg-primary hover:bg-red-700 text-white text-sm font-bold py-3 rounded-lg text-center shadow-lg">ĐẶT VÉ NHANH</a>
                    </div>
                </div>
                <div class="p-4">
                    <a href="detail.php?id=<?php echo $movie['id']; ?>" class="font-bold text-gray-900 mb-1 block hover:text-primary transition line-clamp-2 h-12"><?php echo htmlspecialchars($movie['title']); ?></a>
                    <p class="text-xs text-primary font-medium mb-2"><?php echo htmlspecialchars($movie['genre'] ?: ''); ?></p>
                    <div class="flex items-center justify-between text-xs border-t border-gray-100 pt-3">
                        <span class="text-gray-500"><?php echo htmlspecialchars($movie['duration_minutes']); ?> phút</span>
                        <span class="text-yellow-500 font-medium">⭐ 9.0</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center text-gray-500 py-12">Hiện chưa có phim nào đang chiếu.</div>
        <?php endif; ?>
    </div>
</section>

<!-- ====== PHIM SẮP CHIẾU ====== -->
<?php if (!empty($upcomingMovies)): ?>
<section class="mb-12">
    <div class="flex items-center justify-between mb-6 border-l-4 border-blue-500 pl-4">
        <h2 class="text-2xl font-bold text-secondary">PHIM SẮP CHIẾU</h2>
        <a href="movies.php?status=upcoming" class="text-sm font-medium text-blue-500 hover:underline flex items-center gap-1">
            Xem tất cả
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
        <?php foreach (array_slice($upcomingMovies, 0, 5) as $movie): ?>
        <div class="movie-card bg-white rounded-xl shadow-md overflow-hidden group border border-gray-100 opacity-90 hover:opacity-100 transition">
            <div class="relative aspect-[2/3] overflow-hidden bg-gray-200">
                <span class="absolute top-2 left-2 z-10 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded shadow">SẮP CHIẾU</span>
                <img src="<?php echo htmlspecialchars($movie['banner_url'] ?: 'https://placehold.co/300x450/1e3a5f/ffffff?text=Coming+Soon'); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 p-4">
                    <a href="detail.php?id=<?php echo $movie['id']; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 rounded-lg text-center shadow-lg">XEM CHI TIẾT</a>
                </div>
            </div>
            <div class="p-4 bg-gray-50">
                <a href="detail.php?id=<?php echo $movie['id']; ?>" class="font-bold text-gray-700 mb-1 block hover:text-blue-600 transition line-clamp-2 h-12"><?php echo htmlspecialchars($movie['title']); ?></a>
                <p class="text-xs text-blue-500 font-medium mb-2"><?php echo htmlspecialchars($movie['genre'] ?: ''); ?></p>
                <div class="flex items-center justify-between text-xs border-t border-gray-100 pt-3">
                    <span class="text-gray-500"><?php echo htmlspecialchars($movie['duration_minutes']); ?> phút</span>
                    <span class="text-gray-400 font-medium">Sắp ra mắt</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ====== BANNER TÌM KIẾM ====== -->
<section class="mb-8 bg-secondary rounded-2xl p-8 md:p-12 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 opacity-5" style="background: radial-gradient(circle at 30% 50%, #E11D48 0%, transparent 60%), radial-gradient(circle at 70% 50%, #3B82F6 0%, transparent 60%);"></div>
    <div class="relative z-10">
        <h2 class="text-2xl md:text-3xl font-extrabold mb-3">Tìm kiếm bộ phim bạn yêu thích</h2>
        <p class="text-gray-300 mb-6">Lọc theo thể loại, rạp chiếu và ngày chiếu dễ dàng</p>
        <a href="movies.php" class="inline-flex items-center gap-2 bg-primary hover:bg-red-700 text-white font-bold px-8 py-3.5 rounded-xl transition shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Khám phá tất cả phim
        </a>
    </div>
</section>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
