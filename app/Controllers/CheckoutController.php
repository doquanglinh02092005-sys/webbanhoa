<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;
use RuntimeException;
use Throwable;

final class CheckoutController extends Controller
{
    public function show(): void
    {
        $sessionUser = require_login();
        ensure_shop_schema(db());
        $profile = (new User())->find((int) $sessionUser['id']) ?? $sessionUser;
        $errors = [];
        $successOrder = trim((string) ($_GET['success'] ?? ''));
        $bankTransfer = shop_config()['bank_transfer'];
        $successOrderData = null;
        if ($successOrder !== '') {
            $statement = db()->prepare('SELECT order_number,total_amount,payment_method FROM orders WHERE order_number=? AND user_id=? LIMIT 1');
            $statement->bind_param('si', $successOrder, $sessionUser['id']);
            $statement->execute();
            $successOrderData = $statement->get_result()->fetch_assoc() ?: null;
        }
        $loyaltyVndPerPoint = (int) shop_config()['loyalty_vnd_per_point'];
        $loyaltyRedemptionRate = (int) shop_config()['loyalty_redemption_vnd_per_point'];
        $availablePoints = (int) ($profile['loyalty_points'] ?? 0);
        $form = [
            'customer_name' => trim((string) ($_POST['customer_name'] ?? $profile['full_name'] ?? '')),
            'customer_email' => trim((string) ($_POST['customer_email'] ?? $profile['email'] ?? '')),
            'customer_phone' => trim((string) ($_POST['customer_phone'] ?? $profile['phone'] ?? '')),
            'delivery_address' => trim((string) ($_POST['delivery_address'] ?? '')),
            'delivery_date' => trim((string) ($_POST['delivery_date'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
            'payment_method' => (string) ($_POST['payment_method'] ?? 'cod'),
            'points_to_use' => max(0, (int) ($_POST['points_to_use'] ?? 0)),
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $cart = json_decode((string) ($_POST['cart_json'] ?? ''), true);
            if (!is_array($cart) || !$cart) $errors[] = 'Giỏ hoa đang trống.';
            if (mb_strlen($form['customer_name']) < 2) $errors[] = 'Vui lòng nhập tên người nhận.';
            if (!preg_match('/^[0-9+().\s-]{8,20}$/', $form['customer_phone'])) $errors[] = 'Số điện thoại không hợp lệ.';
            if (mb_strlen($form['delivery_address']) < 10) $errors[] = 'Địa chỉ giao hoa cần cụ thể hơn.';
            if ($form['customer_email'] !== '' && !filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
            if ($form['delivery_date'] !== '' && $form['delivery_date'] < date('Y-m-d')) $errors[] = 'Ngày giao không thể ở trong quá khứ.';
            if (!in_array($form['payment_method'], ['cod', 'bank_transfer'], true)) $errors[] = 'Phương thức thanh toán không hợp lệ.';
            if (!$errors) {
                try {
                    $orders = new Order();
                    $order = $orders->createFromCart((int) $sessionUser['id'], $form, $cart);
                    redirect('checkout.php?success=' . rawurlencode($order['order_number']));
                } catch (Throwable $exception) {
                    $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'Không thể tạo đơn hàng. Vui lòng thử lại.';
                }
            }
        }
        $this->view('checkout/show', compact('errors', 'successOrder', 'successOrderData', 'bankTransfer', 'form', 'loyaltyVndPerPoint', 'loyaltyRedemptionRate', 'availablePoints') + ['pageTitle' => 'Đặt hoa']);
    }
}
