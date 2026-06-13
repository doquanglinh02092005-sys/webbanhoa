<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="auth-main">
  <section class="auth-card auth-message checkout-success" data-clear-cart="<?= $status === 'success' ? '1' : '0' ?>">
    <span class="auth-symbol"><?= $status === 'success' ? '✓' : '!' ?></span>
    <h1><?= $status === 'success' ? 'Thanh toán thành công' : 'Thanh toán chưa hoàn tất' ?></h1>
    <p><?= h($message) ?></p>
    <?php if ($orderNumber): ?><p>Mã đơn: <strong><?= h($orderNumber) ?></strong></p><?php endif; ?>
    <a class="auth-primary-link" href="account.php">Xem đơn hàng của tôi</a>
    <?php if ($status !== 'success'): ?><a class="auth-secondary-link" href="index.html#products">Quay lại cửa hàng</a><?php endif; ?>
  </section>
</main>
<script>if(document.querySelector('[data-clear-cart="1"]'))localStorage.removeItem('linh-florist-cart');</script>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
