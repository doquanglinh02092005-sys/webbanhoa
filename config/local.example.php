<?php
declare(strict_types=1);

return [
    // Redirect có thể dùng localhost. IPN cần URL HTTPS công khai (ví dụ ngrok).
    'app_url' => 'http://localhost/Webbanhoa',
    'loyalty_vnd_per_point' => 10000,
    'momo' => [
        // Bật QR demo nội bộ, không gọi API và không trừ tiền thật.
        'demo_enabled' => true,
        'partner_code' => 'YOUR_MOMO_PARTNER_CODE',
        'access_key' => 'YOUR_MOMO_ACCESS_KEY',
        'secret_key' => 'YOUR_MOMO_SECRET_KEY',
        'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
        'redirect_url' => 'http://localhost/Webbanhoa/momo-return.php',
        // Thay bằng URL public khi muốn MoMo sandbox gọi IPN thật.
        'ipn_url' => 'https://your-ngrok-domain.example/api/momo-ipn.php',
    ],
];
