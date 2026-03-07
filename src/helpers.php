<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function assetUrl(string $path): string
{
    if ($path === '' || $path[0] !== '/') {
        return $path;
    }

    $fullPath = dirname(__DIR__) . $path;
    if (!is_file($fullPath)) {
        return $path;
    }

    $version = (int) @filemtime($fullPath);
    if ($version <= 0) {
        return $path;
    }

    $separator = strpos($path, '?') === false ? '?' : '&';
    return $path . $separator . 'v=' . $version;
}

function baseSlug(array $base): string
{
    $name = (string) ($base['name'] ?? 'baza');
    $slug = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $slug = strtr($slug, $map);
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? 'baza';
    $slug = trim($slug, '-');
    $id = (int) ($base['id'] ?? 0);

    return ($slug !== '' ? $slug : 'baza') . '-' . $id;
}

function baseUrl(array $base, array $params = []): string
{
    $url = '/baza/' . rawurlencode(baseSlug($base));
    if ($params !== []) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function requestOrigin(array $config): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host !== '') {
        return $scheme . '://' . $host;
    }

    $siteUrl = trim((string) ($config['site']['url'] ?? ''));
    return $siteUrl !== '' ? rtrim($siteUrl, '/') : '';
}

function normalizeSort(string $sort): string
{
    $allowed = ['relevance', 'price_asc', 'price_desc', 'rating_desc', 'newest'];
    return in_array($sort, $allowed, true) ? $sort : 'relevance';
}

function formatPriceRange(?int $from, ?int $to): string
{
    $priceFrom = max(0, (int) $from);
    $priceTo = max(0, (int) $to);
    if ($priceFrom > 0) {
        return "от {$priceFrom} ₽";
    }
    if ($priceTo > 0) {
        return "от {$priceTo} ₽";
    }
    return 'Цена по запросу';
}

function reviewWordByCount(int $count): string
{
    $abs = abs($count) % 100;
    $tail = $abs % 10;
    if ($abs > 10 && $abs < 20) {
        return 'отзывов';
    }
    if ($tail === 1) {
        return 'отзыв';
    }
    if ($tail >= 2 && $tail <= 4) {
        return 'отзыва';
    }
    return 'отзывов';
}

function formatRatingSummary(float $avgRating, int $reviewCount): string
{
    if ($reviewCount < 1) {
        return 'Нет оценок';
    }
    return '★ ' . number_format($avgRating, 1, '.', '') . ' · ' . $reviewCount . ' ' . reviewWordByCount($reviewCount);
}

function formatReviewDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    if (!$date instanceof DateTimeImmutable) {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }
        $date = (new DateTimeImmutable())->setTimestamp($timestamp);
    }

    $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];
    $month = (int) $date->format('n');
    $monthName = $months[$month] ?? $date->format('m');

    return $date->format('j') . ' ' . $monthName . ' ' . $date->format('Y');
}

function csrfToken(): string
{
    ensureSessionStarted();
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    if (!headers_sent()) {
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        if (PHP_VERSION_ID >= 70300) {
            setcookie('csrf_token', $_SESSION['csrf_token'], [
                'expires' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie(
                'csrf_token',
                $_SESSION['csrf_token'],
                0,
                '/; samesite=Lax',
                '',
                $isHttps,
                true
            );
        }
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    ensureSessionStarted();
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? null;
    if (is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token)) {
        return true;
    }

    $cookieToken = $_COOKIE['csrf_token'] ?? null;
    return is_string($cookieToken) && $cookieToken !== '' && hash_equals($cookieToken, $token);
}

function normalizePublicWebsiteUrl(string $url, string $origin = ''): string
{
    $candidate = trim($url);
    if ($candidate === '') {
        return '';
    }

    if (filter_var($candidate, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    $parts = parse_url($candidate);
    if (!is_array($parts)) {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }

    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host === '') {
        return '';
    }

    if ($origin !== '') {
        $originParts = parse_url($origin);
        $originHost = is_array($originParts) ? strtolower((string) ($originParts['host'] ?? '')) : '';
        if ($originHost !== '' && $host === $originHost) {
            return '';
        }
    }

    return $candidate;
}

function yandexMetrikaCounterHtml(): string
{
    return <<<'HTML'
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=107082526', 'ym');

    ym(107082526, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/107082526" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
HTML;
}

function premiumBadges(array $amenities, float $avgRating, int $reviewCount, int $priceFrom): array
{
    $badges = [];

    if ($avgRating >= 4.8 && $reviewCount >= 1) {
        $badges[] = ['label' => 'Топ рейтинг', 'class' => 'is-rating'];
    }

    if (in_array('Баня', $amenities, true)) {
        $badges[] = ['label' => 'С баней', 'class' => 'is-amenity'];
    } elseif (in_array('Пляж', $amenities, true)) {
        $badges[] = ['label' => 'У воды', 'class' => 'is-amenity'];
    } elseif (in_array('SPA', $amenities, true)) {
        $badges[] = ['label' => 'SPA', 'class' => 'is-amenity'];
    }

    if ($priceFrom > 0 && $priceFrom <= 4000) {
        $badges[] = ['label' => 'Доступная цена', 'class' => 'is-price'];
    }

    return array_slice($badges, 0, 2);
}

function extractSettlementFromAddress(string $address): string
{
    if ($address === '') {
        return '';
    }

    $settlement = '';

    if (preg_match('/^([^,]+),/u', $address, $matches) === 1) {
        $candidate = trim((string) $matches[1]);
        if (!preg_match('/обл|область|район|г\.о|ул\.|улиц|просп|шоссе/u', $candidate)) {
            $settlement = $candidate;
        }
    }

    // If address is specified via district without a leading town,
    // use district center (Ramenskoye) for distance calculation.
    if (
        $settlement === ''
        && preg_match('/раменск[а-я\s\.\-]*(район|р-н|г\.о|округ)/u', $address) === 1
    ) {
        $settlement = 'Раменское';
    }

    if ($settlement === '' && preg_match('/(?:дер|д)\.\s*([^,]+)/u', $address, $matches) === 1) {
        $settlement = trim((string) $matches[1]);
    }

    if ($settlement === '' && preg_match('/(?:пос|п)\.\s*([^,]+)/u', $address, $matches) === 1) {
        $settlement = trim((string) $matches[1]);
    }

    return $settlement;
}

function settlementCenterCoords(string $settlement): ?array
{
    $map = [
        'Кратово' => [55.591111, 38.180278],
        'Раменское' => [55.566900, 38.230300],
        'Гжель' => [55.609440, 38.396390],
        'Григорово' => [55.644608, 38.373577],
        'Турыгино' => [55.577083, 38.506639],
        'Каменное Тяжино' => [55.544738, 38.025666],
        'Заболотье' => [55.548804, 38.189905],
    ];

    return $map[$settlement] ?? null;
}

function baseCoords(array $base): ?array
{
    if (
        isset($base['lat'], $base['lng'])
        && is_numeric($base['lat'])
        && is_numeric($base['lng'])
        && (float) $base['lat'] !== 0.0
        && (float) $base['lng'] !== 0.0
    ) {
        return [(float) $base['lat'], (float) $base['lng']];
    }

    $id = (int) ($base['id'] ?? 0);
    $fallback = [
        1 => [55.544738, 38.025666],       // Chulkovo Club
        3 => [55.577083, 38.506639],       // Ozero Ponti
        4 => [55.644608, 38.373577],       // Akvarel
        5 => [55.572212, 38.237219],       // Na Koroleva (approx.)
        6 => [55.548804, 38.189905],       // Taezhny
        7 => [55.591111, 38.180278],       // Tihaya Zavod (settlement fallback)
    ];

    return $fallback[$id] ?? null;
}

function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadiusKm * $c;
}

function baseDistanceToCenterHint(array $base): string
{
    $address = trim((string) ($base['address'] ?? ''));
    $settlement = extractSettlementFromAddress($address);
    if ($settlement === '') {
        return '';
    }

    $center = settlementCenterCoords($settlement);
    $coords = baseCoords($base);
    if (!is_array($center) || !is_array($coords)) {
        return '';
    }

    $distanceKm = haversineKm((float) $coords[0], (float) $coords[1], (float) $center[0], (float) $center[1]);
    $distanceText = number_format($distanceKm, 1, '.', '');

    return $distanceText . ' км до центра ' . $settlement;
}

function baseRailwayHint(array $base): string
{
    $coords = baseCoords($base);
    if (!is_array($coords)) {
        return '';
    }

    $stations = [
        ['name' => 'Раменское', 'lat' => 55.5726, 'lng' => 38.2297],
        ['name' => 'Ипподром', 'lat' => 55.5607, 'lng' => 38.2517],
        ['name' => 'Отдых', 'lat' => 55.6126, 'lng' => 38.0938],
        ['name' => 'Кратово', 'lat' => 55.5958, 'lng' => 38.1712],
        ['name' => 'Ильинская', 'lat' => 55.6197, 'lng' => 38.1183],
        ['name' => 'Удельная', 'lat' => 55.6248, 'lng' => 38.0463],
        ['name' => 'Гжель', 'lat' => 55.6079, 'lng' => 38.4042],
    ];

    $nearest = null;
    $nearestDistance = PHP_FLOAT_MAX;
    foreach ($stations as $station) {
        $distance = haversineKm((float) $coords[0], (float) $coords[1], (float) $station['lat'], (float) $station['lng']);
        if ($distance < $nearestDistance) {
            $nearestDistance = $distance;
            $nearest = $station;
        }
    }

    if (!is_array($nearest) || $nearestDistance === PHP_FLOAT_MAX) {
        return '';
    }

    return number_format($nearestDistance, 1, '.', '') . ' км до ж/д станции ' . (string) $nearest['name'];
}
