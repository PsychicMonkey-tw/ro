<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

function xmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function sitemapCacheFile(string $prefix): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . '/' . $prefix . '_sitemap_cache.xml';
}

$origin = requestOrigin($config);

$cacheConfig = is_array($config['cache'] ?? null) ? $config['cache'] : [];
$cachePrefix = trim((string) ($cacheConfig['prefix'] ?? 'baza2'));
if ($cachePrefix === '') {
    $cachePrefix = 'baza2';
}
$cacheTtlSeconds = max(0, (int) ($cacheConfig['sitemap_ttl'] ?? 900));
header('Cache-Control: public, max-age=' . $cacheTtlSeconds);
$cacheFile = sitemapCacheFile($cachePrefix);
if (is_file($cacheFile) && (time() - (int) @filemtime($cacheFile)) < $cacheTtlSeconds) {
    $cached = @file_get_contents($cacheFile);
    if (is_string($cached) && $cached !== '') {
        echo $cached;
        exit;
    }
}

$pdo = db($config);
$stmt = $pdo->query(
    'SELECT id, name, updated_at
     FROM bases
     WHERE status = "published"
     ORDER BY id DESC
     LIMIT 5000'
);
$bases = $stmt->fetchAll();

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

$homeLoc = $origin . '/';
$xml .= "  <url>\n";
$xml .= '    <loc>' . xmlEscape($homeLoc) . "</loc>\n";
$xml .= "    <changefreq>daily</changefreq>\n";
$xml .= "    <priority>1.0</priority>\n";
$xml .= "  </url>\n";

foreach ($bases as $base) {
    $loc = $origin . baseUrl($base);
    $updatedAt = (string) ($base['updated_at'] ?? '');
    $updatedAtTs = strtotime($updatedAt);
    $lastmod = $updatedAtTs !== false ? date('c', $updatedAtTs) : '';

    $xml .= "  <url>\n";
    $xml .= '    <loc>' . xmlEscape($loc) . "</loc>\n";
    if ($lastmod !== '') {
        $xml .= '    <lastmod>' . xmlEscape($lastmod) . "</lastmod>\n";
    }
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.8</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= "</urlset>\n";
@file_put_contents($cacheFile, $xml, LOCK_EX);
echo $xml;
