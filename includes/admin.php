<?php
declare(strict_types=1);

function admin_money(int|string|null $amount): string
{
    return number_format((int) $amount, 0, ',', '.') . 'đ';
}

function admin_date(?string $value, bool $withTime = false): string
{
    if (!$value) {
        return '-';
    }
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', strtotime($value));
}

function admin_status_label(string $status): string
{
    return [
        'active' => 'Đang bán',
        'hidden' => 'Đang ẩn',
        'draft' => 'Bản nháp',
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
        'cod' => 'Khi nhận hàng',
        'momo' => 'MoMo',
        'reversed' => 'Đã thu hồi',
        'disabled' => 'Đã khóa',
        'customer' => 'Khách hàng',
        'admin' => 'Quản trị viên',
    ][$status] ?? $status;
}

function admin_unique_slug(mysqli $connection, string $value, string $table, int $ignoreId = 0): string
{
    if (!in_array($table, ['categories', 'products'], true)) {
        throw new InvalidArgumentException('Bảng không hợp lệ.');
    }

    $base = shop_slug($value);
    $slug = $base;
    $suffix = 2;
    do {
        $statement = $connection->prepare("SELECT id FROM {$table} WHERE slug = ? AND id <> ? LIMIT 1");
        $statement->bind_param('si', $slug, $ignoreId);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc();
        if (!$exists) {
            return $slug;
        }
        $slug = $base . '-' . $suffix++;
    } while ($suffix < 1000);

    return $base . '-' . bin2hex(random_bytes(3));
}

function admin_upload_product_image(array $file, ?string $currentImage = null): ?string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return $currentImage;
    }

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'Ảnh vượt quá dung lượng upload cho phép của PHP.',
        UPLOAD_ERR_FORM_SIZE => 'Ảnh vượt quá dung lượng cho phép của biểu mẫu.',
        UPLOAD_ERR_PARTIAL => 'Ảnh chỉ được tải lên một phần. Vui lòng thử lại.',
        UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm để nhận ảnh.',
        UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi ảnh vào thư mục tạm.',
        UPLOAD_ERR_EXTENSION => 'PHP đã dừng quá trình tải ảnh.',
    ];
    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new RuntimeException($uploadErrors[$uploadError] ?? 'Không thể nhận file ảnh tải lên.');
    }
    if (($file['size'] ?? 0) <= 0) {
        throw new RuntimeException('File ảnh tải lên đang trống.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh vượt quá dung lượng tối đa 5MB.');
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File ảnh tải lên không hợp lệ.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Chỉ hỗ trợ ảnh JPG, PNG hoặc WEBP.');
    }

    $directory = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Thư mục uploads/products không có quyền ghi cho Apache.');
    }

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Không thể di chuyển ảnh vào thư mục uploads/products.');
    }

    return 'uploads/products/' . $filename;
}
