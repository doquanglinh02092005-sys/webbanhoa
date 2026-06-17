<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="account-main page-shell">
  <?php if ($flashMessage): ?><div class="auth-notice <?= h($flashMessage['type']) ?> account-flash"><?= h($flashMessage['message']) ?></div><?php endif; ?>
  <section class="account-hero account-order-hero">
    <div>
      <p class="eyebrow">Order detail</p>
      <h1>Đơn <?= h($order['order_number']) ?></h1>
      <p>Đặt lúc <?= h(admin_date($order['created_at'], true)) ?> · <?= h(admin_status_label($order['payment_method'])) ?></p>
    </div>
    <a class="admin-secondary" href="account.php">← Tài khoản</a>
  </section>

  <section class="account-grid account-order-grid">
    <article class="account-panel account-order-summary">
      <div class="panel-heading"><div><p class="eyebrow">Trạng thái</p><h2>Thông tin đơn hàng</h2></div><span class="panel-icon">#</span></div>
      <dl class="profile-list">
        <div><dt>Mã đơn</dt><dd><?= h($order['order_number']) ?></dd></div>
        <div><dt>Trạng thái đơn</dt><dd><?= h(admin_status_label($order['status'])) ?></dd></div>
        <div><dt>Thanh toán</dt><dd><?= h(admin_status_label($order['payment_status'])) ?></dd></div>
        <div><dt>Phương thức</dt><dd><?= h(admin_status_label($order['payment_method'])) ?></dd></div>
      </dl>
      <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] !== 'paid'): ?>
        <div class="bank-transfer-box account-bank-box">
          <h2>Thông tin chuyển khoản</h2>
          <p>Vui lòng chuyển khoản đúng số tiền và ghi đúng mã đơn hàng trong nội dung.</p>
          <div class="bank-transfer-grid">
            <div><span>Ngân hàng</span><strong><?= h($bankTransfer['bank_name']) ?></strong></div>
            <div><span>Số tài khoản</span><strong><?= h($bankTransfer['account_number']) ?></strong><button type="button" data-copy-value="<?= h($bankTransfer['account_number']) ?>">Copy STK</button></div>
            <div><span>Chủ tài khoản</span><strong><?= h($bankTransfer['account_name']) ?></strong></div>
            <div><span>Số tiền</span><strong><?= admin_money($order['total_amount']) ?></strong></div>
            <div class="full"><span>Nội dung chuyển khoản</span><strong><?= h($order['order_number']) ?></strong><button type="button" data-copy-value="<?= h($order['order_number']) ?>">Copy nội dung</button></div>
          </div>
        </div>
      <?php endif; ?>
    </article>

    <article class="account-panel account-order-receiver">
      <div class="panel-heading"><div><p class="eyebrow">Người nhận</p><h2>Thông tin giao hoa</h2></div><span class="panel-icon">⌂</span></div>
      <dl class="profile-list">
        <div><dt>Họ tên</dt><dd><?= h($order['customer_name']) ?></dd></div>
        <div><dt>Số điện thoại</dt><dd><?= h($order['customer_phone']) ?></dd></div>
        <div><dt>Địa chỉ</dt><dd><?= h($order['delivery_address']) ?></dd></div>
        <div><dt>Ngày giao</dt><dd><?= $order['delivery_date'] ? h(admin_date($order['delivery_date'])) : 'Chưa hẹn' ?></dd></div>
        <div><dt>Ghi chú</dt><dd><?= nl2br(h($order['note'] ?: 'Không có')) ?></dd></div>
      </dl>
    </article>

    <article class="account-panel account-order-items">
      <div class="panel-heading"><div><p class="eyebrow">Sản phẩm</p><h2>Hoa trong đơn</h2></div><span class="panel-icon">♡</span></div>
      <div class="account-order-table">
        <?php foreach ($items as $item): ?>
          <?php $image = $item['image_url'] ?: 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=300&q=80'; ?>
          <div>
            <img src="<?= h($image) ?>" alt="<?= h($item['product_name']) ?>">
            <span><strong><?= h($item['product_name']) ?></strong><small><?= h($item['sku'] ?: 'Sản phẩm') ?></small></span>
            <span><?= (int) $item['quantity'] ?> × <?= admin_money($item['unit_price']) ?></span>
            <strong><?= admin_money($item['line_total']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="checkout-totals account-order-totals">
        <div><span>Tạm tính</span><strong><?= admin_money($order['subtotal']) ?></strong></div>
        <div><span>Phí giao</span><strong><?= admin_money($order['shipping_fee']) ?></strong></div>
        <div><span>Điểm đã dùng</span><strong><?= (int) $order['points_used'] ?> điểm</strong></div>
        <div><span>Giảm từ điểm</span><strong>-<?= admin_money($order['points_discount']) ?></strong></div>
        <div class="total"><span>Tổng thanh toán</span><strong><?= admin_money($order['total_amount']) ?></strong></div>
      </div>
    </article>

    <article class="account-panel account-order-edit">
      <div class="panel-heading"><div><p class="eyebrow">Cập nhật</p><h2>Chỉnh thông tin nhận hàng</h2></div><span class="panel-icon">✎</span></div>
      <?php if ($canModify): ?>
        <form class="auth-form" method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="update_delivery">
          <label>Họ tên người nhận<input name="customer_name" value="<?= h($order['customer_name']) ?>" required maxlength="120"></label>
          <label>Số điện thoại<input name="customer_phone" value="<?= h($order['customer_phone']) ?>" required maxlength="20"></label>
          <label>Địa chỉ nhận hàng<input name="delivery_address" value="<?= h($order['delivery_address']) ?>" required maxlength="500"></label>
          <label>Ghi chú<textarea name="note" rows="4"><?= h($order['note']) ?></textarea></label>
          <button class="auth-submit" type="submit">Cập nhật thông tin</button>
        </form>
        <form class="account-cancel-form" method="post" data-confirm-submit="Bạn chắc chắn muốn hủy đơn hàng này?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="cancel">
          <button class="account-danger-button" type="submit">Hủy đơn hàng</button>
        </form>
      <?php else: ?>
        <p class="account-locked-message">Đơn hàng đã được xử lý nên không thể chỉnh sửa thông tin nhận hàng.</p>
      <?php endif; ?>
    </article>
  </section>
</main>
<script src="account-order.js?v=20260617"></script>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
