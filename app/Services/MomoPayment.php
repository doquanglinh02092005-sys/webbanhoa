<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class MomoPayment
{
    private array $config;

    public function __construct()
    {
        $this->config = momo_config();
        if (!$this->config['enabled']) {
            throw new RuntimeException('Thanh toán MoMo chưa được cấu hình.');
        }
    }

    public function create(array $order, array $form, string $momoOrderId, string $requestId): array
    {
        $extraData = base64_encode((string) json_encode(['orderNumber' => $order['order_number']], JSON_UNESCAPED_UNICODE));
        $requestType = 'captureWallet';
        $orderInfo = 'Thanh toán đơn hoa ' . $order['order_number'];
        $rawSignature = 'accessKey=' . $this->config['access_key']
            . '&amount=' . $order['total_amount']
            . '&extraData=' . $extraData
            . '&ipnUrl=' . $this->config['ipn_url']
            . '&orderId=' . $momoOrderId
            . '&orderInfo=' . $orderInfo
            . '&partnerCode=' . $this->config['partner_code']
            . '&redirectUrl=' . $this->config['redirect_url']
            . '&requestId=' . $requestId
            . '&requestType=' . $requestType;

        $payload = [
            'partnerCode' => $this->config['partner_code'],
            'requestType' => $requestType,
            'ipnUrl' => $this->config['ipn_url'],
            'redirectUrl' => $this->config['redirect_url'],
            'orderId' => $momoOrderId,
            'amount' => (int) $order['total_amount'],
            'orderInfo' => $orderInfo,
            'requestId' => $requestId,
            'extraData' => $extraData,
            'lang' => 'vi',
            'autoCapture' => true,
            'signature' => hash_hmac('sha256', $rawSignature, $this->config['secret_key']),
            'userInfo' => [
                'name' => $form['customer_name'],
                'phoneNumber' => $form['customer_phone'],
                'email' => $form['customer_email'],
            ],
        ];

        $curl = curl_init($this->config['endpoint'] . '/v2/gateway/api/create');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $responseBody = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($responseBody === false || $curlError !== '') {
            throw new RuntimeException('Không thể kết nối cổng thanh toán MoMo.');
        }

        $response = json_decode($responseBody, true);
        if ($httpCode < 200 || $httpCode >= 300 || !is_array($response)) {
            throw new RuntimeException('MoMo trả về phản hồi không hợp lệ.');
        }
        if ((int) ($response['resultCode'] ?? -1) !== 0 || empty($response['payUrl'])) {
            throw new RuntimeException((string) ($response['message'] ?? 'Không thể tạo giao dịch MoMo.'));
        }
        return $response;
    }

    public function verifyResult(array $payload): bool
    {
        $signature = (string) ($payload['signature'] ?? '');
        if ($signature === '' || (string) ($payload['partnerCode'] ?? '') !== $this->config['partner_code']) {
            return false;
        }
        $rawSignature = 'accessKey=' . $this->config['access_key']
            . '&amount=' . ($payload['amount'] ?? '')
            . '&extraData=' . ($payload['extraData'] ?? '')
            . '&message=' . ($payload['message'] ?? '')
            . '&orderId=' . ($payload['orderId'] ?? '')
            . '&orderInfo=' . ($payload['orderInfo'] ?? '')
            . '&orderType=' . ($payload['orderType'] ?? '')
            . '&partnerCode=' . ($payload['partnerCode'] ?? '')
            . '&payType=' . ($payload['payType'] ?? '')
            . '&requestId=' . ($payload['requestId'] ?? '')
            . '&responseTime=' . ($payload['responseTime'] ?? '')
            . '&resultCode=' . ($payload['resultCode'] ?? '')
            . '&transId=' . ($payload['transId'] ?? '');
        return hash_equals(hash_hmac('sha256', $rawSignature, $this->config['secret_key']), $signature);
    }
}
