<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$healthToken = trim((string) getenv('HEALTH_TOKEN'));
if ($healthToken !== '') {
    $requestToken = trim((string) ($_GET['token'] ?? ''));
    if ($requestToken === '') {
        $requestToken = trim((string) ($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? ''));
    }

    if ($requestToken === '' || !hash_equals($healthToken, $requestToken)) {
        http_response_code(403);
        echo (string) json_encode([
            'status' => 'forbidden',
            'time' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

try {
    $pdo = db($config);
    $pdo->query('SELECT 1');
    echo (string) json_encode([
        'status' => 'ok',
        'time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    echo (string) json_encode([
        'status' => 'error',
        'time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
