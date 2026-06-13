<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Services\MomoPayment;
use Throwable;

final class MomoController extends Controller
{
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
                $order = (new Order())->applyMomoResult($payload, false);
                if (!$order) {
                    http_response_code(404);
                    $message = 'Không tìm thấy đơn hàng tương ứng.';
                } elseif ((int) ($payload['resultCode'] ?? -1) === 0) {
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
            $order = (new Order())->applyMomoResult($payload, true);
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
