<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="auth-main auth-main-split">
  <section class="auth-intro"><p class="eyebrow">Welcome back</p><h1>Thật vui khi<br>gặp lại bạn.</h1><p>Đăng nhập để tiếp tục chọn hoa, quản lý thông tin và theo dõi những đơn hàng yêu thương.</p><div class="auth-petal petal-one"></div><div class="auth-petal petal-two"></div></section>
  <section class="auth-card"><div class="auth-card-heading"><p class="eyebrow">Tài khoản của bạn</p><h2>Đăng nhập</h2><p>Nhập thông tin đã đăng ký để tiếp tục.</p></div>
    <?php if ($flashMessage): ?><div class="auth-notice <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="auth-notice error"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <form class="auth-form" method="post" novalidate><?= csrf_field() ?><input type="hidden" name="next" value="<?= h($next) ?>"><label>Email<input type="email" name="email" value="<?= h($email) ?>" required autocomplete="email"></label><label>Mật khẩu<input type="password" name="password" required autocomplete="current-password"></label><button class="auth-submit" type="submit">Đăng nhập</button></form>
    <p class="auth-switch">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p><a class="setup-link" href="setup.php">Thiết lập hệ thống lần đầu</a>
  </section>
</main>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
