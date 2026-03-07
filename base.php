<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$pdo = db($config);
$slug = trim((string) ($_GET['slug'] ?? ''));
$base = $slug !== '' ? fetchBaseBySlug($pdo, $slug) : null;

if (!is_array($base)) {
    http_response_code(404);
    $siteName = (string) ($config['site']['name'] ?? 'Базы отдыха Раменского района');
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= yandexMetrikaCounterHtml() ?>
        <title>Страница не найдена — <?= e($siteName) ?></title>
        <meta name="robots" content="noindex,follow">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/svg+xml" href="/public/assets/favicon.svg">
        <link rel="shortcut icon" href="/favicon.ico">
        <link rel="stylesheet" href="<?= e(assetUrl('/public/assets/app.css')) ?>">
    </head>
    <body class="base-page base-page-not-found">
    <header class="header">
        <div class="container header-row">
            <a class="brand" href="/"><?= e($siteName) ?></a>
            <a class="back-link" href="/">← Назад к каталогу</a>
        </div>
    </header>
    <main class="container">
        <section class="panel not-found-panel">
            <h1>Страница не найдена</h1>
            <p class="muted">Похоже, такой карточки базы больше нет или адрес изменился.</p>
            <div class="actions">
                <a href="/">Перейти в каталог</a>
            </div>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// Redirect legacy URL format to canonical pretty URL.
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($requestPath === '/base.php') {
    $canonical = baseUrl($base);
    if (isset($_GET['review']) && (string) $_GET['review'] !== '') {
        $canonical = baseUrl($base, ['review' => (string) $_GET['review']]);
    }
    header('Location: ' . $canonical, true, 301);
    exit;
}

trackUniqueVisit($pdo, '/base/' . $slug);

$baseId = (int) $base['id'];
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности формы. Обновите страницу.';
    } else {
        $authorName = trim((string) ($_POST['author_name'] ?? ''));
        $reviewText = trim((string) ($_POST['review_text'] ?? ''));
        $rating = (int) ($_POST['rating'] ?? 0);

        if ($authorName === '' || $reviewText === '' || $rating < 1 || $rating > 5) {
            $error = 'Заполните имя, отзыв и оценку от 1 до 5.';
        } elseif (!canSubmitReviewByIp($pdo, $baseId, clientIpAddress(), 2)) {
            $error = 'Лимит отзывов за день с вашего IP исчерпан.';
        } else {
            createReview($pdo, $baseId, $authorName, $rating, $reviewText);
            header('Location: ' . baseUrl($base, ['review' => 'pending']) . '#reviews');
            exit;
        }
    }
}

if (($_GET['review'] ?? '') === 'pending') {
    $notice = 'Спасибо! Отзыв отправлен на модерацию.';
}

$stats = fetchReviewStats($pdo, $baseId);
$reviews = fetchReviews($pdo, $baseId, 12);
$siteName = (string) ($config['site']['name'] ?? 'Базы отдыха Раменского района');
$images = is_array($base['images'] ?? null) ? $base['images'] : [];
$mainImage = $images[0]['image_path'] ?? '/public/assets/placeholder.svg';
$galleryImages = array_slice($images, 0, 6);
if ($galleryImages === []) {
    $galleryImages = [
        ['image_path' => $mainImage, 'alt_text' => (string) $base['name']],
    ];
}
$amenities = is_array($base['amenities'] ?? null) ? $base['amenities'] : [];
$baseBadges = premiumBadges($amenities, (float) $stats['avg_rating'], (int) $stats['review_count'], (int) ($base['price_from'] ?? 0));
$headerBadges = array_values(array_filter(
    $baseBadges,
    static fn (array $badge): bool => (string) ($badge['class'] ?? '') !== 'is-amenity'
));
$thumbImages = array_slice($galleryImages, 1);
$transportHint = baseDistanceToCenterHint($base);
$railwayHint = baseRailwayHint($base);
$origin = requestOrigin($config);
$officialWebsiteUrl = normalizePublicWebsiteUrl((string) ($base['booking_url'] ?? ''), $origin);
$canonicalUrl = $origin . baseUrl($base);
$hasReviewParam = isset($_GET['review']) && (string) $_GET['review'] !== '';
$metaTitle = (string) $base['name'] . ' — ' . $siteName;
$metaDescription = trim((string) ($base['short_description'] ?? ''));
if ($metaDescription === '') {
    $metaDescription = 'База отдыха «' . (string) $base['name'] . '» в Раменском районе: адрес, цены, удобства и отзывы гостей.';
}
$metaDescription = function_exists('mb_substr')
    ? mb_substr($metaDescription, 0, 190, 'UTF-8')
    : substr($metaDescription, 0, 190);
$absoluteMainImage = $origin . (string) $mainImage;

$lodgingSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'LodgingBusiness',
    'name' => (string) $base['name'],
    'description' => $metaDescription,
    'url' => $canonicalUrl,
    'image' => $absoluteMainImage,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => (string) ($base['address'] ?? ''),
        'addressLocality' => 'Раменское',
        'addressRegion' => 'Московская область',
        'addressCountry' => 'RU',
    ],
];
if ($officialWebsiteUrl !== '') {
    $lodgingSchema['sameAs'] = [$officialWebsiteUrl];
}
if ((int) $stats['review_count'] > 0) {
    $lodgingSchema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format((float) $stats['avg_rating'], 1, '.', ''),
        'reviewCount' => (int) $stats['review_count'],
        'bestRating' => 5,
        'worstRating' => 1,
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
    <meta name="robots" content="<?= $hasReviewParam ? 'noindex,follow' : 'index,follow' ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($absoluteMainImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($absoluteMainImage) ?>">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= e(assetUrl('/public/assets/app.css')) ?>">
    <script type="application/ld+json"><?= (string) json_encode($lodgingSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="base-page">
<header class="header">
    <div class="container header-row">
        <a class="brand" href="/"><?= e($siteName) ?></a>
        <a class="back-link" href="/">← Назад к каталогу</a>
    </div>
</header>

<main class="container">
    <section class="base-hero panel">
        <div class="base-gallery">
            <div class="base-cover-wrap">
                <img
                    class="base-cover"
                    src="<?= e((string) $mainImage) ?>"
                    alt="<?= e((string) $base['name']) ?>"
                    width="1200"
                    height="900"
                    decoding="async"
                    fetchpriority="high"
                >
                <?php if ($amenities !== [] || $officialWebsiteUrl !== ''): ?>
                    <div class="card-badges card-service-badges base-cover-badges">
                        <?php foreach (array_slice($amenities, 0, 6) as $amenity): ?>
                            <span class="card-badge"><?= e((string) $amenity) ?></span>
                        <?php endforeach; ?>
                        <?php if ($officialWebsiteUrl !== ''): ?>
                            <a class="card-badge base-cover-link" href="<?= e($officialWebsiteUrl) ?>" target="_blank" rel="noopener">Сайт базы</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($thumbImages !== []): ?>
                <div class="base-thumbs">
                    <?php foreach ($thumbImages as $image): ?>
                        <?php $imagePath = (string) ($image['image_path'] ?? '/public/assets/placeholder.svg'); ?>
                        <?php $imageAlt = (string) ($image['alt_text'] ?? $base['name']); ?>
                        <a class="base-thumb" href="<?= e($imagePath) ?>" target="_blank" rel="noopener">
                            <img
                                src="<?= e($imagePath) ?>"
                                alt="<?= e($imageAlt) ?>"
                                width="300"
                                height="225"
                                loading="lazy"
                                decoding="async"
                                fetchpriority="low"
                            >
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="base-content">
            <?php if ($headerBadges !== []): ?>
                <div class="card-badges base-badges">
                    <?php foreach ($headerBadges as $badge): ?>
                        <span class="card-badge <?= e((string) $badge['class']) ?>"><?= e((string) $badge['label']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <h1><?= e((string) $base['name']) ?></h1>
            <p class="muted"><?= e((string) $base['address']) ?></p>
            <p class="base-summary"><?= e((string) $base['short_description']) ?></p>

            <div class="data-grid base-meta-grid">
                <div class="data-item">
                    <span class="data-label">Цена</span>
                    <strong class="data-value price"><?= e(formatPriceRange((int) $base['price_from'], (int) $base['price_to'])) ?></strong>
                </div>
                <div class="data-item">
                    <span class="data-label">Рейтинг</span>
                    <strong class="data-value rating <?= (int) $stats['review_count'] < 1 ? 'is-empty' : '' ?>">
                        <?php if ((int) $stats['review_count'] < 1): ?>
                            Нет оценок
                        <?php else: ?>
                            <span class="star-icon" aria-hidden="true">★</span>
                            <?= e(number_format((float) $stats['avg_rating'], 1, '.', '')) ?>
                            · <?= e((string) ((int) $stats['review_count'])) ?> <?= e(reviewWordByCount((int) $stats['review_count'])) ?>
                        <?php endif; ?>
                    </strong>
                </div>
                <?php if ($transportHint !== ''): ?>
                    <div class="data-item">
                        <span class="data-label">Локация</span>
                        <span class="data-value"><?= e($transportHint) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($railwayHint !== ''): ?>
                    <div class="data-item">
                        <span class="data-label">Транспорт</span>
                        <span class="data-value"><?= e($railwayHint) ?></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <?php if (!empty($base['description'])): ?>
        <section class="panel">
            <h2>Описание</h2>
            <p><?= nl2br(e((string) $base['description'])) ?></p>
        </section>
    <?php endif; ?>

    <section id="reviews" class="panel">
        <h2>Отзывы</h2>
        <?php if ($notice !== ''): ?><p class="notice" role="status" aria-live="polite"><?= e($notice) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="error" role="alert" aria-live="assertive"><?= e($error) ?></p><?php endif; ?>

        <form method="post" class="review-form">
            <input type="hidden" name="action" value="add_review">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Ваше имя
                <input type="text" name="author_name" maxlength="120" required>
            </label>
            <label>Оценка
                <select name="rating" required>
                    <option value="5">5 — Отлично</option>
                    <option value="4">4 — Хорошо</option>
                    <option value="3">3 — Нормально</option>
                    <option value="2">2 — Плохо</option>
                    <option value="1">1 — Очень плохо</option>
                </select>
            </label>
            <label>Отзыв
                <textarea name="review_text" maxlength="1400" required></textarea>
            </label>
            <button type="submit">Отправить на модерацию</button>
        </form>

        <div class="reviews-list">
            <?php if ($reviews === []): ?>
                <p class="muted">Пока нет опубликованных отзывов.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-item">
                        <div class="review-head">
                            <strong><?= e((string) $review['author_name']) ?></strong>
                            <span class="review-score"><span class="star-icon" aria-hidden="true">★</span> <?= e((string) $review['rating']) ?></span>
                        </div>
                        <p class="review-text"><?= nl2br(e((string) $review['review_text'])) ?></p>
                        <p class="muted review-date"><?= e(formatReviewDate((string) $review['created_at'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
