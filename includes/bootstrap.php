<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/shop.php';
require_once __DIR__ . '/schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'linh-florist-sessions';
    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0700, true);
    }
    @chmod($sessionDirectory, 0700);
    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) {
        session_save_path($sessionDirectory);
    }
    session_name('linh_florist_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function refresh_current_user(): ?array
{
    $sessionUser = current_user();
    if (!$sessionUser) {
        return null;
    }

    $statement = db()->prepare('SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $statement->bind_param('i', $sessionUser['id']);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if (!$user || $user['status'] !== 'active') {
        logout_user();
        return null;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];

    return $_SESSION['user'];
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function safe_next_path(?string $path): ?string
{
    if (!$path || str_contains($path, "\n") || str_contains($path, "\r") || str_contains($path, '\\')) {
        return null;
    }

    $parts = parse_url($path);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || str_starts_with($path, '//')) {
        return null;
    }

    return $path;
}

function require_guest(): void
{
    $user = current_user();
    if ($user) {
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'account.php');
    }
}

function require_login(): array
{
    try {
        $user = refresh_current_user();
    } catch (Throwable $exception) {
        http_response_code(503);
        exit('Không thể kết nối database. Hãy kiểm tra MySQL trong XAMPP.');
    }
    if (!$user) {
        $next = rawurlencode($_SERVER['REQUEST_URI'] ?? 'account.php');
        redirect('login.php?next=' . $next);
    }

    return $user;
}

function require_admin(): array
{
    try {
        $user = refresh_current_user();
    } catch (Throwable $exception) {
        http_response_code(503);
        exit('Không thể kết nối database. Hãy kiểm tra MySQL trong XAMPP.');
    }
    if (!$user) {
        redirect('../login.php?next=admin/index.php');
    }

    if ($user['role'] !== 'admin') {
        http_response_code(403);
        $pageTitle = 'Không có quyền truy cập';
        $assetPrefix = '../';
        $homePrefix = '../';
        require __DIR__ . '/auth-header.php';
        echo '<main class="auth-main"><section class="auth-card auth-message"><span class="auth-symbol">!</span><h1>Không có quyền truy cập</h1><p>Trang này chỉ dành cho quản trị viên.</p><a class="auth-primary-link" href="../account.php">Về tài khoản</a></section></main>';
        require __DIR__ . '/auth-footer.php';
        exit;
    }

    ensure_shop_schema(db());

    return $user;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}
