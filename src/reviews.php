<?php

declare(strict_types=1);

function trustedProxyIps(): array
{
    $raw = trim((string) getenv('TRUSTED_PROXY_IPS'));
    if ($raw === '') {
        return [];
    }

    $items = preg_split('/\s*,\s*/', $raw) ?: [];
    $result = [];
    foreach ($items as $item) {
        $ip = trim((string) $item);
        if ($ip === '') {
            continue;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            $result[$ip] = true;
        }
    }

    return array_keys($result);
}

function isTrustedProxy(string $remoteAddr): bool
{
    if ($remoteAddr === '') {
        return false;
    }
    return in_array($remoteAddr, trustedProxyIps(), true);
}

function clientIpAddress(): string
{
    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteAddr !== '' && !isTrustedProxy($remoteAddr)) {
        return filter_var($remoteAddr, FILTER_VALIDATE_IP) !== false ? $remoteAddr : '0.0.0.0';
    }

    $candidates = [];
    $forwardedByTrustedProxy = $remoteAddr !== '' && isTrustedProxy($remoteAddr);
    if ($forwardedByTrustedProxy) {
        $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
        $candidates[] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    }
    $candidates[] = $remoteAddr !== '' ? $remoteAddr : null;

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        $value = trim(explode(',', $candidate)[0]);
        if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return $value;
        }
    }

    return '0.0.0.0';
}

function canSubmitReviewByIp(PDO $pdo, int $baseId, string $ip, int $dailyLimit = 2): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM reviews
         WHERE base_id = :base_id
           AND author_ip = :author_ip
           AND DATE(created_at) = CURDATE()'
    );
    $stmt->execute([
        ':base_id' => $baseId,
        ':author_ip' => $ip,
    ]);

    return ((int) $stmt->fetchColumn()) < $dailyLimit;
}

function createReview(PDO $pdo, int $baseId, string $authorName, int $rating, string $reviewText): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO reviews (base_id, author_name, rating, review_text, author_ip, status)
         VALUES (:base_id, :author_name, :rating, :review_text, :author_ip, :status)'
    );
    $authorTrimmed = function_exists('mb_substr') ? mb_substr($authorName, 0, 120, 'UTF-8') : substr($authorName, 0, 120);
    $reviewTrimmed = function_exists('mb_substr') ? mb_substr($reviewText, 0, 1400, 'UTF-8') : substr($reviewText, 0, 1400);
    $stmt->execute([
        ':base_id' => $baseId,
        ':author_name' => $authorTrimmed,
        ':rating' => max(1, min(5, $rating)),
        ':review_text' => $reviewTrimmed,
        ':author_ip' => clientIpAddress(),
        ':status' => 'pending',
    ]);
}
