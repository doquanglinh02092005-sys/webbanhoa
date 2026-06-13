<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;
use Throwable;

final class Order extends Model
{
    public function recentForUser(int $userId, int $limit = 5): array
    {
        $statement = $this->db->prepare('SELECT id,order_number,total_amount,status,payment_method,payment_status,points_used,points_discount,points_earned,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT ?');
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createFromCart(int $userId, array $form, array $cart): array
    {
        $quantities = [];
        foreach ($cart as $item) {
            $productId = (int) ($item['id'] ?? 0);
            if ($productId > 0) {
                $quantities[$productId] = ($quantities[$productId] ?? 0) + 1;
            }
        }
        if (!$quantities) {
            throw new RuntimeException('Giỏ hoa không có sản phẩm hợp lệ.');
        }

        $paymentMethod = in_array($form['payment_method'] ?? '', ['cod', 'momo'], true)
            ? $form['payment_method']
            : 'cod';
        $productModel = new Product($this->db);
        $this->db->begin_transaction();
        try {
            $items = [];
            $subtotal = 0;
            foreach ($quantities as $productId => $quantity) {
                $product = $productModel->lockForOrder($productId);
                if (!$product) throw new RuntimeException('Một sản phẩm không còn được bán.');
                if ((int) $product['stock_quantity'] < $quantity) throw new RuntimeException('Sản phẩm "' . $product['name'] . '" không đủ tồn kho.');
                $lineTotal = (int) $product['price'] * $quantity;
                $subtotal += $lineTotal;
                $items[] = $product + ['quantity' => $quantity, 'line_total' => $lineTotal];
            }

            $shippingFee = $subtotal >= 800000 ? 0 : 40000;
            $requestedPoints = max(0, (int) ($form['points_to_use'] ?? 0));
            $redemptionRate = (int) shop_config()['loyalty_redemption_vnd_per_point'];
            $statement = $this->db->prepare('SELECT loyalty_points FROM users WHERE id=? FOR UPDATE');
            $statement->bind_param('i', $userId);
            $statement->execute();
            $availablePoints = (int) ($statement->get_result()->fetch_assoc()['loyalty_points'] ?? 0);
            if ($requestedPoints > $availablePoints) {
                throw new RuntimeException('Số điểm muốn dùng lớn hơn số điểm hiện có.');
            }
            $maximumPoints = intdiv($subtotal + $shippingFee, $redemptionRate);
            if ($requestedPoints > $maximumPoints) {
                throw new RuntimeException('Số điểm muốn dùng lớn hơn giá trị đơn hàng.');
            }
            $pointsUsed = $requestedPoints;
            $pointsDiscount = $pointsUsed * $redemptionRate;
            $total = max(0, $subtotal + $shippingFee - $pointsDiscount);
            $orderNumber = 'LF' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
            $deliveryDate = $form['delivery_date'] !== '' ? $form['delivery_date'] : null;
            $statement = $this->db->prepare('INSERT INTO orders (order_number,user_id,customer_name,customer_email,customer_phone,delivery_address,delivery_date,note,subtotal,shipping_fee,discount_amount,points_used,points_discount,total_amount,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $statement->bind_param('sissssssiiiiiis', $orderNumber, $userId, $form['customer_name'], $form['customer_email'], $form['customer_phone'], $form['delivery_address'], $deliveryDate, $form['note'], $subtotal, $shippingFee, $pointsDiscount, $pointsUsed, $pointsDiscount, $total, $paymentMethod);
            $statement->execute();
            $orderId = (int) $this->db->insert_id;

            if ($pointsUsed > 0) {
                $statement = $this->db->prepare('UPDATE users SET loyalty_points=loyalty_points-? WHERE id=?');
                $statement->bind_param('ii', $pointsUsed, $userId);
                $statement->execute();
                $description = 'Dùng điểm cho đơn ' . $orderNumber;
                $statement = $this->db->prepare("INSERT INTO loyalty_transactions (user_id,order_id,points,type,status,description) VALUES (?,?,?,'redeem','active',?)");
                $statement->bind_param('iiis', $userId, $orderId, $pointsUsed, $description);
                $statement->execute();
            }

            $itemStatement = $this->db->prepare('INSERT INTO order_items (order_id,product_id,product_name,sku,image_url,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?,?,?)');
            foreach ($items as $item) {
                $productId = (int) $item['id'];
                $unitPrice = (int) $item['price'];
                $quantity = (int) $item['quantity'];
                $lineTotal = (int) $item['line_total'];
                $itemStatement->bind_param('iisssiii', $orderId, $productId, $item['name'], $item['sku'], $item['image_url'], $unitPrice, $quantity, $lineTotal);
                $itemStatement->execute();
                $productModel->decreaseStock($productId, $quantity);
            }
            $this->db->commit();
            return [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'points_used' => $pointsUsed,
                'points_discount' => $pointsDiscount,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'items' => $items,
            ];
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function prepareMomoPayment(int $id, string $momoOrderId, string $requestId): void
    {
        $statement = $this->db->prepare('UPDATE orders SET momo_order_id=?,momo_request_id=? WHERE id=? AND payment_method="momo"');
        $statement->bind_param('ssi', $momoOrderId, $requestId, $id);
        $statement->execute();
    }

    public function applyMomoResult(array $payload, bool $cancelOnFailure): ?array
    {
        $momoOrderId = (string) ($payload['orderId'] ?? '');
        $amount = (int) ($payload['amount'] ?? 0);
        $resultCode = (int) ($payload['resultCode'] ?? -1);
        $transId = trim((string) ($payload['transId'] ?? ''));
        $requestId = trim((string) ($payload['requestId'] ?? ''));

        $this->db->begin_transaction();
        try {
            $statement = $this->db->prepare('SELECT * FROM orders WHERE momo_order_id=? FOR UPDATE');
            $statement->bind_param('s', $momoOrderId);
            $statement->execute();
            $order = $statement->get_result()->fetch_assoc() ?: null;
            if (!$order || (int) $order['total_amount'] !== $amount || $order['payment_method'] !== 'momo') {
                $this->db->rollback();
                return null;
            }

            if ($resultCode === 0) {
                $statement = $this->db->prepare("UPDATE orders SET payment_status='paid',paid_at=COALESCE(paid_at,NOW()),payment_reference=?,momo_trans_id=NULLIF(?,''),momo_result_code=0,momo_request_id=? WHERE id=?");
                $statement->bind_param('sssi', $transId, $transId, $requestId, $order['id']);
                $statement->execute();
                $order['payment_status'] = 'paid';
            } else {
                $statement = $this->db->prepare('UPDATE orders SET momo_result_code=?,momo_request_id=? WHERE id=?');
                $statement->bind_param('isi', $resultCode, $requestId, $order['id']);
                $statement->execute();
                if ($cancelOnFailure && $order['status'] === 'pending' && $order['payment_status'] === 'unpaid') {
                    $this->releaseStock((int) $order['id'], $order);
                    $this->db->query('UPDATE orders SET status="cancelled" WHERE id=' . (int) $order['id']);
                    $order['status'] = 'cancelled';
                    $this->refundRedeemedPoints($order);
                }
            }

            $this->syncLoyaltyPoints($order);
            $this->db->commit();
            return $order;
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function cancelUnpaidOrder(int $id): void
    {
        $this->db->begin_transaction();
        try {
            $statement = $this->db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $order = $statement->get_result()->fetch_assoc();
            if ($order && $order['payment_status'] === 'unpaid') {
                $this->releaseStock($id, $order);
                $this->db->query('UPDATE orders SET status="cancelled" WHERE id=' . $id);
                $this->refundRedeemedPoints($order);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function filtered(string $query, string $status): array
    {
        $where = ['1=1']; $types = ''; $values = [];
        if ($query !== '') { $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)'; $search = '%' . $query . '%'; $types .= 'sss'; array_push($values, $search, $search, $search); }
        if (in_array($status, ['pending','confirmed','preparing','shipping','completed','cancelled'], true)) { $where[] = 'status=?'; $types .= 's'; $values[] = $status; }
        $statement = $this->db->prepare('SELECT * FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
        if ($values) $statement->bind_param($types, ...$values);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM orders WHERE id=?');
        $statement->bind_param('i', $id);
        $statement->execute();
        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function items(int $id): array
    {
        $statement = $this->db->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');
        $statement->bind_param('i', $id);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, string $status, ?string $paymentStatus = null): void
    {
        $this->db->begin_transaction();
        try {
            $statement = $this->db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $statement->bind_param('i', $id);
            $statement->execute();
            $order = $statement->get_result()->fetch_assoc();
            if (!$order) throw new RuntimeException('Đơn hàng không tồn tại.');
            if ($order['status'] === 'cancelled' && $status !== 'cancelled') throw new RuntimeException('Không thể mở lại đơn đã hủy vì tồn kho đã được hoàn.');

            $newPaymentStatus = $paymentStatus
                ?? ($status === 'completed' && $order['payment_method'] === 'cod' ? 'paid' : $order['payment_status']);
            if ($status === 'cancelled') $this->releaseStock($id, $order);
            $statement = $this->db->prepare('UPDATE orders SET status=?,payment_status=?,paid_at=CASE WHEN ?="paid" THEN COALESCE(paid_at,NOW()) ELSE paid_at END WHERE id=?');
            $statement->bind_param('sssi', $status, $newPaymentStatus, $newPaymentStatus, $id);
            $statement->execute();
            $order['status'] = $status;
            $order['payment_status'] = $newPaymentStatus;
            if ($status === 'cancelled') $this->refundRedeemedPoints($order);
            $this->syncLoyaltyPoints($order);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function releaseStock(int $orderId, array $order): void
    {
        if (!empty($order['stock_released_at'])) return;
        $statement = $this->db->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL');
        $statement->bind_param('i', $orderId);
        $statement->execute();
        $result = $statement->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        $statement->close();

        $restore = $this->db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $productId = (int) $item['product_id'];
            $restore->bind_param('ii', $quantity, $productId);
            $restore->execute();
        }
        $restore->close();
        $this->db->query('UPDATE orders SET stock_released_at=NOW() WHERE id=' . $orderId);
    }

    private function syncLoyaltyPoints(array $order): void
    {
        $userId = (int) ($order['user_id'] ?? 0);
        if ($userId <= 0) return;
        $orderId = (int) $order['id'];
        $points = loyalty_points_for_amount((int) $order['subtotal']);
        $eligible = $order['status'] === 'completed' && $order['payment_status'] === 'paid' && $points > 0;

        $statement = $this->db->prepare("SELECT * FROM loyalty_transactions WHERE order_id=? AND type='earn' FOR UPDATE");
        $statement->bind_param('i', $orderId);
        $statement->execute();
        $transaction = $statement->get_result()->fetch_assoc() ?: null;

        if ($eligible && (!$transaction || $transaction['status'] === 'reversed')) {
            $statement = $this->db->prepare('UPDATE users SET loyalty_points=loyalty_points+? WHERE id=?');
            $statement->bind_param('ii', $points, $userId);
            $statement->execute();
            if ($transaction) {
                $statement = $this->db->prepare("UPDATE loyalty_transactions SET points=?,status='active',description=? WHERE order_id=? AND type='earn'");
                $description = 'Cộng điểm từ đơn ' . $order['order_number'];
                $statement->bind_param('isi', $points, $description, $orderId);
            } else {
                $statement = $this->db->prepare("INSERT INTO loyalty_transactions (user_id,order_id,points,type,status,description) VALUES (?,?,?,'earn','active',?)");
                $description = 'Cộng điểm từ đơn ' . $order['order_number'];
                $statement->bind_param('iiis', $userId, $orderId, $points, $description);
            }
            $statement->execute();
            $this->db->query('UPDATE orders SET points_earned=' . $points . ',points_awarded_at=NOW() WHERE id=' . $orderId);
        } elseif (!$eligible && $transaction && $transaction['status'] === 'active') {
            $oldPoints = (int) $transaction['points'];
            $statement = $this->db->prepare('UPDATE users SET loyalty_points=GREATEST(0,loyalty_points-?) WHERE id=?');
            $statement->bind_param('ii', $oldPoints, $userId);
            $statement->execute();
            $this->db->query("UPDATE loyalty_transactions SET status='reversed' WHERE order_id=" . $orderId . " AND type='earn'");
            $this->db->query('UPDATE orders SET points_earned=0,points_awarded_at=NULL WHERE id=' . $orderId);
        }
    }

    private function refundRedeemedPoints(array $order): void
    {
        $userId = (int) ($order['user_id'] ?? 0);
        $orderId = (int) ($order['id'] ?? 0);
        $points = (int) ($order['points_used'] ?? 0);
        if ($userId <= 0 || $orderId <= 0 || $points <= 0) return;

        $statement = $this->db->prepare("SELECT id FROM loyalty_transactions WHERE order_id=? AND type='refund' FOR UPDATE");
        $statement->bind_param('i', $orderId);
        $statement->execute();
        if ($statement->get_result()->fetch_assoc()) return;

        $statement = $this->db->prepare('UPDATE users SET loyalty_points=loyalty_points+? WHERE id=?');
        $statement->bind_param('ii', $points, $userId);
        $statement->execute();
        $this->db->query("UPDATE loyalty_transactions SET status='reversed' WHERE order_id={$orderId} AND type='redeem'");
        $description = 'Hoàn điểm do hủy đơn ' . $order['order_number'];
        $statement = $this->db->prepare("INSERT INTO loyalty_transactions (user_id,order_id,points,type,status,description) VALUES (?,?,?,'refund','active',?)");
        $statement->bind_param('iiis', $userId, $orderId, $points, $description);
        $statement->execute();
    }

    public function countAll(): int { return (int) $this->db->query('SELECT COUNT(*) total FROM orders')->fetch_assoc()['total']; }
    public function revenue(): int { return (int) $this->db->query("SELECT COALESCE(SUM(total_amount),0) total FROM orders WHERE status='completed'")->fetch_assoc()['total']; }
    public function recent(int $limit = 6): array { return $this->db->query('SELECT id,order_number,customer_name,total_amount,status,created_at FROM orders ORDER BY created_at DESC LIMIT ' . (int) $limit)->fetch_all(MYSQLI_ASSOC); }
}
