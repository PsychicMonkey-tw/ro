<?php

declare(strict_types=1);

function trackUniqueVisit(PDO $pdo, string $path): void
{
    $visitDate = date('Y-m-d');
    $ip = clientIpAddress();
    $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $userAgent = function_exists('mb_substr') ? mb_substr($userAgent, 0, 512, 'UTF-8') : substr($userAgent, 0, 512);
    $path = trim($path);
    $path = function_exists('mb_substr') ? mb_substr($path, 0, 255, 'UTF-8') : substr($path, 0, 255);
    $visitorHash = hash('sha256', $visitDate . '|' . $ip . '|' . $userAgent);

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO site_visits (visit_date, visitor_hash, ip_address, user_agent, path)
         VALUES (:visit_date, :visitor_hash, :ip_address, :user_agent, :path)'
    );
    $stmt->execute([
        ':visit_date' => $visitDate,
        ':visitor_hash' => $visitorHash,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent,
        ':path' => $path,
    ]);
}
