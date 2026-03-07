<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

function pageCachePath(string $prefix, string $scope, string $key): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . '/' . $prefix . '_' . $scope . '_' . $key . '.cache';
}

function pageCacheKey(array $payload): string
{
    $encoded = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if (!is_string($encoded) || $encoded === '') {
        $encoded = serialize($payload);
    }
    return hash('sha256', $encoded);
}

function pageCacheRead(string $prefix, string $scope, array $payload, int $ttlSeconds): ?array
{
    $key = pageCacheKey($payload);
    $path = pageCachePath($prefix, $scope, $key);
    if (!is_file($path)) {
        return null;
    }
    $mtime = (int) @filemtime($path);
    if ($mtime <= 0 || (time() - $mtime) > $ttlSeconds) {
        return null;
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function pageCacheWrite(string $prefix, string $scope, array $payload, array $value): void
{
    $key = pageCacheKey($payload);
    $path = pageCachePath($prefix, $scope, $key);
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || $encoded === '') {
        return;
    }
    @file_put_contents($path, $encoded, LOCK_EX);
}

$pdo = db($config);
trackUniqueVisit($pdo, '/');

$cacheConfig = is_array($config['cache'] ?? null) ? $config['cache'] : [];
$cachePrefix = trim((string) ($cacheConfig['prefix'] ?? 'baza2'));
if ($cachePrefix === '') {
    $cachePrefix = 'baza2';
}
$filtersTtl = max(0, (int) ($cacheConfig['filters_ttl'] ?? 600));
$catalogTtl = max(0, (int) ($cacheConfig['catalog_ttl'] ?? 45));
$reviewsShowcaseTtl = max(0, (int) ($cacheConfig['reviews_showcase_ttl'] ?? 60));

$rawQuery = $_GET['q'] ?? '';
if (is_array($rawQuery)) {
    $rawQuery = (string) ($rawQuery[0] ?? '');
}
$query = normalizeSearchQuery((string) $rawQuery);
$allFilters = pageCacheRead($cachePrefix, 'filters', [], $filtersTtl);
if (!is_array($allFilters)) {
    $allFilters = fetchAmenityFilters($pdo);
    pageCacheWrite($cachePrefix, 'filters', [], $allFilters);
}
$filters = [];
foreach (['wifi', 'parking', 'pool', 'banya'] as $slug) {
    if (isset($allFilters[$slug])) {
        $filters[$slug] = $allFilters[$slug];
    }
}
$filtersLabels = [
    'wifi' => 'Wi-Fi',
    'parking' => 'Парковка',
    'pool' => 'Бассейн',
    'banya' => 'Баня/Сауна',
];
$rawType = $_GET['type'] ?? '';
$requestedTypes = [];
if (is_array($rawType)) {
    $requestedTypes = $rawType;
} elseif (is_string($rawType) && $rawType !== '') {
    $requestedTypes = explode(',', $rawType);
}
$selectedTypes = normalizeTypes($requestedTypes, $filters);
$sort = normalizeSort(trim((string) ($_GET['sort'] ?? 'relevance')));
$page = max(1, (int) ($_GET['page'] ?? 1));

$catalogCachePayload = [
    'q' => $query,
    'types' => $selectedTypes,
    'sort' => $sort,
    'page' => $page,
    'per_page' => 9,
];
$catalog = pageCacheRead($cachePrefix, 'catalog', $catalogCachePayload, $catalogTtl);
if (!is_array($catalog)) {
    $catalog = fetchBases($pdo, $query, $selectedTypes, $sort, $page, 9);
    pageCacheWrite($cachePrefix, 'catalog', $catalogCachePayload, $catalog);
}
$bases = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
$totalItems = (int) ($catalog['total_items'] ?? 0);
$totalPages = (int) ($catalog['total_pages'] ?? 1);
$currentPage = (int) ($catalog['page'] ?? 1);

$reviewsShowcaseCachePayload = [
    'q' => $query,
    'types' => $selectedTypes,
    'sort' => $sort,
    'page' => $page,
    'limit' => 8,
];
$reviewsShowcase = pageCacheRead($cachePrefix, 'reviews_showcase', $reviewsShowcaseCachePayload, $reviewsShowcaseTtl);
if (!is_array($reviewsShowcase)) {
    $reviewsShowcase = fetchRecentReviewsShowcase($pdo, 8);
    pageCacheWrite($cachePrefix, 'reviews_showcase', $reviewsShowcaseCachePayload, $reviewsShowcase);
}

$siteName = (string) ($config['site']['name'] ?? 'Базы отдыха Раменского района');
$origin = requestOrigin($config);
$isFilteredPage = $query !== '' || $selectedTypes !== [] || $sort !== 'relevance';
$isPaginatedPage = $currentPage > 1;
$shouldNoindex = $isFilteredPage;

if ($isFilteredPage) {
    $metaTitle = 'Результаты подбора баз отдыха — ' . $siteName;
    $metaDescription = 'Подбор баз отдыха в Раменском районе по параметрам: удобства, цена и рейтинг. Сбросьте фильтры, чтобы открыть основной каталог.';
} elseif ($isPaginatedPage) {
    $metaTitle = $siteName . ' — страница ' . $currentPage;
    $metaDescription = 'Страница ' . $currentPage . ' каталога баз отдыха Раменского района: цены от собственников, отзывы гостей и удобства.';
} else {
    $metaTitle = $siteName;
    $metaDescription = 'Поиск и сравнение баз отдыха в Раменском районе: цены, рейтинг, удобства и проверенные отзывы.';
}

$canonicalParams = [];
if (!$isFilteredPage && $isPaginatedPage) {
    $canonicalParams['page'] = $currentPage;
}
$canonicalUrl = $origin . '/';
if ($canonicalParams !== []) {
    $canonicalUrl .= '?' . http_build_query($canonicalParams);
}
$ogImage = $origin . (string) (($bases[0]['cover_image'] ?? null) ?: '/public/assets/placeholder.svg');

$cheapestBaseId = 0;
$cheapestPrice = PHP_INT_MAX;
$topRatedBaseId = 0;
$topRatedScore = -1.0;
$topRatedReviewCount = -1;
foreach ($bases as $baseItem) {
    $baseId = (int) ($baseItem['id'] ?? 0);
    $priceFrom = (int) ($baseItem['price_from'] ?? 0);
    if ($priceFrom > 0 && $priceFrom < $cheapestPrice) {
        $cheapestPrice = $priceFrom;
        $cheapestBaseId = $baseId;
    }

    $avgRating = (float) ($baseItem['avg_rating'] ?? 0);
    $reviewCount = (int) ($baseItem['review_count'] ?? 0);
    if ($reviewCount < 1) {
        continue;
    }
    if (
        $avgRating > $topRatedScore
        || ($avgRating === $topRatedScore && $reviewCount > $topRatedReviewCount)
    ) {
        $topRatedScore = $avgRating;
        $topRatedReviewCount = $reviewCount;
        $topRatedBaseId = $baseId;
    }
}

function pageUrl(int $targetPage, string $q, array $types, string $sort): string
{
    $params = ['page' => $targetPage];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($types !== []) {
        $params['type'] = implode(',', $types);
    }
    if ($sort !== 'relevance') {
        $params['sort'] = $sort;
    }
    return '/?' . http_build_query($params);
}

$itemListElements = [];
$position = 1;
foreach ($bases as $baseForSchema) {
    $itemListElements[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'url' => $origin . baseUrl($baseForSchema),
        'name' => (string) ($baseForSchema['name'] ?? ''),
    ];
}
$catalogSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $metaTitle,
    'description' => $metaDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'ru-RU',
];
if ($itemListElements !== []) {
    $catalogSchema['mainEntity'] = [
        '@type' => 'ItemList',
        'itemListElement' => $itemListElements,
    ];
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= yandexMetrikaCounterHtml() ?>
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="<?= $shouldNoindex ? 'noindex,follow' : 'index,follow' ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= e(assetUrl('/public/assets/app.css')) ?>">
    <script type="application/ld+json"><?= (string) json_encode($catalogSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<header class="header">
    <div class="container header-row">
        <a class="catalog-title" href="/">Базы отдыха Раменского района</a>
        <form method="get" action="/" class="header-search-mini">
            <input type="text" name="q" value="<?= e($query) ?>" placeholder="Быстрый поиск...">
            <button type="submit">Найти</button>
        </form>
    </div>
</header>

<main class="container">
    <h1 class="sr-only">Базы отдыха Раменского района</h1>
    <div id="catalog" class="catalog-layout">
    <aside class="panel filters-sidebar">
        <?php if ($query !== '' || $selectedTypes !== [] || $sort !== 'relevance'): ?>
            <a class="reset-link" href="/">Сбросить всё</a>
        <?php endif; ?>

        <form method="get" class="filters-form">
            <?php if ($query !== ''): ?>
                <input type="hidden" name="q" value="<?= e($query) ?>">
            <?php endif; ?>

            <div class="filter-group">
                <p class="filter-group-title">Сортировка</p>
                <select class="filter-select" name="sort" onchange="this.form.submit()">
                    <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По релевантности</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Сначала дешевле</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Сначала дороже</option>
                    <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>По рейтингу</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
                </select>
            </div>

            <div class="filter-group">
                <p class="filter-group-title">Удобства</p>
                <?php foreach ($filters as $key => $item): ?>
                    <label class="filter-check">
                        <input
                            type="checkbox"
                            name="type[]"
                            value="<?= e($key) ?>"
                            <?= in_array($key, $selectedTypes, true) ? 'checked' : '' ?>
                            onchange="this.form.submit()"
                        >
                        <span class="filter-label">
                            <span class="filter-icon" aria-hidden="true">
                                <?php if ($key === 'wifi'): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 9a16 16 0 0 1 20 0"></path>
                                        <path d="M5 13a11 11 0 0 1 14 0"></path>
                                        <path d="M8.5 17a6 6 0 0 1 7 0"></path>
                                        <circle cx="12" cy="20" r="1"></circle>
                                    </svg>
                                <?php elseif ($key === 'parking'): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                                        <path d="M9 17V7h4.5a3.5 3.5 0 0 1 0 7H9"></path>
                                    </svg>
                                <?php elseif ($key === 'pool'): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 18c1.5 1 2.5 1 4 0s2.5-1 4 0 2.5 1 4 0 2.5-1 4 0 2.5 1 4 0"></path>
                                        <path d="M7 10l5-4 5 4"></path>
                                        <path d="M12 6v7"></path>
                                    </svg>
                                <?php elseif ($key === 'banya'): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7 14a5 5 0 0 0 10 0"></path>
                                        <path d="M10 11c0-1.5 1-2 1-3.5S10 5 10 4"></path>
                                        <path d="M14 11c0-1.2 1-1.8 1-3.2S14 5.4 14 4.3"></path>
                                    </svg>
                                <?php endif; ?>
                            </span>
                            <span><?= e((string) ($filtersLabels[$key] ?? $item['label'])) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <noscript><button type="submit">Применить</button></noscript>
        </form>
    </aside>

    <section class="catalog-content">
    <section class="panel catalog-ad-slot" aria-label="Рекламный блок">
        <a class="catalog-promo-banner" href="/">
            <span class="catalog-promo-label">Реклама</span>
            <strong>Ваш баннер может быть здесь</strong>
            <span>Размещение на главной странице каталога</span>
        </a>
    </section>
    <section class="grid">
        <?php if ($bases === []): ?>
            <article class="panel empty">
                <h2>Ничего не найдено</h2>
                <p>Измените параметры поиска или снимите фильтры.</p>
            </article>
        <?php else: ?>
            <?php foreach ($bases as $baseIndex => $base): ?>
                <?php
                $amenities = array_filter(array_map('trim', explode(',', (string) ($base['amenities'] ?? ''))));
                $baseId = (int) ($base['id'] ?? 0);
                $reviewCount = (int) ($base['review_count'] ?? 0);
                $transportHint = baseDistanceToCenterHint($base);
                $railwayHint = baseRailwayHint($base);
                $premiumBadges = [];
                if ($baseId > 0 && $baseId === $topRatedBaseId) {
                    $premiumBadges[] = ['label' => 'Топ рейтинг', 'class' => 'is-rating'];
                }
                if ($baseId > 0 && $baseId === $cheapestBaseId) {
                    $premiumBadges[] = ['label' => 'Доступная цена', 'class' => 'is-price'];
                }
                $serviceBadges = [];
                if (in_array('Wi-Fi', $amenities, true)) {
                    $serviceBadges[] = 'Wi-Fi';
                }
                if (in_array('Парковка', $amenities, true)) {
                    $serviceBadges[] = 'Парковка';
                }
                if (in_array('Бассейн', $amenities, true)) {
                    $serviceBadges[] = 'Бассейн';
                }
                if (in_array('Баня', $amenities, true) || in_array('Сауна', $amenities, true)) {
                    $serviceBadges[] = 'Баня/Сауна';
                }
                ?>
                <article class="card">
                    <div class="card-media">
                        <img
                            src="<?= e((string) ($base['cover_image'] ?: '/public/assets/placeholder.svg')) ?>"
                            alt="<?= e((string) $base['name']) ?>"
                            width="800"
                            height="600"
                            loading="<?= $baseIndex === 0 ? 'eager' : 'lazy' ?>"
                            decoding="async"
                            fetchpriority="<?= $baseIndex === 0 ? 'high' : 'auto' ?>"
                        >
                        <?php if ($premiumBadges !== []): ?>
                            <div class="card-badges">
                                <?php foreach ($premiumBadges as $badge): ?>
                                    <span class="card-badge <?= e((string) $badge['class']) ?>"><?= e((string) $badge['label']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($serviceBadges !== []): ?>
                            <div class="card-badges card-service-badges">
                                <?php foreach ($serviceBadges as $service): ?>
                                    <span class="card-badge is-service"><?= e($service) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-head">
                            <h2><a href="<?= e(baseUrl($base)) ?>"><?= e((string) $base['name']) ?></a></h2>
                            <span class="card-score <?= $reviewCount < 1 ? 'is-empty' : '' ?>">
                                <?php if ($reviewCount < 1): ?>
                                    Нет оценок
                                <?php else: ?>
                                    <span class="star-icon" aria-hidden="true">★</span> <?= e(number_format((float) $base['avg_rating'], 1)) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <p class="card-address"><?= e((string) $base['address']) ?></p>
                        <div class="card-lines">
                            <?php if ($transportHint !== ''): ?>
                                <p class="card-line"><?= e($transportHint) ?></p>
                            <?php endif; ?>
                            <?php if ($railwayHint !== ''): ?>
                                <p class="card-line"><?= e($railwayHint) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-bottom">
                            <p class="price card-price-inline"><?= e(formatPriceRange((int) $base['price_from'], (int) $base['price_to'])) ?></p>
                            <a class="details-link" href="<?= e(baseUrl($base)) ?>">Подробнее</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="<?= e(pageUrl($currentPage - 1, $query, $selectedTypes, $sort)) ?>">Назад</a>
            <?php endif; ?>
            <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                <a href="<?= e(pageUrl($p, $query, $selectedTypes, $sort)) ?>" class="<?= $p === $currentPage ? 'active' : '' ?>"><?= e((string) $p) ?></a>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= e(pageUrl($currentPage + 1, $query, $selectedTypes, $sort)) ?>">Вперед</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <?php if ($reviewsShowcase !== []): ?>
        <section class="panel reviews-showcase">
            <div class="reviews-showcase-head">
                <h2>Отзывы путешественников</h2>
                <div class="reviews-nav" aria-label="Навигация по отзывам">
                    <button type="button" class="reviews-nav-btn" data-carousel-target="reviewsCarousel" data-direction="prev" aria-label="Предыдущие отзывы">‹</button>
                    <button type="button" class="reviews-nav-btn" data-carousel-target="reviewsCarousel" data-direction="next" aria-label="Следующие отзывы">›</button>
                </div>
            </div>
            <p class="reviews-position" data-carousel-position-for="reviewsCarousel">1 / <?= e((string) count($reviewsShowcase)) ?></p>
            <div id="reviewsCarousel" class="reviews-carousel">
                <?php foreach ($reviewsShowcase as $reviewIndex => $reviewCard): ?>
                    <?php
                    $reviewBaseUrl = baseUrl([
                        'id' => $reviewCard['id'],
                        'name' => $reviewCard['name'],
                    ]);
                    $reviewLocationHint = baseDistanceToCenterHint($reviewCard);
                    ?>
                    <article class="review-teaser">
                        <a class="review-teaser-media" href="<?= e($reviewBaseUrl) ?>">
                            <img
                                src="<?= e((string) $reviewCard['cover_image']) ?>"
                                alt="<?= e((string) $reviewCard['name']) ?>"
                                width="640"
                                height="480"
                                loading="<?= $reviewIndex === 0 ? 'eager' : 'lazy' ?>"
                                decoding="async"
                                fetchpriority="<?= $reviewIndex === 0 ? 'high' : 'auto' ?>"
                            >
                            <span class="review-teaser-title"><?= e((string) $reviewCard['name']) ?></span>
                            <?php if ($reviewLocationHint !== ''): ?>
                                <span class="review-teaser-sub"><?= e($reviewLocationHint) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="review-teaser-body">
                            <p class="review-teaser-author"><?= e((string) $reviewCard['author_name']) ?> · <span class="star-icon" aria-hidden="true">★</span> <?= e((string) $reviewCard['rating']) ?></p>
                            <p class="review-teaser-text"><?= e((string) $reviewCard['review_text']) ?></p>
                            <a class="review-teaser-link" href="<?= e($reviewBaseUrl) ?>">Подробнее</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel catalog-guide">
        <h2>Как выбрать базу отдыха в Раменском районе</h2>
        <p>Сравнивайте карточки по цене, отзывам, расстоянию до центра населённого пункта и близости к ж/д станции. Для точного подбора используйте фильтры удобств и сортировку каталога.</p>
        <p>В каталоге публикуются только актуальные карточки с адресом, фото и контактами. Перед бронированием уточняйте свободные даты и условия размещения на стороне базы.</p>
    </section>
    </section>
    </div>
</main>

<footer class="footer">
    <div class="container">© <?= date('Y') ?> <?= e($siteName) ?></div>
</footer>
<script>
(() => {
  const buttons = document.querySelectorAll('.reviews-nav-btn[data-carousel-target]');
  const updatePosition = (carousel) => {
    if (!carousel) return;
    const total = carousel.querySelectorAll('.review-teaser').length;
    if (!total) return;
    const counter = document.querySelector(`[data-carousel-position-for="${carousel.id}"]`);
    if (!counter) return;
    const firstCard = carousel.querySelector('.review-teaser');
    const cardWidth = firstCard ? firstCard.getBoundingClientRect().width : 280;
    const gap = 12;
    const step = Math.max(1, cardWidth + gap);
    const index = Math.min(total, Math.max(1, Math.round(carousel.scrollLeft / step) + 1));
    counter.textContent = `${index} / ${total}`;
  };

  const processed = new Set();
  buttons.forEach((button) => {
    const targetId = button.getAttribute('data-carousel-target');
    const carousel = targetId ? document.getElementById(targetId) : null;
    if (!carousel) return;

    if (!processed.has(carousel.id)) {
      processed.add(carousel.id);
      carousel.addEventListener('scroll', () => updatePosition(carousel), { passive: true });
      window.addEventListener('resize', () => updatePosition(carousel));
      updatePosition(carousel);
    }

    button.addEventListener('click', () => {
      const direction = button.getAttribute('data-direction') === 'prev' ? -1 : 1;
      const firstCard = carousel.querySelector('.review-teaser');
      const cardWidth = firstCard ? firstCard.getBoundingClientRect().width : 280;
      const gap = 12;
      carousel.scrollBy({
        left: direction * (cardWidth + gap),
        behavior: 'smooth',
      });
      setTimeout(() => updatePosition(carousel), 220);
    });
  });
})();
</script>
</body>
</html>
