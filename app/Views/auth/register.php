<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="auth-main auth-main-split">
  <section class="auth-intro"><p class="eyebrow">A little flower, a lot of love</p><h1>Tạo tài khoản<br>của riêng bạn.</h1><p>Lưu thông tin nhận hoa, theo dõi đơn hàng và nhận những ưu đãi dịu dàng từ Linh Florist.</p><div class="auth-petal petal-one"></div><div class="auth-petal petal-two"></div></section>
  <section class="auth-card"><div class="auth-card-heading"><p class="eyebrow">Thành viên mới</p><h2>Đăng ký tài khoản</h2><p>Chỉ mất một phút để bắt đầu.</p></div>
    <?php if ($errors): ?><div class="auth-notice error"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <form class="auth-form" method="post" novalidate><?= csrf_field() ?><label>Họ và tên<input type="text" name="full_name" value="<?= h($fullName) ?>" required maxlength="100" autocomplete="name"></label><div class="auth-form-row"><label>Email<input type="email" name="email" value="<?= h($email) ?>" required maxlength="190" autocomplete="email"></label><label>Số điện thoại<input type="tel" name="phone" value="<?= h($phone) ?>" maxlength="20" autocomplete="tel"></label></div><label>Mật khẩu<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Tối thiểu 8 ký tự.</small></label><label>Xác nhận mật khẩu<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label><button class="auth-submit" type="submit">Tạo tài khoản</button></form>
    <p class="auth-switch">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
  </section>
</main>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
