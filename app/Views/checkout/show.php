<?php require dirname(__DIR__, 3) . '/includes/auth-header.php'; ?>
<main class="checkout-main page-shell" data-checkout-success="<?= h($successOrder) ?>" data-points-rate="<?= $loyaltyVndPerPoint ?>" data-redemption-rate="<?= $loyaltyRedemptionRate ?>" data-available-points="<?= $availablePoints ?>">
<?php if ($successOrder): ?>
  <section class="auth-card auth-message checkout-success">
    <span class="auth-symbol">✓</span><h1>Đặt hoa thành công</h1>
    <p>Mã đơn của bạn là <strong><?= h($successOrder) ?></strong>. Bạn sẽ được cộng điểm sau khi đơn hoàn thành và đã thanh toán.</p>
    <?php if (($successOrderData['payment_method'] ?? '') === 'bank_transfer'): ?>
      <div class="bank-transfer-box">
        <h2>Thông tin chuyển khoản</h2>
        <p>Vui lòng chuyển khoản đúng số tiền và ghi đúng nội dung để shop xác nhận đơn nhanh hơn.</p>
        <div class="bank-transfer-grid">
          <div><span>Ngân hàng</span><strong><?= h($bankTransfer['bank_name']) ?></strong></div>
          <div><span>Số tài khoản</span><strong data-copy-account><?= h($bankTransfer['account_number']) ?></strong><button type="button" data-copy-value="<?= h($bankTransfer['account_number']) ?>">Copy STK</button></div>
          <div><span>Chủ tài khoản</span><strong><?= h($bankTransfer['account_name']) ?></strong></div>
          <div><span>Số tiền</span><strong><?= admin_money($successOrderData['total_amount'] ?? 0) ?></strong></div>
          <div class="full"><span>Nội dung chuyển khoản</span><strong data-copy-order><?= h($successOrder) ?></strong><button type="button" data-copy-value="<?= h($successOrder) ?>">Copy nội dung</button></div>
        </div>
        <small>Đơn hàng sẽ được xử lý sau khi shop xác nhận thanh toán trong tài khoản ngân hàng.</small>
      </div>
    <?php endif; ?>
    <a class="auth-primary-link" href="account.php">Xem đơn hàng</a>
  </section>
<?php else: ?>
  <section class="checkout-heading"><p class="eyebrow">Complete your order</p><h1>Hoàn tất đơn hoa</h1><p>Kiểm tra giỏ hoa, chọn cách thanh toán và nhập địa chỉ người nhận.</p><div class="checkout-steps"><span class="done">01 Giỏ hoa</span><span class="active">02 Giao hàng</span><span>03 Xác nhận</span></div></section>
  <?php if ($errors): ?><div class="auth-notice error"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
  <form class="checkout-grid" method="post" data-checkout-form>
    <?= csrf_field() ?><input type="hidden" name="cart_json" data-cart-json>
    <section class="checkout-panel">
      <div class="checkout-panel-heading"><span>01</span><div><h2>Thông tin người nhận</h2><p>Điền chính xác để florist liên hệ và giao hoa đúng hẹn.</p></div></div>
      <div class="auth-form">
        <label>Họ và tên<input name="customer_name" value="<?= h($form['customer_name']) ?>" required maxlength="120"></label>
        <div class="auth-form-row"><label>Email<input type="email" name="customer_email" value="<?= h($form['customer_email']) ?>" maxlength="190"></label><label>Số điện thoại<input name="customer_phone" value="<?= h($form['customer_phone']) ?>" required maxlength="20"></label></div>
        <label>Địa chỉ giao hoa<input name="delivery_address" value="<?= h($form['delivery_address']) ?>" required maxlength="500"></label>
        <label>Ngày giao mong muốn<input type="date" name="delivery_date" value="<?= h($form['delivery_date']) ?>" min="<?= date('Y-m-d') ?>"></label>
        <label>Lời nhắn cho cửa hàng<textarea name="note" rows="4"><?= h($form['note']) ?></textarea></label>
      </div>
      <div class="payment-section">
        <div class="checkout-panel-heading"><span>02</span><div><h2>Phương thức thanh toán</h2><p>Chọn phương thức phù hợp với bạn.</p></div></div>
        <label class="payment-option"><input type="radio" name="payment_method" value="cod" <?= $form['payment_method'] === 'cod' ? 'checked' : '' ?>><span><strong>Thanh toán khi nhận hàng</strong><small>Trả tiền mặt khi cửa hàng giao hoa.</small></span></label>
        <label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer" <?= $form['payment_method'] === 'bank_transfer' ? 'checked' : '' ?>><span><strong>Chuyển khoản ngân hàng</strong><small>Sau khi đặt hàng, hệ thống sẽ hiển thị thông tin chuyển khoản. Vui lòng chuyển đúng số tiền và ghi đúng mã đơn hàng trong nội dung chuyển khoản.</small></span></label>
      </div>
    </section>
    <aside class="checkout-panel checkout-summary">
      <div class="checkout-panel-heading"><span>03</span><div><h2>Tóm tắt đơn hàng</h2><p>Giá và tồn kho sẽ được xác nhận khi đặt.</p></div></div><div data-checkout-items></div>
      <div class="checkout-rewards"><div><strong>Sử dụng điểm thưởng</strong><small>Bạn có <?= number_format($availablePoints, 0, ',', '.') ?> điểm · 1 điểm = <?= admin_money($loyaltyRedemptionRate) ?></small></div><div class="checkout-reward-input"><input type="number" name="points_to_use" min="0" max="<?= $availablePoints ?>" value="<?= (int) $form['points_to_use'] ?>" data-points-input><button type="button" data-use-max-points>Dùng tối đa</button></div><small data-points-message></small></div>
      <div class="checkout-totals"><div><span>Tạm tính</span><strong data-checkout-subtotal>0đ</strong></div><div><span>Phí giao dự kiến</span><strong data-checkout-shipping>0đ</strong></div><div data-points-discount-row hidden><span>Giảm bằng điểm</span><strong data-points-discount>0đ</strong></div><div class="total"><span>Tổng cộng</span><strong data-checkout-total>0đ</strong></div></div>
      <div class="checkout-points"><strong>Điểm dự kiến: <span data-checkout-points>0</span> điểm</strong><small>Cộng sau khi đơn hoàn thành và thanh toán.</small></div>
      <button class="auth-submit" type="submit" data-checkout-submit>Xác nhận đặt hoa</button>
      <p class="checkout-note">Miễn phí giao cho đơn từ 800.000đ. Tư vấn: 0981 028 774.</p>
    </aside>
  </form>
<?php endif; ?>
</main><script src="checkout.js?v=20260617-bank-transfer"></script>
<?php require dirname(__DIR__, 3) . '/includes/auth-footer.php'; ?>
