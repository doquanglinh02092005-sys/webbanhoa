<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

final class SetupException extends RuntimeException
{
}

$pageTitle = 'Khởi tạo hệ thống';
$errors = [];
$installed = false;
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));

try {
    $connection = db();
    $result = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $installed = (int) $result->fetch_assoc()['total'] > 0;
} catch (Throwable $exception) {
    $installed = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!$errors) {
        try {
            $config = database_config();
            $databaseName = database_name();
            $server = database_server();
            $server->query("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $connection = new mysqli($config['host'], $config['user'], $config['pass'], $databaseName, $config['port']);
            $connection->set_charset('utf8mb4');

            $usersTable = $connection->query("SHOW TABLES LIKE 'users'");
            if ($usersTable->num_rows > 0) {
                $requiredColumns = ['id', 'full_name', 'email', 'phone', 'password_hash', 'role', 'status', 'last_login_at', 'created_at', 'updated_at'];
                $existingColumns = [];
                $columns = $connection->query('SHOW COLUMNS FROM users');
                while ($column = $columns->fetch_assoc()) {
                    $existingColumns[] = $column['Field'];
                }

                if (array_diff($requiredColumns, $existingColumns)) {
                    $userCount = (int) $connection->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
                    if ($userCount > 0) {
                        throw new SetupException('Bảng users cũ không tương thích và đang có dữ liệu.');
                    }
                    $connection->query('DROP TABLE users');
                }
            }

            $connection->query("CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                phone VARCHAR(20) NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
                status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
                loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_role (role),
                INDEX idx_users_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            ensure_shop_schema($connection);

            $adminCount = (int) $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
            if ($adminCount > 0) {
                $installed = true;
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $connection->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
                $statement->bind_param('sss', $fullName, $email, $hash);
                $statement->execute();
                $installed = true;
                flash('success', 'Khởi tạo thành công. Hãy đăng nhập bằng tài khoản admin vừa tạo.');
                redirect('login.php');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception instanceof SetupException
                ? $exception->getMessage()
                : 'Không thể kết nối MariaDB. Hãy bật MySQL trong XAMPP rồi thử lại.';
        }
    }
}

require __DIR__ . '/includes/auth-header.php';
?>
<main class="auth-main">
  <section class="auth-card setup-card">
    <div class="auth-card-heading">
      <p class="eyebrow">Thiết lập lần đầu</p>
      <h1>Khởi tạo Linh Florist</h1>
      <p>Tạo database và tài khoản quản trị đầu tiên cho website.</p>
    </div>

    <?php if ($installed): ?>
      <div class="auth-notice success">Hệ thống đã được khởi tạo và đã có tài khoản admin.</div>
      <a class="auth-primary-link" href="login.php">Đến trang đăng nhập</a>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="auth-notice error"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div>
      <?php endif; ?>
      <form class="auth-form" method="post" novalidate>
        <?= csrf_field() ?>
        <label>Họ tên quản trị viên<input type="text" name="full_name" value="<?= h($fullName) ?>" required maxlength="100" autocomplete="name"></label>
        <label>Email quản trị<input type="email" name="email" value="<?= h($email) ?>" required maxlength="190" autocomplete="email"></label>
        <label>Mật khẩu<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Tối thiểu 8 ký tự.</small></label>
        <label>Xác nhận mật khẩu<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
        <button class="auth-submit" type="submit">Tạo database và tài khoản admin</button>
      </form>
      <p class="setup-hint">Mặc định XAMPP: user <code>root</code>, mật khẩu trống, database <code>web_ban_hoa</code>.</p>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/includes/auth-footer.php'; ?>
