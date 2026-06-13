<?php
$pageTitle = $pageTitle ?? 'Quản trị';
$activeAdminPage = $activeAdminPage ?? 'dashboard';
$admin = $admin ?? require_admin();
$flashMessage = $flashMessage ?? pull_flash();
$navItems = [
    'dashboard' => ['index.php', 'Tổng quan', '01'],
    'products' => ['products.php', 'Sản phẩm', '02'],
    'categories' => ['categories.php', 'Danh mục', '03'],
    'orders' => ['orders.php', 'Đơn hàng', '04'],
    'customers' => ['customers.php', 'Khách hàng', '05'],
];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle) ?> | Linh Florist Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <a class="admin-brand" href="index.php"><span>ℒ</span><div><strong>Linh Florist</strong><small>Administration</small></div></a>
      <nav class="admin-nav" aria-label="Quản trị">
        <?php foreach ($navItems as $key => [$href, $label, $number]): ?>
          <a class="<?= $activeAdminPage === $key ? 'active' : '' ?>" href="<?= h($href) ?>"><span><?= h($number) ?></span><?= h($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-sidebar-footer">
        <div class="admin-user"><span><?= h(mb_strtoupper(mb_substr($admin['full_name'], 0, 1))) ?></span><div><strong><?= h($admin['full_name']) ?></strong><small>Quản trị viên</small></div></div>
        <a href="../index.html">Xem cửa hàng</a>
        <form method="post" action="../logout.php"><?= csrf_field() ?><button type="submit">Đăng xuất</button></form>
      </div>
    </aside>
    <main class="admin-content">
      <header class="admin-mobile-header"><button type="button" data-admin-menu>Menu</button><strong>Linh Florist Admin</strong></header>
      <?php if ($flashMessage): ?><div class="admin-notice <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
