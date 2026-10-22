<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

try {
    $pdo->query('SELECT 1');
} catch (PDOException $exception) {
    error_log('Health check failed: ' . $exception->getMessage());
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("database unavailable\n");
}

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo "ok\n";
