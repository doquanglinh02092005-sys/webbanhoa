<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\MomoPayment;
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
        $momoEnabled = (bool) momo_config()['enabled'];
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
            if (!in_array($form['payment_method'], ['cod', 'momo'], true)) $errors[] = 'Phương thức thanh toán không hợp lệ.';
            if ($form['payment_method'] === 'momo' && !$momoEnabled) $errors[] = 'Thanh toán MoMo chưa được cấu hình. Vui lòng chọn thanh toán khi nhận hàng.';
            if (!$errors) {
                try {
                    $orders = new Order();
                    $order = $orders->createFromCart((int) $sessionUser['id'], $form, $cart);
                    if ($form['payment_method'] === 'momo') {
                        $momoOrderId = $order['order_number'] . '-M-' . strtoupper(bin2hex(random_bytes(3)));
                        $requestId = 'REQ-' . strtoupper(bin2hex(random_bytes(8)));
                        $orders->prepareMomoPayment((int) $order['id'], $momoOrderId, $requestId);
                        try {
                            $response = (new MomoPayment())->create($order, $form, $momoOrderId, $requestId);
                            header('Location: ' . $response['payUrl']);
                            exit;
                        } catch (Throwable $exception) {
                            $orders->cancelUnpaidOrder((int) $order['id']);
                            throw $exception;
                        }
                    }
                    redirect('checkout.php?success=' . rawurlencode($order['order_number']));
                } catch (Throwable $exception) {
                    $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'Không thể tạo đơn hàng. Vui lòng thử lại.';
                }
            }
        }
        $this->view('checkout/show', compact('errors', 'successOrder', 'form', 'momoEnabled', 'loyaltyVndPerPoint', 'loyaltyRedemptionRate', 'availablePoints') + ['pageTitle' => 'Đặt hoa']);
    }
}
