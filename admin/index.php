<?php
declare(strict_types=1);
if (!isset($renderAdminView)) {
    require __DIR__ . "/../app/bootstrap.php";
    (new App\Controllers\AdminController())->dashboard();
    return;
}
require __DIR__ . "/../includes/admin-header.php";
?>
<section class="admin-page-heading">
  <div><p class="eyebrow">Admin dashboard</p><h1>Tổng quan cửa hàng</h1><p>Xin chào <?= h($admin['full_name']) ?>. Đây là tình hình hoạt động hiện tại.</p></div>
  <a class="admin-primary" href="product-form.php">+ Thêm sản phẩm</a>
</section>

<section class="admin-stats">
  <article class="admin-stat"><span>Sản phẩm</span><strong><?= $stats['products'] ?></strong><small><?= $stats['low_stock'] ?> sản phẩm sắp hết</small></article>
  <article class="admin-stat"><span>Đơn hàng</span><strong><?= $stats['orders'] ?></strong><small>Tổng đơn trong hệ thống</small></article>
  <article class="admin-stat"><span>Khách hàng</span><strong><?= $stats['customers'] ?></strong><small>Tài khoản mua hàng</small></article>
  <article class="admin-stat"><span>Doanh thu</span><strong style="font-size:29px"><?= admin_money($stats['revenue']) ?></strong><small>Từ đơn hoàn thành</small></article>
</section>

<section class="admin-grid">
  <div>
    <article class="admin-card">
      <header class="admin-card-header"><div><h2>Đơn hàng gần đây</h2><p>Theo dõi và xử lý đơn mới</p></div><a href="orders.php">Xem tất cả</a></header>
      <?php if ($recentOrders): ?>
        <div class="admin-table-wrap"><table class="admin-data-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày tạo</th></tr></thead><tbody>
        <?php foreach ($recentOrders as $order): ?><tr><td><a href="order.php?id=<?= (int) $order['id'] ?>"><strong><?= h($order['order_number']) ?></strong></a></td><td><?= h($order['customer_name']) ?></td><td><?= admin_money($order['total_amount']) ?></td><td><span class="admin-status <?= h($order['status']) ?>"><?= h(admin_status_label($order['status'])) ?></span></td><td><?= admin_date($order['created_at'], true) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      <?php else: ?><div class="admin-empty">Chưa có đơn hàng. Khi khách đặt hoa, đơn mới sẽ xuất hiện tại đây.</div><?php endif; ?>
    </article>
  </div>
  <aside>
    <article class="admin-card">
      <header class="admin-card-header"><div><h2>Sắp hết hàng</h2><p>Tồn kho từ 5 sản phẩm trở xuống</p></div><a href="products.php?stock=low">Quản lý</a></header>
      <?php if ($lowStockProducts): ?><div class="admin-list"><?php foreach ($lowStockProducts as $product): ?><div class="admin-list-item"><div><strong><?= h($product['name']) ?></strong><small><?= h($product['sku']) ?></small></div><strong><?= (int) $product['stock_quantity'] ?> cành</strong></div><?php endforeach; ?></div><?php else: ?><div class="admin-empty">Tồn kho đang ổn định.</div><?php endif; ?>
    </article>
    <article class="admin-card">
      <header class="admin-card-header"><div><h2>Khách hàng mới</h2><p>Tài khoản đăng ký gần đây</p></div><a href="customers.php">Xem tất cả</a></header>
      <?php if ($recentCustomers): ?><div class="admin-list"><?php foreach ($recentCustomers as $customer): ?><div class="admin-list-item"><div><strong><?= h($customer['full_name']) ?></strong><small><?= h($customer['email']) ?></small></div><small><?= admin_date($customer['created_at']) ?></small></div><?php endforeach; ?></div><?php else: ?><div class="admin-empty">Chưa có khách hàng.</div><?php endif; ?>
    </article>
  </aside>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
