<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT id, full_name, email, phone, password_hash, role, status, loyalty_points, created_at, last_login_at FROM users WHERE email = ? LIMIT 1');
        $statement->bind_param('s', $email);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, full_name, email, phone, role, status, loyalty_points, created_at, last_login_at FROM users WHERE id = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function createCustomer(string $fullName, string $email, string $phone, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $this->db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, NULLIF(?, ''), ?, 'customer')");
        $statement->bind_param('ssss', $fullName, $email, $phone, $hash);
        $statement->execute();
        return (int) $this->db->insert_id;
    }

    public function markLogin(int $id): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
    }

    public function allWithOrderStats(string $query = ''): array
    {
        $sql = 'SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(CASE WHEN o.status="completed" THEN o.total_amount ELSE 0 END),0) AS spent FROM users u LEFT JOIN orders o ON o.user_id=u.id';
        if ($query === '') {
            return $this->db->query($sql . ' GROUP BY u.id ORDER BY u.created_at DESC')->fetch_all(MYSQLI_ASSOC);
        }
        $search = '%' . $query . '%';
        $statement = $this->db->prepare($sql . ' WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? GROUP BY u.id ORDER BY u.created_at DESC');
        $statement->bind_param('sss', $search, $search, $search);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateRole(int $id, string $role): void
    {
        $statement = $this->db->prepare('UPDATE users SET role=? WHERE id=?');
        $statement->bind_param('si', $role, $id);
        $statement->execute();
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->db->prepare('UPDATE users SET status=? WHERE id=?');
        $statement->bind_param('si', $status, $id);
        $statement->execute();
    }

    public function loyaltyHistory(int $userId, int $limit = 10): array
    {
        $statement = $this->db->prepare('SELECT lt.points,lt.type,lt.status,lt.description,lt.created_at,o.order_number FROM loyalty_transactions lt JOIN orders o ON o.id=lt.order_id WHERE lt.user_id=? ORDER BY lt.created_at DESC,lt.id DESC LIMIT ?');
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countCustomers(): int { return (int)$this->db->query("SELECT COUNT(*) total FROM users WHERE role='customer'")->fetch_assoc()['total']; }
    public function recentCustomers(int $limit=5): array { return $this->db->query("SELECT id,full_name,email,created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT ".(int)$limit)->fetch_all(MYSQLI_ASSOC); }
}
