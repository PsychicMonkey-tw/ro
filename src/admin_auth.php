<?php

declare(strict_types=1);

function currentAdmin(): ?array
{
    ensureSessionStarted();
    $admin = $_SESSION['admin_user'] ?? null;
    if (!is_array($admin)) {
        return null;
    }
    if (!isset($admin['id'], $admin['email'])) {
        return null;
    }
    return [
        'id' => (int) $admin['id'],
        'email' => (string) $admin['email'],
    ];
}

function isAdminLoggedIn(): bool
{
    return currentAdmin() !== null;
}

function adminAuthClientIp(): string
{
    if (function_exists('clientIpAddress')) {
        return clientIpAddress();
    }
    $fallback = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return $fallback !== '' ? $fallback : '0.0.0.0';
}

function adminLoginLimits(array $config): array
{
    $security = is_array($config['security'] ?? null) ? $config['security'] : [];
    $adminLogin = is_array($security['admin_login'] ?? null) ? $security['admin_login'] : [];

    return [
        'max_attempts' => max(1, (int) ($adminLogin['max_attempts'] ?? 5)),
        'window_seconds' => max(60, (int) ($adminLogin['window_seconds'] ?? 900)),
        'block_seconds' => max(60, (int) ($adminLogin['block_seconds'] ?? 900)),
    ];
}

function adminLoginThrottleStoragePath(array $config, string $email, string $ip): string
{
    $limits = adminLoginLimits($config);
    $scope = $limits['window_seconds'] . '|' . $limits['block_seconds'];
    $identity = strtolower(trim($email)) . '|' . $ip . '|' . $scope;
    $hash = hash('sha256', $identity);
    $dir = rtrim(sys_get_temp_dir(), '/\\') . '/baza2_admin_login_rate';

    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    return $dir . '/' . $hash . '.json';
}

function adminLoginThrottleStatus(array $config, string $email, ?string $ip = null): array
{
    $emailNormalized = trim($email);
    if ($emailNormalized === '') {
        return ['allowed' => true, 'retry_after' => 0];
    }

    $ipAddress = $ip ?? adminAuthClientIp();
    $path = adminLoginThrottleStoragePath($config, $emailNormalized, $ipAddress);
    if (!is_file($path)) {
        return ['allowed' => true, 'retry_after' => 0];
    }

    $raw = @file_get_contents($path);
    $state = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($state)) {
        return ['allowed' => true, 'retry_after' => 0];
    }

    $blockedUntil = (int) ($state['blocked_until'] ?? 0);
    $retryAfter = max(0, $blockedUntil - time());
    if ($retryAfter > 0) {
        return ['allowed' => false, 'retry_after' => $retryAfter];
    }

    return ['allowed' => true, 'retry_after' => 0];
}

function adminRegisterFailedLoginAttempt(array $config, string $email, ?string $ip = null): void
{
    $emailNormalized = trim($email);
    if ($emailNormalized === '') {
        return;
    }

    $ipAddress = $ip ?? adminAuthClientIp();
    $limits = adminLoginLimits($config);
    $path = adminLoginThrottleStoragePath($config, $emailNormalized, $ipAddress);
    $now = time();

    $state = ['attempts' => [], 'blocked_until' => 0];
    $raw = @file_get_contents($path);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
        $state['attempts'] = is_array($decoded['attempts'] ?? null) ? $decoded['attempts'] : [];
        $state['blocked_until'] = (int) ($decoded['blocked_until'] ?? 0);
    }

    $windowStart = $now - $limits['window_seconds'];
    $attempts = [];
    foreach ($state['attempts'] as $attemptTs) {
        $attemptTs = (int) $attemptTs;
        if ($attemptTs >= $windowStart) {
            $attempts[] = $attemptTs;
        }
    }

    $attempts[] = $now;
    $state['attempts'] = $attempts;
    if (count($attempts) >= $limits['max_attempts']) {
        $state['blocked_until'] = $now + $limits['block_seconds'];
        $state['attempts'] = [];
    }

    @file_put_contents($path, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function adminResetLoginAttempts(array $config, string $email, ?string $ip = null): void
{
    $emailNormalized = trim($email);
    if ($emailNormalized === '') {
        return;
    }

    $ipAddress = $ip ?? adminAuthClientIp();
    $path = adminLoginThrottleStoragePath($config, $emailNormalized, $ipAddress);
    if (is_file($path)) {
        @unlink($path);
    }
}

function adminLogin(PDO $pdo, string $email, string $password, array $config = []): bool
{
    ensureSessionStarted();
    $emailNormalized = trim($email);
    $emailNormalized = function_exists('mb_strtolower') ? mb_strtolower($emailNormalized, 'UTF-8') : strtolower($emailNormalized);
    $ipAddress = adminAuthClientIp();

    $throttle = adminLoginThrottleStatus($config, $emailNormalized, $ipAddress);
    if (!$throttle['allowed']) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT id, email, password_hash
         FROM admin_users
         WHERE email = :email AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute([':email' => $emailNormalized]);
    $admin = $stmt->fetch();
    if (!is_array($admin)) {
        adminRegisterFailedLoginAttempt($config, $emailNormalized, $ipAddress);
        return false;
    }

    $hash = (string) ($admin['password_hash'] ?? '');
    if (!password_verify($password, $hash)) {
        adminRegisterFailedLoginAttempt($config, $emailNormalized, $ipAddress);
        return false;
    }

    adminResetLoginAttempts($config, $emailNormalized, $ipAddress);
    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => (int) $admin['id'],
        'email' => (string) $admin['email'],
    ];
    return true;
}

function requireAdminAuth(): void
{
    if (isAdminLoggedIn()) {
        return;
    }
    header('Location: /admin/login.php');
    exit;
}

function adminLogout(): void
{
    ensureSessionStarted();
    unset($_SESSION['admin_user']);
    session_regenerate_id(true);
}
