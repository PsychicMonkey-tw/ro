<?php

declare(strict_types=1);

if (!function_exists('ensureSessionStarted')) {
    function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }

        $cookieParams = session_get_cookie_params();
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => (string) ($cookieParams['path'] ?? '/'),
                'domain' => (string) ($cookieParams['domain'] ?? ''),
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(
                0,
                ((string) ($cookieParams['path'] ?? '/')) . '; samesite=Lax',
                (string) ($cookieParams['domain'] ?? ''),
                $isHttps,
                true
            );
        }

        session_start();

        $idleTimeout = (int) (getenv('SESSION_IDLE_TIMEOUT') ?: 7200);
        $lastActivity = (int) ($_SESSION['__last_activity'] ?? 0);
        $now = time();
        if ($idleTimeout > 0 && $lastActivity > 0 && ($now - $lastActivity) > $idleTimeout) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['__last_activity'] = $now;
    }
}

$configPath = __DIR__ . '/../config.php';
if (!is_file($configPath)) {
    $examplePath = __DIR__ . '/../config.example.php';
    if (is_file($examplePath)) {
        copy($examplePath, $configPath);
    }
}

$config = require $configPath;

if (!function_exists('sendSecurityHeaders')) {
    function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://mc.yandex.ru; font-src 'self' data:; connect-src 'self' https://mc.yandex.ru; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

sendSecurityHeaders();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/admin_auth.php';
