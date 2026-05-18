<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../home.php');
    }
    exit();
}

require_once '../../config/Database.php';
require_once '../../Models/UserModel.php';
require_once '../../Controllers/AuthController.php';

$message = '';
$messageType = '';

// Thông báo khi bị redirect từ admin guard
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $message = 'Bạn cần đăng nhập bằng tài khoản Admin để truy cập trang này.';
    $messageType = 'error';
}

$redirectAfterLogin = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = (new Database())->getConnection();
    $authController = new AuthController($db);
    
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectAfterLogin = $_POST['redirect'] ?? '';
    
    $result = $authController->login($email, $password);
    $message     = $result['message'];
    $messageType = $result['status'];
    
    if ($messageType === 'success') {
        // Admin -> Admin dashboard, others -> homepage or redirect param
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin/dashboard.php');
        } elseif ($redirectAfterLogin) {
            header('Location: ' . urldecode($redirectAfterLogin));
        } else {
            header('Location: ../home.php');
        }
        exit();
    }
}

ob_start();
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 w-full max-w-md">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Đăng nhập</h2>
            <p class="text-gray-500 text-sm">Chào mừng bạn quay lại với EAUT Cinema</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $messageType === 'error' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'; ?> border px-4 py-3 rounded-lg mb-6 text-sm text-center font-medium">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                    </div>
                    <input type="email" name="email" id="email" required class="pl-10 w-full border border-gray-300 rounded-xl shadow-sm py-3 px-4 focus:ring-primary focus:border-primary transition" placeholder="you@example.com">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="password" id="password" required class="pl-10 w-full border border-gray-300 rounded-xl shadow-sm py-3 px-4 focus:ring-primary focus:border-primary transition" placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary">
                    <span class="ml-2 text-gray-600">Ghi nhớ đăng nhập</span>
                </label>
                <a href="#" class="font-medium text-primary hover:text-red-700 transition">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition duration-300 ease-in-out transform hover:-translate-y-0.5">
                Đăng nhập
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-gray-600">
            Chưa có tài khoản? 
            <a href="register.php" class="font-bold text-primary hover:text-red-700 transition">Đăng ký ngay</a>
        </p>
    </div>
</div>

<?php if ($redirectAfterLogin): ?>
<input type="hidden" id="redirect_val" value="<?php echo htmlspecialchars($redirectAfterLogin); ?>">
<script>
document.querySelector('form').insertAdjacentHTML('beforeend', '<input type="hidden" name="redirect" value="' + document.getElementById('redirect_val').value + '">');
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/client_layout.php';
?>
