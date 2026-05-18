<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new UserModel($db);
    }

    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ email và mật khẩu.'];
        }

        $user = $this->userModel->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            return ['status' => 'success', 'message' => 'Đăng nhập thành công.'];
        }

        return ['status' => 'error', 'message' => 'Email hoặc mật khẩu không chính xác.'];
    }

    public function register($fullName, $email, $password, $confirmPassword) {
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            return ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin.'];
        }

        if ($password !== $confirmPassword) {
            return ['status' => 'error', 'message' => 'Mật khẩu xác nhận không khớp.'];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'];
        }

        $success = $this->userModel->createUser($fullName, $email, $password);
        if ($success) {
            // Tự động đăng nhập sau khi đăng ký
            $user = $this->userModel->findByEmail($email);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            return ['status' => 'success', 'message' => 'Đăng ký thành công.'];
        }

        return ['status' => 'error', 'message' => 'Email đã tồn tại hoặc có lỗi xảy ra.'];
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}
?>
