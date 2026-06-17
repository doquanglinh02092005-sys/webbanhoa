<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Services\MomoPayment;
use Throwable;

final class MomoController extends Controller
{
    public function demo(): void
    {
        $user = require_login();
        $momoOrderId = trim((string) ($_GET['order'] ?? $_POST['order'] ?? ''));
        $requestId = trim((string) ($_GET['request'] ?? $_POST['request'] ?? ''));
        $orders = new Order();
        $error = '';

        if (!momo_config()['demo_enabled']) {
            http_response_code(404);
            $error = 'Phương thức thanh toán MoMo đang tắt.';
            $order = null;
        } else {
            $order = $orders->findMomoDemo($momoOrderId, $requestId, (int) $user['id']);
            if (!$order) {
                http_response_code(404);
                $error = 'Không tìm thấy đơn thanh toán MoMo tương ứng.';
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $order = $orders->confirmMomoDemo($momoOrderId, $requestId, (int) $user['id']);
                if (!$order) {
                    http_response_code(409);
                    $error = 'Đơn hàng đã bị hủy hoặc không thể xác nhận.';
                } else {
                    redirect('momo-demo.php?order=' . rawurlencode($momoOrderId) . '&request=' . rawurlencode($requestId) . '&success=1');
                }
            }
        }

        $success = $order && $order['payment_status'] === 'paid';
        $qrPayload = $order ? implode('|', [
            'MOMO-PAYMENT',
            'SHOP=LINH-FLORIST',
            'ORDER=' . $order['order_number'],
            'AMOUNT=' . (int) $order['total_amount'],
            'REQUEST=' . $requestId,
        ]) : '';
        $this->view('payment/momo-demo', compact('order', 'momoOrderId', 'requestId', 'success', 'error', 'qrPayload') + ['pageTitle' => 'Thanh toán MoMo']);
    }

    public function handleReturn(): void
    {
        $status = 'error';
        $message = 'Không thể xác minh kết quả thanh toán MoMo.';
        $orderNumber = '';
        try {
            $payload = $_GET;
            $momo = new MomoPayment();
            if (!$momo->verifyResult($payload)) {
                http_response_code(400);
                $message = 'Chữ ký phản hồi MoMo không hợp lệ.';
            } else {
                $order = (new Order())->applyMomoResult($payload);
                if (!$order) {
                    http_response_code(404);
                    $message = 'Không tìm thấy đơn hàng tương ứng.';
                } elseif ($order['payment_status'] === 'paid') {
                    $status = 'success';
                    $orderNumber = (string) $order['order_number'];
                    $message = 'Thanh toán MoMo thành công. Cửa hàng sẽ sớm xác nhận đơn hoa của bạn.';
                } else {
                    $orderNumber = (string) $order['order_number'];
                    $message = (string) ($payload['message'] ?? 'Giao dịch MoMo chưa thành công.');
                }
            }
        } catch (Throwable $exception) {
            http_response_code(503);
            $message = 'Không thể xử lý kết quả MoMo lúc này. Vui lòng kiểm tra lại trong tài khoản.';
        }
        $this->view('payment/momo-result', compact('status', 'message', 'orderNumber') + ['pageTitle' => 'Kết quả thanh toán']);
    }

    public function ipn(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $payload = json_decode((string) file_get_contents('php://input'), true);
            if (!is_array($payload) || !(new MomoPayment())->verifyResult($payload)) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid signature']);
                return;
            }
            $order = (new Order())->applyMomoResult($payload);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['message' => 'Order not found']);
                return;
            }
            http_response_code(204);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['message' => 'Unable to process payment']);
        }
    }
}
