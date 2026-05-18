<?php
require_once '../config/Database.php';
require_once '../Models/BookingModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$db           = (new Database())->getConnection();
$bookingModel = new BookingModel($db);

$booking_id = (int)($_GET['id'] ?? 0);
$booking    = $bookingModel->getBookingById($booking_id);

// Chỉ cho xem booking của chính mình
if (!$booking || (int)$booking['user_id'] !== (int)$_SESSION['user_id']) {
    header('Location: home.php');
    exit();
}

$bookedSeats = $bookingModel->getBookingSeats($booking_id);

ob_start();
?>
<!-- Confetti nhỏ bằng CSS animation -->
<style>
@keyframes float { 0%{transform:translateY(0) rotate(0deg);opacity:1} 100%{transform:translateY(-120px) rotate(720deg);opacity:0} }
.confetti { position:fixed; width:10px; height:10px; border-radius:2px; animation:float 2s ease-out forwards; pointer-events:none; z-index:999; }
</style>
<script>
(function(){
    const colors = ['#E11D48','#F59E0B','#10B981','#3B82F6','#8B5CF6'];
    for (let i=0; i<40; i++) {
        setTimeout(()=>{
            const el=document.createElement('div');
            el.className='confetti';
            el.style.left=Math.random()*100+'vw';
            el.style.top='100vh';
            el.style.background=colors[Math.floor(Math.random()*colors.length)];
            el.style.animationDuration=(1.5+Math.random())+'s';
            document.body.appendChild(el);
            setTimeout(()=>el.remove(),3000);
        }, i*60);
    }
})();
</script>

<div class="max-w-2xl mx-auto py-8">
    <!-- Thành công -->
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Đặt vé thành công! 🎉</h1>
        <p class="text-gray-500">Mã đơn hàng: <span class="font-bold text-primary">#<?= str_pad($booking_id, 6, '0', STR_PAD_LEFT) ?></span></p>
    </div>

    <!-- Vé -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <!-- Banner phim -->
        <div class="relative h-36 bg-gray-900">
            <?php if ($booking['banner_url']): ?>
            <img src="<?= htmlspecialchars($booking['banner_url']) ?>" class="w-full h-full object-cover opacity-40" alt="">
            <?php endif; ?>
            <div class="absolute inset-0 flex items-center p-6">
                <div class="text-white">
                    <p class="text-xs text-gray-300 uppercase tracking-wider mb-1">Phim</p>
                    <h2 class="text-2xl font-extrabold"><?= htmlspecialchars($booking['movie_title']) ?></h2>
                </div>
            </div>
        </div>

        <!-- Đường cắt vé -->
        <div class="flex items-center -my-3 z-10 relative">
            <div class="w-6 h-6 rounded-full bg-gray-100 -ml-3 border border-gray-200"></div>
            <div class="flex-grow border-t-2 border-dashed border-gray-200 mx-2"></div>
            <div class="w-6 h-6 rounded-full bg-gray-100 -mr-3 border border-gray-200"></div>
        </div>

        <!-- Chi tiết -->
        <div class="p-6 grid grid-cols-2 gap-5 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wider mb-1">Rạp chiếu</p>
                <p class="font-bold text-gray-800"><?= htmlspecialchars($booking['cinema_name']) ?></p>
                <p class="text-gray-500 text-xs"><?= htmlspecialchars($booking['cinema_address']) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wider mb-1">Phòng chiếu</p>
                <p class="font-bold text-gray-800"><?= htmlspecialchars($booking['room_name']) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wider mb-1">Ngày chiếu</p>
                <p class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($booking['start_time'])) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wider mb-1">Giờ chiếu</p>
                <p class="font-bold text-gray-800"><?= date('H:i', strtotime($booking['start_time'])) ?> → <?= date('H:i', strtotime($booking['end_time'])) ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-400 text-xs uppercase tracking-wider mb-2">Ghế đã đặt</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($bookedSeats as $s): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold text-sm <?= $s['type']==='vip' ? 'bg-yellow-100 text-yellow-700 border border-yellow-300' : 'bg-gray-100 text-gray-700' ?>">
                        <?= $s['row_name'].$s['col_number'] ?>
                        <?php if ($s['type']==='vip'): ?><span class="text-xs font-normal text-yellow-500">VIP</span><?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Đường cắt vé -->
        <div class="flex items-center -my-3 z-10 relative">
            <div class="w-6 h-6 rounded-full bg-gray-100 -ml-3 border border-gray-200"></div>
            <div class="flex-grow border-t-2 border-dashed border-gray-200 mx-2"></div>
            <div class="w-6 h-6 rounded-full bg-gray-100 -mr-3 border border-gray-200"></div>
        </div>

        <div class="p-6 flex items-center justify-between bg-gray-50">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider">Tổng thanh toán</p>
                <p class="text-2xl font-extrabold text-primary"><?= number_format($booking['total_amount'], 0, ',', '.') ?>đ</p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 font-bold text-sm rounded-full">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    Đã xác nhận
                </span>
            </div>
        </div>
    </div>

    <div class="flex gap-4 mt-6">
        <a href="home.php" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 rounded-xl text-center transition">← Về trang chủ</a>
        <a href="movies.php" class="flex-1 bg-primary hover:bg-red-700 text-white font-bold py-3 rounded-xl text-center transition">Đặt vé phim khác</a>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/client_layout.php';
?>
