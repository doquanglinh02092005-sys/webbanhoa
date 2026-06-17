<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;
use RuntimeException;
use Throwable;

final class AccountController extends Controller
{
    public function show(): void
    {
        $sessionUser = require_login();
        try {
            ensure_shop_schema(db());
            $user = (new User())->find((int) $sessionUser['id']);
            if (!$user || $user['status'] !== 'active') { logout_user(); redirect('login.php'); }
            $recentOrders = (new Order())->recentForUser((int) $sessionUser['id']);
            $loyaltyHistory = (new User())->loyaltyHistory((int) $sessionUser['id']);
        } catch (Throwable $exception) {
            $user = $sessionUser + ['phone' => '', 'loyalty_points' => 0, 'created_at' => null, 'last_login_at' => null];
            $recentOrders = [];
            $loyaltyHistory = [];
        }
        $this->view('account/show', compact('user', 'recentOrders', 'loyaltyHistory') + ['pageTitle' => 'Tài khoản của tôi', 'flashMessage' => pull_flash()]);
    }

    public function order(): void
    {
        $sessionUser = require_login();
        ensure_shop_schema(db());
        $orders = new Order();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            exit('Đơn hàng không tồn tại.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? '');
            try {
                if ($action === 'cancel') {
                    $orders->cancelForCustomer($id, (int) $sessionUser['id']);
                    flash('success', 'Đơn hàng đã được hủy thành công.');
                } elseif ($action === 'update_delivery') {
                    $data = [
                        'customer_name' => trim((string) ($_POST['customer_name'] ?? '')),
                        'customer_phone' => trim((string) ($_POST['customer_phone'] ?? '')),
                        'delivery_address' => trim((string) ($_POST['delivery_address'] ?? '')),
                        'note' => trim((string) ($_POST['note'] ?? '')),
                    ];
                    if (mb_strlen($data['customer_name']) < 2) throw new RuntimeException('Họ tên người nhận không được để trống.');
                    if (!preg_match('/^[0-9+().\s-]{8,20}$/', $data['customer_phone'])) throw new RuntimeException('Số điện thoại không hợp lệ.');
                    if (mb_strlen($data['delivery_address']) < 10) throw new RuntimeException('Địa chỉ nhận hàng cần cụ thể hơn.');
                    $orders->updateCustomerDelivery($id, (int) $sessionUser['id'], $data);
                    flash('success', 'Đã cập nhật thông tin nhận hàng.');
                }
            } catch (Throwable $exception) {
                flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Không thể cập nhật đơn hàng.');
            }
            redirect('account-order.php?id=' . $id);
        }

        $order = $orders->findForUser($id, (int) $sessionUser['id']);
        if (!$order) {
            http_response_code(404);
            exit('Đơn hàng không tồn tại.');
        }
        $items = $orders->items($id);
        $canModify = $orders->canCustomerModify($order);
        $bankTransfer = shop_config()['bank_transfer'];
        $this->view('account/order', compact('order', 'items', 'canModify', 'bankTransfer') + ['pageTitle' => 'Chi tiết đơn hàng', 'flashMessage' => pull_flash()]);
    }
}
