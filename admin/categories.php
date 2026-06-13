<?php
declare(strict_types=1);
if (!isset($renderAdminView)) {
    require __DIR__ . "/../app/bootstrap.php";
    (new App\Controllers\AdminController())->categories();
    return;
}
require __DIR__ . "/../includes/admin-header.php";
?>
<section class="admin-page-heading"><div><p class="eyebrow">Catalog structure</p><h1>Danh mục</h1><p>Sắp xếp sản phẩm theo nhóm hoa để khách hàng dễ tìm kiếm.</p></div></section>
<div class="admin-two-columns">
  <form class="admin-form-card" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $editId ?>"><input type="hidden" name="action" value="save"><div class="admin-form-grid">
    <div class="admin-field full"><label>Tên danh mục *</label><input name="name" value="<?= h($_POST['name'] ?? $editCategory['name'] ?? '') ?>" required></div>
    <div class="admin-field full"><label>Mô tả</label><textarea name="description"><?= h($_POST['description'] ?? $editCategory['description'] ?? '') ?></textarea></div>
    <div class="admin-field"><label>Thứ tự</label><input type="number" name="sort_order" value="<?= (int) ($_POST['sort_order'] ?? $editCategory['sort_order'] ?? 0) ?>"></div>
    <div class="admin-field"><label>Trạng thái</label><select name="status"><option value="active">Đang hiện</option><option value="hidden" <?= ($_POST['status'] ?? $editCategory['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Đang ẩn</option></select></div>
  </div><?php if ($errors): ?><div class="admin-form-errors" style="margin-top:18px"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?><div class="admin-form-actions"><button class="admin-primary" type="submit"><?= $editId ? 'Lưu thay đổi' : 'Thêm danh mục' ?></button><?php if ($editId): ?><a class="admin-secondary" href="categories.php">Hủy</a><?php endif; ?></div></form>
  <section class="admin-card"><header class="admin-card-header"><div><h2>Danh sách danh mục</h2><p><?= count($categories) ?> danh mục</p></div></header><div class="admin-table-wrap"><table class="admin-data-table"><thead><tr><th>Tên</th><th>Sản phẩm</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach ($categories as $category): ?><tr><td><strong><?= h($category['name']) ?></strong><small><?= h($category['slug']) ?></small></td><td><?= (int) $category['product_count'] ?></td><td><?= (int) $category['sort_order'] ?></td><td><span class="admin-status <?= h($category['status']) ?>"><?= $category['status'] === 'active' ? 'Đang hiện' : 'Đang ẩn' ?></span></td><td><div class="admin-actions"><a href="categories.php?edit=<?= (int) $category['id'] ?>">Sửa</a><form method="post" data-confirm="Xóa danh mục này?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="danger" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
