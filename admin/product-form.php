<?php
declare(strict_types=1);
if (!isset($renderAdminView)) {
    require __DIR__ . "/../app/bootstrap.php";
    (new App\Controllers\AdminController())->productForm();
    return;
}
require __DIR__ . "/../includes/admin-header.php";
?>
<section class="admin-page-heading"><div><p class="eyebrow">Product editor</p><h1><?= $product ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm' ?></h1><p>Quản lý thông tin, giá, tồn kho và hình ảnh hiển thị ngoài cửa hàng.</p></div><a class="admin-secondary" href="products.php">← Danh sách</a></section>
<?php if ($errors): ?><div class="admin-form-errors"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form class="admin-form-card" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="admin-form-grid">
    <div class="admin-field full"><label>Tên sản phẩm *</label><input name="name" value="<?= h($data['name']) ?>" required maxlength="180"></div>
    <div class="admin-field"><label>Mã sản phẩm *</label><input name="sku" value="<?= h($data['sku']) ?>" required maxlength="60" placeholder="VD: LF-013"></div>
    <div class="admin-field"><label>Danh mục</label><select name="category_id"><option value="0">Chưa phân loại</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $data['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field"><label>Màu chủ đạo</label><input name="color" value="<?= h($data['color']) ?>" maxlength="60" placeholder="Hồng, trắng, pastel..."></div>
    <div class="admin-field"><label>Nhóm hiển thị trên menu</label><select name="occasion"><option value="">Chưa phân nhóm</option><?php foreach (['birthday'=>'Hoa sinh nhật','love'=>'Hoa tình yêu','congratulations'=>'Hoa chúc mừng','bouquet'=>'Bó hoa','basket'=>'Giỏ hoa','wedding'=>'Hoa cưới','seasonal'=>'Hoa theo mùa'] as $value=>$label): ?><option value="<?= $value ?>" <?= $data['occasion'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><small>Khuyến mãi được xác định tự động khi giá gốc lớn hơn giá bán.</small></div>
    <div class="admin-field"><label>Nhãn sản phẩm</label><input name="badge" value="<?= h($data['badge']) ?>" maxlength="80" placeholder="Mới, Bán chạy, -10%..."></div>
    <div class="admin-field"><label>Giá bán *</label><input type="number" name="price" min="0" step="1000" value="<?= (int) $data['price'] ?>" required></div>
    <div class="admin-field"><label>Giá gốc</label><input type="number" name="compare_price" min="0" step="1000" value="<?= (int) $data['compare_price'] ?>"></div>
    <div class="admin-field"><label>Số lượng tồn kho</label><input type="number" name="stock_quantity" min="0" value="<?= (int) $data['stock_quantity'] ?>"></div>
    <div class="admin-field"><label>Trạng thái</label><select name="status"><option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Đang bán</option><option value="hidden" <?= $data['status'] === 'hidden' ? 'selected' : '' ?>>Ẩn khỏi cửa hàng</option><option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Bản nháp</option></select></div>
    <div class="admin-field full"><label>Mô tả ngắn</label><input name="short_description" value="<?= h($data['short_description']) ?>" maxlength="255"></div>
    <div class="admin-field full"><label>Mô tả chi tiết</label><textarea name="description"><?= h($data['description']) ?></textarea></div>
    <div class="admin-field"><label>Tải ảnh lên</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG hoặc WEBP, tối đa 5MB.</small></div>
    <div class="admin-field"><label>Hoặc đường dẫn ảnh</label><input type="url" name="image_url" value="<?= h($data['image_url']) ?>" placeholder="https://..."><small>Nếu tải file mới, file sẽ được ưu tiên.</small></div>
    <?php if ($preview): ?><div class="admin-field full"><label>Ảnh hiện tại</label><img class="admin-image-preview" src="<?= h($preview) ?>" alt=""></div><?php endif; ?>
    <label class="admin-checkbox full"><input type="checkbox" name="featured" value="1" <?= $data['featured'] ? 'checked' : '' ?>> Đánh dấu là sản phẩm nổi bật</label>
  </div>
  <div class="admin-form-actions"><button class="admin-primary" type="submit">Lưu sản phẩm</button><a class="admin-secondary" href="products.php">Hủy</a></div>
</form>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
