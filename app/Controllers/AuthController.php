<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use mysqli_sql_exception;
use Throwable;

final class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function login(): void
    {
        require_guest();
        $errors = [];
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $next = safe_next_path((string) ($_GET['next'] ?? $_POST['next'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
            if ($_SESSION['login_attempts'] >= 5) {
                if (!isset($_SESSION['login_locked_until'])) $_SESSION['login_locked_until'] = time() + 300;
                if (time() < $_SESSION['login_locked_until']) {
                    $errors[] = 'Bạn đã thử đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 5 phút.';
                    $this->view('auth/login', compact('errors', 'email', 'next') + ['pageTitle' => 'Đăng nhập', 'flashMessage' => pull_flash()]);
                    return;
                }
                $_SESSION['login_attempts'] = 0;
                unset($_SESSION['login_locked_until']);
            }

            try {
                $user = $this->users->findByEmail($email);
                if (!$user || !password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
                    $_SESSION['login_attempts']++;
                    $errors[] = 'Email hoặc mật khẩu không đúng.';
                }
                elseif ($user['status'] !== 'active') $errors[] = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
                else {
                    $_SESSION['login_attempts'] = 0;
                    unset($_SESSION['login_locked_until']);
                    login_user($user);
                    $this->users->markLogin((int) $user['id']);
                    redirect($next ?: ($user['role'] === 'admin' ? 'admin/index.php' : 'account.php'));
                }
            } catch (Throwable $exception) {
                $errors[] = 'Không thể kết nối database. Hãy bật MySQL trong XAMPP.';
            }
        }
        $this->view('auth/login', compact('errors', 'email', 'next') + ['pageTitle' => 'Đăng nhập', 'flashMessage' => pull_flash()]);
    }

    public function register(): void
    {
        require_guest();
        $errors = [];
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
            if ($phone !== '' && !preg_match('/^[0-9+().\s-]{8,20}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ.';
            if (strlen($password) < 8) $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
            if ($password !== (string) ($_POST['password_confirm'] ?? '')) $errors[] = 'Mật khẩu xác nhận không khớp.';
            if (!$errors) {
                try {
                    $id = $this->users->createCustomer($fullName, $email, $phone, $password);
                    login_user(['id' => $id, 'full_name' => $fullName, 'email' => $email, 'role' => 'customer']);
                    flash('success', 'Đăng ký thành công. Chào mừng bạn đến với Linh Florist!');
                    redirect('account.php');
                } catch (mysqli_sql_exception $exception) {
                    $errors[] = $exception->getCode() === 1062 ? 'Email này đã được sử dụng.' : 'Không thể tạo tài khoản.';
                }
            }
        }
        $this->view('auth/register', compact('errors', 'fullName', 'email', 'phone') + ['pageTitle' => 'Đăng ký']);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
        verify_csrf();
        logout_user();
        redirect('login.php');
    }
}
