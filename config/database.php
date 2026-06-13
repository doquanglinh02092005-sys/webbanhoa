<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function database_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'web_ban_hoa',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ];
}

function database_name(): string
{
    $name = database_config()['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Tên database không hợp lệ.');
    }

    return $name;
}

function database_server(): mysqli
{
    $config = database_config();
    $connection = new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        '',
        $config['port']
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}

function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $config = database_config();
    $connection = new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        $config['name'],
        $config['port']
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}
