<?php
declare(strict_types=1);

return [
    // Phải là URL HTTPS công khai để MoMo gọi được IPN, không dùng localhost.
    'app_url' => 'https://your-public-domain.example',
    'loyalty_vnd_per_point' => 10000,
    'momo' => [
        'partner_code' => 'YOUR_MOMO_PARTNER_CODE',
        'access_key' => 'YOUR_MOMO_ACCESS_KEY',
        'secret_key' => 'YOUR_MOMO_SECRET_KEY',
        'endpoint' => 'https://test-payment.momo.vn',
        // Có thể bỏ trống để hệ thống tự tạo từ app_url.
        'redirect_url' => '',
        'ipn_url' => '',
    ],
];
