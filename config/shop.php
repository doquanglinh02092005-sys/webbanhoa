<?php
declare(strict_types=1);

function shop_config(): array
{
    $localFile = __DIR__ . '/local.php';
    $local = is_file($localFile) ? require $localFile : [];
    $local = is_array($local) ? $local : [];
    $momo = is_array($local['momo'] ?? null) ? $local['momo'] : [];
    return [
        'app_url' => rtrim((string) (getenv('APP_URL') ?: ($local['app_url'] ?? '')), '/'),
        'loyalty_vnd_per_point' => max(1000, (int) (getenv('LOYALTY_VND_PER_POINT') ?: ($local['loyalty_vnd_per_point'] ?? 10000))),
        'loyalty_redemption_vnd_per_point' => 1000,
        'momo' => [
            'partner_code' => trim((string) (getenv('MOMO_PARTNER_CODE') ?: ($momo['partner_code'] ?? ''))),
            'access_key' => trim((string) (getenv('MOMO_ACCESS_KEY') ?: ($momo['access_key'] ?? ''))),
            'secret_key' => trim((string) (getenv('MOMO_SECRET_KEY') ?: ($momo['secret_key'] ?? ''))),
            'endpoint' => rtrim((string) (getenv('MOMO_ENDPOINT') ?: ($momo['endpoint'] ?? 'https://test-payment.momo.vn')), '/'),
            'redirect_url' => trim((string) (getenv('MOMO_REDIRECT_URL') ?: ($momo['redirect_url'] ?? ''))),
            'ipn_url' => trim((string) (getenv('MOMO_IPN_URL') ?: ($momo['ipn_url'] ?? ''))),
        ],
    ];
}

function loyalty_points_for_amount(int $amount): int
{
    return max(0, intdiv($amount, shop_config()['loyalty_vnd_per_point']));
}

function momo_config(): array
{
    $config = shop_config()['momo'];
    $baseUrl = shop_config()['app_url'];
    if ($config['redirect_url'] === '' && $baseUrl !== '') {
        $config['redirect_url'] = $baseUrl . '/momo-return.php';
    }
    if ($config['ipn_url'] === '' && $baseUrl !== '') {
        $config['ipn_url'] = $baseUrl . '/api/momo-ipn.php';
    }

    $config['enabled'] = $config['partner_code'] !== ''
        && $config['access_key'] !== ''
        && $config['secret_key'] !== ''
        && filter_var($config['redirect_url'], FILTER_VALIDATE_URL)
        && filter_var($config['ipn_url'], FILTER_VALIDATE_URL);

    return $config;
}
