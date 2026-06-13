<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use Throwable;

final class ProductApiController
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            ensure_shop_schema(db());
            $products = array_map(static fn(array $p): array => [
                'id' => (int) $p['id'], 'name' => $p['name'], 'category' => $p['category'] ?: 'Hoa tươi',
                'color' => $p['color'] ?: 'Khác', 'occasion' => trim(strtolower((string) ($p['occasion'] ?? ''))), 'price' => (int) $p['price'],
                'compare' => (int) $p['compare_price'], 'compare_price' => (int) $p['compare_price'],
                'badge' => $p['badge'] ?: 'Hoa tươi', 'image' => $p['image_url'] ?: 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=85',
                'stock' => (int) $p['stock_quantity'], 'shortDescription' => $p['short_description'] ?: '', 'description' => $p['description'] ?: '',
            ], (new Product())->activeCatalog());
            echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $exception) {
            http_response_code(503);
            echo json_encode(['error' => 'Không thể tải sản phẩm.'], JSON_UNESCAPED_UNICODE);
        }
    }
}
