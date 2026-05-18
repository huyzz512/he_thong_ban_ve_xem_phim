<?php
require_once '../config/Database.php';
require_once '../Models/MovieModel.php';

$db = (new Database())->getConnection();
$movieModel = new MovieModel($db);

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movie = $movieModel->getMovieById($movieId);

if (!$movie) {
    header('Location: home.php');
    exit();
}

function getYoutubeEmbedUrl($url) {
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
    $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    if (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    return isset($youtube_id) ? 'https://www.youtube.com/embed/' . $youtube_id : $url;
}

ob_start();
?>

<!-- Banner & Thông tin chính -->
<div class="relative bg-black h-[50vh] md:h-[70vh] flex items-center">
    <?php if ($movie['banner_url']): ?>
        <img src="<?php echo htmlspecialchars($movie['banner_url']); ?>" alt="Banner" class="absolute inset-0 w-full h-full object-cover opacity-40">
    <?php else: ?>
        <div class="absolute inset-0 w-full h-full bg-gray-900 opacity-80"></div>
    <?php endif; ?>
    
    <div class="absolute inset-0 bg-gradient-to-t from-gray-50 to-transparent"></div>

    <div class="container mx-auto px-4 relative z-10 flex flex-col md:flex-row items-end md:items-center gap-8 mt-20">
        <!-- Poster nhỏ (nếu có thể tách từ banner, ở đây dùng banner làm đại diện) -->
        <div class="hidden md:block w-48 lg:w-64 flex-shrink-0 shadow-2xl rounded-xl overflow-hidden border-4 border-white">
            <img src="<?php echo htmlspecialchars($movie['banner_url'] ? $movie['banner_url'] : 'https://via.placeholder.com/300x450'); ?>" alt="Poster" class="w-full h-auto object-cover aspect-[2/3]">
        </div>
        
        <div class="text-white flex-grow">
            <?php if ($movie['status'] === 'showing'): ?>
                <span class="inline-block bg-primary text-xs font-bold px-3 py-1 rounded-full mb-3 shadow-md">ĐANG CHIẾU</span>
            <?php else: ?>
                <span class="inline-block bg-gray-600 text-xs font-bold px-3 py-1 rounded-full mb-3 shadow-md">NGỪNG CHIẾU</span>
            <?php endif; ?>
            
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 drop-shadow-lg"><?php echo htmlspecialchars($movie['title']); ?></h1>
            
            <div class="flex items-center gap-4 text-sm md:text-base font-medium mb-6 drop-shadow-md">
                <span class="flex items-center gap-1"><svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg> 9.0 (IMDb)</span>
                <span>•</span>
                <span class="flex items-center gap-1"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <?php echo htmlspecialchars($movie['duration_minutes']); ?> phút</span>
            </div>

            <p class="text-gray-200 text-sm md:text-base max-w-3xl leading-relaxed mb-8 line-clamp-3 md:line-clamp-none drop-shadow-md">
                <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
            </p>

            <div class="flex flex-wrap gap-4">
                <a href="booking.php?movie_id=<?php echo $movie['id']; ?>" class="bg-primary hover:bg-red-700 text-white font-bold px-8 py-3.5 rounded-xl transition shadow-lg flex items-center gap-2 transform hover:-translate-y-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    Mua Vé Ngay
                </a>
                <?php if ($movie['trailer_url']): ?>
                <a href="#trailer" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold px-8 py-3.5 rounded-xl transition shadow-lg flex items-center gap-2 border border-white/30 transform hover:-translate-y-1">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path></svg>
                    Xem Trailer
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Cột bên trái: Lịch chiếu & Trailer -->
    <div class="lg:col-span-2 space-y-12">
        
        <?php if ($movie['trailer_url']): ?>
        <section id="trailer">
            <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-primary pl-4 mb-6">Trailer</h2>
            <div class="aspect-video rounded-2xl overflow-hidden shadow-xl border border-gray-200">
                <iframe class="w-full h-full" src="<?php echo getYoutubeEmbedUrl($movie['trailer_url']); ?>" title="Trailer" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </section>
        <?php endif; ?>

        <section>
            <div class="flex items-center justify-between border-l-4 border-primary pl-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Lịch chiếu</h2>
            </div>
            
            <!-- Placeholder layout cho lịch chiếu -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex gap-4 border-b border-gray-100 pb-4 mb-6 overflow-x-auto hide-scrollbar">
                    <button class="bg-primary text-white px-6 py-2 rounded-lg font-bold flex-shrink-0">Hôm nay</button>
                    <button class="bg-gray-100 text-gray-600 hover:bg-gray-200 px-6 py-2 rounded-lg font-medium transition flex-shrink-0">Ngày mai</button>
                </div>
                
                <div class="space-y-6">
                    <div class="border border-gray-100 rounded-xl p-5 hover:shadow-md transition">
                        <h3 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            EAUT Cinema - Hà Nội
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            <a href="booking.php?movie_id=<?php echo $movie['id']; ?>&time=18:00" class="border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-4 py-2 font-medium text-gray-700 transition">18:00</a>
                            <a href="booking.php?movie_id=<?php echo $movie['id']; ?>&time=20:00" class="border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-4 py-2 font-medium text-gray-700 transition">20:00</a>
                            <a href="booking.php?movie_id=<?php echo $movie['id']; ?>&time=22:30" class="border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-4 py-2 font-medium text-gray-700 transition">22:30</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Cột bên phải: Chi tiết thêm -->
    <div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-3">Thông tin chi tiết</h3>
            
            <div class="space-y-4 text-sm">
                <div class="flex justify-between border-b border-gray-50 pb-2">
                    <span class="text-gray-500 font-medium">Khởi chiếu:</span>
                    <span class="text-gray-900 font-bold">2026</span>
                </div>
                <div class="flex justify-between border-b border-gray-50 pb-2">
                    <span class="text-gray-500 font-medium">Thể loại:</span>
                    <span class="text-gray-900 font-bold">Hành động, Phiêu lưu</span>
                </div>
                <div class="flex justify-between border-b border-gray-50 pb-2">
                    <span class="text-gray-500 font-medium">Đạo diễn:</span>
                    <span class="text-gray-900 font-bold">Đang cập nhật</span>
                </div>
                <div class="flex justify-between border-b border-gray-50 pb-2">
                    <span class="text-gray-500 font-medium">Diễn viên:</span>
                    <span class="text-gray-900 font-bold text-right w-1/2 truncate">Đang cập nhật</span>
                </div>
                <div class="flex justify-between border-b border-gray-50 pb-2">
                    <span class="text-gray-500 font-medium">Ngôn ngữ:</span>
                    <span class="text-gray-900 font-bold">Tiếng Anh - Phụ đề Tiếng Việt</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
