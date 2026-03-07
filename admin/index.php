<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

requireAdminAuth();

$pdo = db($config);
$admin = currentAdmin();
$notice = '';
$error = '';
$editingBaseId = max(0, (int) ($_GET['edit_base'] ?? 0));

if (!function_exists('adminPostString')) {
    function adminPostString(string $key): string
    {
        return trim((string) ($_POST[$key] ?? ''));
    }
}

if (!function_exists('adminClearCacheFiles')) {
    function adminClearCacheFiles(string $prefix): int
    {
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?? '';
        if ($safePrefix === '') {
            return 0;
        }
        $pattern = rtrim(sys_get_temp_dir(), '/\\') . '/' . $safePrefix . '_*';
        $files = glob($pattern);
        if (!is_array($files) || $files === []) {
            return 0;
        }
        $removed = 0;
        foreach ($files as $file) {
            if (!is_string($file) || !is_file($file)) {
                continue;
            }
            if (@unlink($file)) {
                $removed++;
            }
        }
        return $removed;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности формы. Обновите страницу.';
    } else {
        $entity = (string) ($_POST['entity'] ?? '');
        if ($entity === 'review') {
            $reviewId = (int) ($_POST['review_id'] ?? 0);
            $action = (string) ($_POST['action'] ?? '');
            $nextStatus = '';
            if ($action === 'publish') {
                $nextStatus = 'published';
            } elseif ($action === 'reject') {
                $nextStatus = 'rejected';
            } elseif ($action === 'pending') {
                $nextStatus = 'pending';
            }

            if ($reviewId < 1 || $nextStatus === '') {
                $error = 'Некорректные данные для модерации.';
            } else {
                $stmt = $pdo->prepare('UPDATE reviews SET status = :status WHERE id = :id LIMIT 1');
                $stmt->execute([
                    ':status' => $nextStatus,
                    ':id' => $reviewId,
                ]);
                $notice = 'Статус отзыва обновлен.';
            }
        } elseif ($entity === 'base') {
            $baseId = (int) ($_POST['base_id'] ?? 0);
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'save') {
                $name = adminPostString('name');
                $shortDescription = adminPostString('short_description');
                $description = adminPostString('description');
                $address = adminPostString('address');
                $phone = adminPostString('phone');
                $bookingUrl = adminPostString('booking_url');
                $priceFrom = max(0, (int) ($_POST['price_from'] ?? 0));
                $priceTo = max(0, (int) ($_POST['price_to'] ?? 0));
                $status = (string) ($_POST['status'] ?? 'draft');
                $coverImage = adminPostString('cover_image');
                $district = adminPostString('district');
                if ($district === '') {
                    $district = 'Раменский район';
                }

                $allowedStatuses = ['draft', 'published', 'archived'];
                if (!in_array($status, $allowedStatuses, true)) {
                    $status = 'draft';
                }

                if ($name === '' || $shortDescription === '' || $address === '') {
                    $error = 'Для карточки заполните название, короткое описание и адрес.';
                } else {
                    if ($baseId > 0) {
                        $stmt = $pdo->prepare(
                            'UPDATE bases
                             SET name = :name,
                                 short_description = :short_description,
                                 description = :description,
                                 address = :address,
                                 district = :district,
                                 phone = :phone,
                                 booking_url = :booking_url,
                                 price_from = :price_from,
                                 price_to = :price_to,
                                 status = :status
                             WHERE id = :id
                             LIMIT 1'
                        );
                        $stmt->execute([
                            ':name' => $name,
                            ':short_description' => $shortDescription,
                            ':description' => $description,
                            ':address' => $address,
                            ':district' => $district,
                            ':phone' => $phone !== '' ? $phone : null,
                            ':booking_url' => $bookingUrl !== '' ? $bookingUrl : null,
                            ':price_from' => $priceFrom,
                            ':price_to' => $priceTo,
                            ':status' => $status,
                            ':id' => $baseId,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO bases (name, short_description, description, address, district, phone, booking_url, price_from, price_to, status)
                             VALUES (:name, :short_description, :description, :address, :district, :phone, :booking_url, :price_from, :price_to, :status)'
                        );
                        $stmt->execute([
                            ':name' => $name,
                            ':short_description' => $shortDescription,
                            ':description' => $description,
                            ':address' => $address,
                            ':district' => $district,
                            ':phone' => $phone !== '' ? $phone : null,
                            ':booking_url' => $bookingUrl !== '' ? $bookingUrl : null,
                            ':price_from' => $priceFrom,
                            ':price_to' => $priceTo,
                            ':status' => $status,
                        ]);
                        $baseId = (int) $pdo->lastInsertId();
                    }

                    if ($baseId > 0 && $coverImage !== '') {
                        $checkStmt = $pdo->prepare(
                            'SELECT id FROM base_images WHERE base_id = :base_id AND is_cover = 1 LIMIT 1'
                        );
                        $checkStmt->execute([':base_id' => $baseId]);
                        $coverRow = $checkStmt->fetch();

                        if (is_array($coverRow)) {
                            $updStmt = $pdo->prepare(
                                'UPDATE base_images
                                 SET image_path = :image_path, alt_text = :alt_text
                                 WHERE id = :id
                                 LIMIT 1'
                            );
                            $updStmt->execute([
                                ':image_path' => $coverImage,
                                ':alt_text' => $name,
                                ':id' => (int) $coverRow['id'],
                            ]);
                        } else {
                            $insStmt = $pdo->prepare(
                                'INSERT INTO base_images (base_id, image_path, alt_text, is_cover, sort_order)
                                 VALUES (:base_id, :image_path, :alt_text, 1, 1)'
                            );
                            $insStmt->execute([
                                ':base_id' => $baseId,
                                ':image_path' => $coverImage,
                                ':alt_text' => $name,
                            ]);
                        }
                    }

                    header('Location: /admin/index.php?edit_base=' . $baseId . '&saved=1');
                    exit;
                }
            } elseif ($action === 'set_status') {
                $status = (string) ($_POST['status'] ?? '');
                $allowedStatuses = ['draft', 'published', 'archived'];
                if ($baseId < 1 || !in_array($status, $allowedStatuses, true)) {
                    $error = 'Некорректные данные для смены статуса карточки.';
                } else {
                    $stmt = $pdo->prepare('UPDATE bases SET status = :status WHERE id = :id LIMIT 1');
                    $stmt->execute([
                        ':status' => $status,
                        ':id' => $baseId,
                    ]);
                    $notice = 'Статус карточки обновлен.';
                }
            } elseif ($action === 'delete') {
                if ($baseId < 1) {
                    $error = 'Некорректные данные для удаления карточки.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM bases WHERE id = :id LIMIT 1');
                    $stmt->execute([':id' => $baseId]);

                    if ($stmt->rowCount() > 0) {
                        header('Location: /admin/index.php?deleted=1');
                        exit;
                    }

                    $error = 'Карточка не найдена или уже удалена.';
                }
            } else {
                $error = 'Неизвестное действие для карточки базы.';
            }
        } elseif ($entity === 'system') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action !== 'clear_cache') {
                $error = 'Неизвестное системное действие.';
            } else {
                $cacheConfig = is_array($config['cache'] ?? null) ? $config['cache'] : [];
                $cachePrefix = trim((string) ($cacheConfig['prefix'] ?? 'baza2'));
                if ($cachePrefix === '') {
                    $cachePrefix = 'baza2';
                }
                $removed = adminClearCacheFiles($cachePrefix);
                $notice = 'Кэш очищен. Удалено файлов: ' . $removed . '.';
            }
        } else {
            $error = 'Неизвестный тип действия.';
        }
    }
}

$editingBase = null;
if ($editingBaseId > 0) {
    $editStmt = $pdo->prepare(
        'SELECT
            b.*,
            bi.image_path AS cover_image
         FROM bases b
         LEFT JOIN base_images bi ON bi.base_id = b.id AND bi.is_cover = 1
         WHERE b.id = :id
         LIMIT 1'
    );
    $editStmt->execute([':id' => $editingBaseId]);
    $editingBase = $editStmt->fetch();
    if (!is_array($editingBase)) {
        $editingBase = null;
        $editingBaseId = 0;
    }
}

$baseForm = [
    'id' => (int) ($editingBase['id'] ?? 0),
    'name' => (string) ($editingBase['name'] ?? ''),
    'short_description' => (string) ($editingBase['short_description'] ?? ''),
    'description' => (string) ($editingBase['description'] ?? ''),
    'address' => (string) ($editingBase['address'] ?? ''),
    'district' => (string) ($editingBase['district'] ?? 'Раменский район'),
    'phone' => (string) ($editingBase['phone'] ?? ''),
    'booking_url' => (string) ($editingBase['booking_url'] ?? ''),
    'price_from' => (int) ($editingBase['price_from'] ?? 0),
    'price_to' => (int) ($editingBase['price_to'] ?? 0),
    'status' => (string) ($editingBase['status'] ?? 'draft'),
    'cover_image' => (string) ($editingBase['cover_image'] ?? ''),
];

$basesStmt = $pdo->query(
    'SELECT
        b.id,
        b.name,
        b.status,
        b.price_from,
        b.price_to,
        b.updated_at,
        COALESCE(rs.review_count, 0) AS review_count
     FROM bases b
     LEFT JOIN (
        SELECT base_id, COUNT(*) AS review_count
        FROM reviews
        WHERE status = "published"
        GROUP BY base_id
     ) rs ON rs.base_id = b.id
     ORDER BY FIELD(b.status, "published", "draft", "archived"), b.id DESC
     LIMIT 300'
);
$bases = $basesStmt->fetchAll();
$totalBasesCount = count($bases);
$publishedBasesCount = 0;
$draftBasesCount = 0;
foreach ($bases as $baseRow) {
    $status = (string) ($baseRow['status'] ?? '');
    if ($status === 'published') {
        $publishedBasesCount++;
    } elseif ($status === 'draft') {
        $draftBasesCount++;
    }
}

$savedFlag = (string) ($_GET['saved'] ?? '');
if ($savedFlag === '1' && $error === '') {
    $notice = 'Карточка успешно сохранена.';
}
$deletedFlag = (string) ($_GET['deleted'] ?? '');
if ($deletedFlag === '1' && $error === '') {
    $notice = 'Карточка удалена.';
}

$stmt = $pdo->query(
    'SELECT
        r.id,
        r.author_name,
        r.rating,
        r.review_text,
        r.status,
        r.created_at,
        b.id AS base_id,
        b.name AS base_name
     FROM reviews r
     JOIN bases b ON b.id = r.base_id
     ORDER BY
        FIELD(r.status, "pending", "published", "rejected"),
        r.id DESC
     LIMIT 200'
);
$reviews = $stmt->fetchAll();
$pendingReviewsCount = 0;
foreach ($reviews as $reviewRow) {
    if ((string) ($reviewRow['status'] ?? '') === 'pending') {
        $pendingReviewsCount++;
    }
}

$statusLabels = [
    'published' => 'опубликовано',
    'draft' => 'черновик',
    'archived' => 'архив',
];
$reviewStatusLabels = [
    'pending' => 'ожидает',
    'published' => 'опубликован',
    'rejected' => 'отклонен',
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= yandexMetrikaCounterHtml() ?>
    <title>Админ-панель</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= e(assetUrl('/public/assets/app.css')) ?>">
</head>
<body>
<header class="header">
    <div class="container header-row">
        <a class="brand" href="/">Базы отдыха</a>
        <div class="admin-topbar">
            <p class="sub">Админ-панель</p>
            <span class="muted"><?= e((string) ($admin['email'] ?? '')) ?></span>
            <a class="back-link" href="/admin/logout.php">Выйти</a>
        </div>
    </div>
</header>

<main class="container admin-shell dashboard-shell">
    <section class="panel">
        <h1>Админ-панель</h1>
        <?php if ($notice !== ''): ?><p class="notice" role="status" aria-live="polite"><?= e($notice) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="error" role="alert" aria-live="assertive"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="admin-inline-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="entity" value="system">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit">Очистить кэш сайта</button>
        </form>
        <div class="data-grid admin-data-grid">
            <div class="data-item">
                <span class="data-label">Карточек</span>
                <strong class="data-value"><?= e((string) $totalBasesCount) ?></strong>
            </div>
            <div class="data-item">
                <span class="data-label">Опубликовано</span>
                <strong class="data-value"><?= e((string) $publishedBasesCount) ?></strong>
            </div>
            <div class="data-item">
                <span class="data-label">Черновики</span>
                <strong class="data-value"><?= e((string) $draftBasesCount) ?></strong>
            </div>
            <div class="data-item">
                <span class="data-label">Ожидают отзывов</span>
                <strong class="data-value"><?= e((string) $pendingReviewsCount) ?></strong>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Карточки баз: создание и публикация</h2>
        <p class="muted">Статусы: <strong>draft</strong> (черновик), <strong>published</strong> (в каталоге), <strong>archived</strong> (скрыта).</p>

        <form method="post" class="review-form admin-base-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="entity" value="base">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="base_id" value="<?= e((string) $baseForm['id']) ?>">

            <label>Название
                <input type="text" name="name" maxlength="180" required value="<?= e((string) $baseForm['name']) ?>">
            </label>

            <label>Короткое описание
                <textarea name="short_description" maxlength="1400" required><?= e((string) $baseForm['short_description']) ?></textarea>
            </label>

            <label>Полное описание
                <textarea name="description" maxlength="4000"><?= e((string) $baseForm['description']) ?></textarea>
            </label>

            <label>Адрес
                <input type="text" name="address" maxlength="255" required value="<?= e((string) $baseForm['address']) ?>">
            </label>

            <label>Район
                <input type="text" name="district" maxlength="120" value="<?= e((string) $baseForm['district']) ?>">
            </label>

            <label>Телефон
                <input type="text" name="phone" maxlength="50" value="<?= e((string) $baseForm['phone']) ?>">
            </label>

            <label>Ссылка на сайт/бронирование
                <input type="url" name="booking_url" maxlength="500" value="<?= e((string) $baseForm['booking_url']) ?>">
            </label>

            <label>Путь к обложке (например /public/assets/placeholder.svg)
                <input type="text" name="cover_image" maxlength="500" value="<?= e((string) $baseForm['cover_image']) ?>">
            </label>

            <label>Цена от
                <input type="number" name="price_from" min="0" value="<?= e((string) $baseForm['price_from']) ?>">
            </label>

            <label>Цена до
                <input type="number" name="price_to" min="0" value="<?= e((string) $baseForm['price_to']) ?>">
            </label>

            <label>Статус
                <select name="status">
                    <option value="draft" <?= $baseForm['status'] === 'draft' ? 'selected' : '' ?>>draft</option>
                    <option value="published" <?= $baseForm['status'] === 'published' ? 'selected' : '' ?>>published</option>
                    <option value="archived" <?= $baseForm['status'] === 'archived' ? 'selected' : '' ?>>archived</option>
                </select>
            </label>

            <button type="submit"><?= $baseForm['id'] > 0 ? 'Сохранить карточку' : 'Создать карточку' ?></button>
            <?php if ($baseForm['id'] > 0): ?>
                <a class="back-link" href="/admin/index.php">Создать новую</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Список карточек</h2>
        <?php if ($bases === []): ?>
            <p class="muted">Карточек пока нет.</p>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($bases as $base): ?>
                    <article class="review-item">
                        <p class="muted admin-row-start">ID: <?= e((string) $base['id']) ?> · обновлено <?= e(formatReviewDate((string) $base['updated_at'])) ?></p>
                        <p>
                            <strong><?= e((string) $base['name']) ?></strong>
                            · <span class="status-chip status-<?= e((string) $base['status']) ?>"><?= e((string) ($statusLabels[(string) $base['status']] ?? (string) $base['status'])) ?></span>
                            · отзывы: <?= e((string) $base['review_count']) ?>
                        </p>
                        <p class="price"><?= e(formatPriceRange((int) $base['price_from'], (int) $base['price_to'])) ?></p>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="entity" value="base">
                            <input type="hidden" name="action" value="set_status">
                            <input type="hidden" name="base_id" value="<?= e((string) $base['id']) ?>">
                            <input type="hidden" name="status" value="published">
                            <button type="submit">Опубликовать</button>
                        </form>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="entity" value="base">
                            <input type="hidden" name="action" value="set_status">
                            <input type="hidden" name="base_id" value="<?= e((string) $base['id']) ?>">
                            <input type="hidden" name="status" value="draft">
                            <button type="submit">В черновик</button>
                        </form>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="entity" value="base">
                            <input type="hidden" name="action" value="set_status">
                            <input type="hidden" name="base_id" value="<?= e((string) $base['id']) ?>">
                            <input type="hidden" name="status" value="archived">
                            <button type="submit">В архив</button>
                        </form>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="entity" value="base">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="base_id" value="<?= e((string) $base['id']) ?>">
                            <button
                                type="submit"
                                onclick="return confirm('Удалить карточку и связанные данные (отзывы, изображения, удобства)? Действие необратимо.');"
                            >Удалить карточку</button>
                        </form>
                        <p class="admin-links">
                            <a class="back-link" href="/admin/index.php?edit_base=<?= e((string) $base['id']) ?>">Редактировать</a>
                            <a class="back-link" href="<?= e(baseUrl(['id' => $base['id'], 'name' => $base['name']])) ?>" target="_blank" rel="noopener">Открыть на сайте</a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Модерация отзывов</h2>
        <?php if ($reviews === []): ?>
            <p class="muted">Отзывов пока нет.</p>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <article class="review-item">
                        <p class="admin-row-start">
                            <strong><?= e((string) $review['author_name']) ?></strong>
                            · <span class="star-icon" aria-hidden="true">★</span> <?= e((string) $review['rating']) ?>
                            · <span class="status-chip status-<?= e((string) $review['status']) ?>"><?= e((string) ($reviewStatusLabels[(string) $review['status']] ?? (string) $review['status'])) ?></span>
                        </p>
                        <p><?= nl2br(e((string) $review['review_text'])) ?></p>
                        <p class="muted">
                            База: <a href="<?= e(baseUrl(['id' => $review['base_id'], 'name' => $review['base_name']])) ?>" target="_blank" rel="noopener"><?= e((string) $review['base_name']) ?></a>
                            · <?= e(formatReviewDate((string) $review['created_at'])) ?>
                        </p>
                        <form method="post" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="entity" value="review">
                            <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">
                            <button type="submit" name="action" value="publish">Опубликовать</button>
                            <button type="submit" name="action" value="reject">Отклонить</button>
                            <button type="submit" name="action" value="pending">Вернуть в ожидание</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
