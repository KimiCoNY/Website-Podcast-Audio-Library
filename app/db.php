<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'podcast_library';
$user = getenv('DB_USER') ?: 'podcast_app';
$password = getenv('DB_PASSWORD') ?: '';
$dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Cơ sở dữ liệu đang khởi động hoặc tạm thời không sẵn sàng.');
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
