<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Product extends Model
{
    public function activeCatalog(): array
    {
        return $this->db->query("SELECT p.id,p.name,p.color,p.occasion,p.price,p.compare_price,p.badge,p.image_url,p.stock_quantity,p.short_description,p.description,c.name AS category FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND (c.status='active' OR c.id IS NULL) ORDER BY p.featured DESC,p.id ASC")->fetch_all(MYSQLI_ASSOC);
    }

    public function lockForOrder(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT id,name,sku,image_url,price,stock_quantity FROM products WHERE id=? AND status='active' FOR UPDATE");
        $statement->bind_param('i', $id);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function decreaseStock(int $id, int $quantity): void
    {
        $statement = $this->db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?');
        $statement->bind_param('ii', $quantity, $id);
        $statement->execute();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function filtered(string $query, string $status, int $categoryId, bool $lowStock): array
    {
        $where = ['1=1']; $types = ''; $values = [];
        if ($query !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $search = '%' . $query . '%'; $types .= 'ss'; $values[] = $search; $values[] = $search; }
        if (in_array($status, ['active','hidden','draft'], true)) { $where[] = 'p.status=?'; $types .= 's'; $values[] = $status; }
        if ($categoryId > 0) { $where[] = 'p.category_id=?'; $types .= 'i'; $values[] = $categoryId; }
        if ($lowStock) $where[] = 'p.stock_quantity<=5';
        $statement = $this->db->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.updated_at DESC');
        if ($values) $statement->bind_param($types, ...$values);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function save(array $data, int $id = 0): void
    {
        $categoryId = $data['category_id'] ?: null;
        $slug = admin_unique_slug($this->db, $data['name'], 'products', $id);
        if ($id > 0) {
            $statement = $this->db->prepare('UPDATE products SET category_id=?,name=?,slug=?,sku=?,color=?,occasion=?,short_description=?,description=?,price=?,compare_price=?,stock_quantity=?,image_url=?,badge=?,status=?,featured=? WHERE id=?');
            $statement->bind_param('isssssssiiisssii', $categoryId,$data['name'],$slug,$data['sku'],$data['color'],$data['occasion'],$data['short_description'],$data['description'],$data['price'],$data['compare_price'],$data['stock_quantity'],$data['image_url'],$data['badge'],$data['status'],$data['featured'],$id);
        } else {
            $statement = $this->db->prepare('INSERT INTO products (category_id,name,slug,sku,color,occasion,short_description,description,price,compare_price,stock_quantity,image_url,badge,status,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $statement->bind_param('isssssssiiisssi', $categoryId,$data['name'],$slug,$data['sku'],$data['color'],$data['occasion'],$data['short_description'],$data['description'],$data['price'],$data['compare_price'],$data['stock_quantity'],$data['image_url'],$data['badge'],$data['status'],$data['featured']);
        }
        $statement->execute();
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM products WHERE id=?');
        $statement->bind_param('i', $id);
        $statement->execute();
    }

    public function setStatus(int $id, string $status): void
    {
        $statement = $this->db->prepare('UPDATE products SET status=? WHERE id=?');
        $statement->bind_param('si', $status, $id);
        $statement->execute();
    }

    public function countManaged(): int { return (int) $this->db->query("SELECT COUNT(*) total FROM products WHERE status<>'draft'")->fetch_assoc()['total']; }
    public function countLowStock(): int { return (int) $this->db->query("SELECT COUNT(*) total FROM products WHERE stock_quantity<=5 AND status='active'")->fetch_assoc()['total']; }
    public function lowStock(int $limit = 6): array { return $this->db->query("SELECT id,name,sku,stock_quantity,image_url FROM products WHERE stock_quantity<=5 AND status='active' ORDER BY stock_quantity,updated_at DESC LIMIT " . (int) $limit)->fetch_all(MYSQLI_ASSOC); }
}
