<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

final class Category extends Model
{
    public function all(bool $activeOnly = false): array
    {
        $where = $activeOnly ? " WHERE c.status='active'" : '';
        return $this->db->query("SELECT c.*,COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.status='active'" . $where . ' GROUP BY c.id ORDER BY c.sort_order,c.name')->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM categories WHERE id=?');
        $statement->bind_param('i', $id); $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function save(array $data, int $id = 0): void
    {
        $slug = admin_unique_slug($this->db, $data['name'], 'categories', $id);
        if ($id > 0) { $statement=$this->db->prepare('UPDATE categories SET name=?,slug=?,description=?,status=?,sort_order=? WHERE id=?'); $statement->bind_param('ssssii',$data['name'],$slug,$data['description'],$data['status'],$data['sort_order'],$id); }
        else { $statement=$this->db->prepare('INSERT INTO categories (name,slug,description,status,sort_order) VALUES (?,?,?,?,?)'); $statement->bind_param('ssssi',$data['name'],$slug,$data['description'],$data['status'],$data['sort_order']); }
        $statement->execute();
    }

    public function delete(int $id): void
    {
        $statement=$this->db->prepare('SELECT COUNT(*) total FROM products WHERE category_id=?'); $statement->bind_param('i',$id); $statement->execute();
        if ((int) $statement->get_result()->fetch_assoc()['total'] > 0) throw new RuntimeException('Danh mục đang có sản phẩm, hãy chuyển sản phẩm trước khi xóa.');
        $statement=$this->db->prepare('DELETE FROM categories WHERE id=?'); $statement->bind_param('i',$id); $statement->execute();
    }
}
