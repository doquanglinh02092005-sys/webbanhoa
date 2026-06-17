<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="auth-main momo-demo-main">
  <section class="auth-card momo-demo-card" data-clear-cart="<?= $success ? '1' : '0' ?>">
    <?php if ($error): ?>
      <span class="auth-symbol">!</span>
      <h1>Không thể mở mã QR</h1>
      <p><?= h($error) ?></p>
      <a class="auth-primary-link" href="account.php">Xem đơn hàng</a>
    <?php elseif ($success): ?>
      <span class="auth-symbol">✓</span>
      <h1>Thanh toán thành công</h1>
      <p>Đơn <strong><?= h($order['order_number']) ?></strong> đã được đánh dấu thanh toán thành công.</p>
      <a class="auth-primary-link" href="account.php">Xem đơn hàng của tôi</a>
    <?php else: ?>
      <div class="momo-demo-heading">
        <div><p class="eyebrow">Thanh toán MoMo</p><h1>Quét mã để thanh toán</h1></div>
        <span class="momo-demo-logo">M</span>
      </div>
      <div class="momo-demo-qr-wrap"><div id="momo-demo-qr" data-qr-payload="<?= h($qrPayload) ?>"></div></div>
      <div class="momo-demo-order">
        <div><span>Mã đơn</span><strong><?= h($order['order_number']) ?></strong></div>
        <div><span>Số tiền</span><strong><?= admin_money($order['total_amount']) ?></strong></div>
      </div>
      <p class="momo-demo-note">Mở ứng dụng MoMo hoặc camera điện thoại để quét mã và hoàn tất thanh toán.</p>
      <form method="post" class="momo-demo-actions">
        <?= csrf_field() ?>
        <input type="hidden" name="order" value="<?= h($momoOrderId) ?>">
        <input type="hidden" name="request" value="<?= h($requestId) ?>">
        <button class="auth-submit" type="submit">Tôi đã thanh toán</button>
        <a class="auth-secondary-link" href="account.php">Thanh toán sau</a>
      </form>
    <?php endif; ?>
  </section>
</main>
<?php if (!$error && !$success): ?>
<script src="assets/vendor/qrcode.min.js"></script>
<script src="momo-demo.js"></script>
<?php elseif ($success): ?>
<script>localStorage.removeItem('linh-florist-cart');</script>
<?php endif; ?>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
