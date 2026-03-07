<?php

declare(strict_types=1);

function fetchAmenityFilters(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT slug, name
         FROM amenities
         ORDER BY
            CASE slug
                WHEN "wifi" THEN 1
                WHEN "banya" THEN 2
                WHEN "beach" THEN 3
                WHEN "restaurant" THEN 4
                WHEN "fishing" THEN 5
                WHEN "parking" THEN 6
                WHEN "kids" THEN 7
                ELSE 100
            END,
            name ASC'
    );
    $items = $stmt->fetchAll();
    $filters = [];
    foreach ($items as $row) {
        $slug = (string) ($row['slug'] ?? '');
        $name = (string) ($row['name'] ?? '');
        if ($slug === '' || $name === '') {
            continue;
        }
        $filters[$slug] = ['label' => $name];
    }
    return $filters;
}

function normalizeTypes(array $types, array $filters): array
{
    $normalized = [];
    foreach ($types as $type) {
        $slug = trim((string) $type);
        if ($slug === '' || !array_key_exists($slug, $filters)) {
            continue;
        }
        $normalized[$slug] = true;
    }
    return array_keys($normalized);
}

function normalizeSearchQuery(string $query): string
{
    $value = trim($query);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    if ($value === '') {
        return '';
    }

    return function_exists('mb_substr') ? mb_substr($value, 0, 120, 'UTF-8') : substr($value, 0, 120);
}

function searchTerms(string $query): array
{
    $normalized = normalizeSearchQuery($query);
    if ($normalized === '') {
        return [];
    }

    $parts = preg_split('/[\s,.;:!?()\[\]"\']+/u', $normalized) ?: [];
    $terms = [];
    foreach ($parts as $part) {
        $term = trim((string) $part);
        if ($term === '') {
            continue;
        }
        $term = function_exists('mb_substr') ? mb_substr($term, 0, 60, 'UTF-8') : substr($term, 0, 60);
        $terms[$term] = true;
        if (count($terms) >= 6) {
            break;
        }
    }

    return array_keys($terms);
}

function fetchBases(PDO $pdo, string $query, array $types, string $sort, int $page, int $perPage = 8): array
{
    $where = ['b.status = :status'];
    $params = [':status' => 'published'];
    $search = normalizeSearchQuery($query);
    $terms = searchTerms($search);

    if ($search !== '') {
        $searchNameParam = ':search_name';
        $searchShortParam = ':search_short';
        $searchAddressParam = ':search_address';
        $searchAmenityParam = ':search_amenity';
        $where[] = '(
            b.name LIKE ' . $searchNameParam . '
            OR b.short_description LIKE ' . $searchShortParam . '
            OR b.address LIKE ' . $searchAddressParam . '
            OR EXISTS (
                SELECT 1
                FROM base_amenities ba_search
                JOIN amenities a_search ON a_search.id = ba_search.amenity_id
                WHERE ba_search.base_id = b.id
                  AND a_search.name LIKE ' . $searchAmenityParam . '
            )
        )';
        $params[$searchNameParam] = '%' . $search . '%';
        $params[$searchShortParam] = '%' . $search . '%';
        $params[$searchAddressParam] = '%' . $search . '%';
        $params[$searchAmenityParam] = '%' . $search . '%';
    }

    foreach ($terms as $idx => $term) {
        $termNameParam = ':search_term_name_' . $idx;
        $termShortParam = ':search_term_short_' . $idx;
        $termAddressParam = ':search_term_address_' . $idx;
        $termAmenityParam = ':search_term_amenity_' . $idx;
        $where[] = '(
            b.name LIKE ' . $termNameParam . '
            OR b.short_description LIKE ' . $termShortParam . '
            OR b.address LIKE ' . $termAddressParam . '
            OR EXISTS (
                SELECT 1
                FROM base_amenities ba_term
                JOIN amenities a_term ON a_term.id = ba_term.amenity_id
                WHERE ba_term.base_id = b.id
                  AND a_term.name LIKE ' . $termAmenityParam . '
            )
        )';
        $params[$termNameParam] = '%' . $term . '%';
        $params[$termShortParam] = '%' . $term . '%';
        $params[$termAddressParam] = '%' . $term . '%';
        $params[$termAmenityParam] = '%' . $term . '%';
    }

    foreach (array_values($types) as $idx => $type) {
        $paramKey = ':amenity_slug_' . $idx;
        $where[] = 'EXISTS (
            SELECT 1
            FROM base_amenities ba_filter
            JOIN amenities a_filter ON a_filter.id = ba_filter.amenity_id
            WHERE ba_filter.base_id = b.id
              AND a_filter.slug = ' . $paramKey . '
        )';
        $params[$paramKey] = $type;
    }

    $searchScoreSql = '0';
    if ($sort === 'price_asc') {
        $orderBy = 'b.price_from ASC, b.id DESC';
    } elseif ($sort === 'price_desc') {
        $orderBy = 'b.price_from DESC, b.id DESC';
    } elseif ($sort === 'rating_desc') {
        $orderBy = 'COALESCE(rs.avg_rating, 0) DESC, rs.review_count DESC, b.id DESC';
    } elseif ($sort === 'newest') {
        $orderBy = 'b.id DESC';
    } else {
        if ($search !== '') {
            $scoreNameParam = ':score_name';
            $scoreShortParam = ':score_short';
            $scoreAddressParam = ':score_address';
            $searchScoreSql = '(
                (CASE WHEN b.name LIKE ' . $scoreNameParam . ' THEN 120 ELSE 0 END) +
                (CASE WHEN b.short_description LIKE ' . $scoreShortParam . ' THEN 40 ELSE 0 END) +
                (CASE WHEN b.address LIKE ' . $scoreAddressParam . ' THEN 25 ELSE 0 END)
            )';
            $params[$scoreNameParam] = '%' . $search . '%';
            $params[$scoreShortParam] = '%' . $search . '%';
            $params[$scoreAddressParam] = '%' . $search . '%';
            $orderBy = 'search_score DESC, COALESCE(rs.avg_rating, 0) DESC, rs.review_count DESC, b.id DESC';
        } else {
            $orderBy = 'b.id DESC';
        }
    }

    $whereSql = implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*)
        FROM bases b
        WHERE ' . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    $countParams = $params;
    unset($countParams[':score_name'], $countParams[':score_short'], $countParams[':score_address']);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $safePage = min(max(1, $page), $totalPages);
    $offset = ($safePage - 1) * $perPage;

    $sql = 'SELECT
            b.id,
            b.name,
            b.short_description,
            b.address,
            b.lat,
            b.lng,
            b.phone,
            b.price_from,
            b.price_to,
            b.booking_url,
            bi.image_path AS cover_image,
            COALESCE(rs.avg_rating, 0) AS avg_rating,
            COALESCE(rs.review_count, 0) AS review_count,
            COALESCE(am.amenities, "") AS amenities,
            ' . $searchScoreSql . ' AS search_score
        FROM bases b
        LEFT JOIN (
            SELECT base_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM reviews
            WHERE status = :review_status
            GROUP BY base_id
        ) rs ON rs.base_id = b.id
        LEFT JOIN (
            SELECT ba.base_id, GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ", ") AS amenities
            FROM base_amenities ba
            JOIN amenities a ON a.id = ba.amenity_id
            GROUP BY ba.base_id
        ) am ON am.base_id = b.id
        LEFT JOIN base_images bi ON bi.base_id = b.id AND bi.is_cover = 1
        WHERE ' . $whereSql . '
        ORDER BY ' . $orderBy . '
        LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':review_status', 'published', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'page' => $safePage,
    ];
}

function fetchRecentReviewsShowcase(PDO $pdo, int $limit = 8): array
{
    $safeLimit = max(1, min(20, $limit));
    $stmt = $pdo->prepare(
        'SELECT
            r.author_name,
            r.review_text,
            r.rating,
            b.id,
            b.name,
            b.address,
            b.lat,
            b.lng,
            COALESCE(bi.image_path, "/public/assets/placeholder.svg") AS cover_image
         FROM reviews r
         JOIN bases b ON b.id = r.base_id
         LEFT JOIN base_images bi ON bi.base_id = b.id AND bi.is_cover = 1
         WHERE r.status = :review_status AND b.status = :base_status
         ORDER BY r.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':review_status', 'published', PDO::PARAM_STR);
    $stmt->bindValue(':base_status', 'published', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $safeLimit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetchBaseBySlug(PDO $pdo, string $slug): ?array
{
    if (preg_match('/-(\d+)$/', $slug, $matches) !== 1) {
        return null;
    }
    $id = (int) $matches[1];
    if ($id < 1) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT
            b.id,
            b.name,
            b.short_description,
            b.description,
            b.address,
            b.phone,
            b.price_from,
            b.price_to,
            b.booking_url,
            b.lat,
            b.lng,
            b.updated_at
        FROM bases b
        WHERE b.id = :id AND b.status = :status
        LIMIT 1'
    );
    $stmt->execute([
        ':id' => $id,
        ':status' => 'published',
    ]);
    $base = $stmt->fetch();
    if (!is_array($base) || baseSlug($base) !== $slug) {
        return null;
    }

    $amenitiesStmt = $pdo->prepare(
        'SELECT a.name
         FROM base_amenities ba
         JOIN amenities a ON a.id = ba.amenity_id
         WHERE ba.base_id = :base_id
         ORDER BY a.name'
    );
    $amenitiesStmt->execute([':base_id' => $id]);
    $base['amenities'] = array_map(
        static fn (array $row): string => (string) $row['name'],
        $amenitiesStmt->fetchAll()
    );

    $imagesStmt = $pdo->prepare(
        'SELECT image_path, alt_text, sort_order
         FROM base_images
         WHERE base_id = :base_id
         ORDER BY is_cover DESC, sort_order ASC, id ASC'
    );
    $imagesStmt->execute([':base_id' => $id]);
    $base['images'] = $imagesStmt->fetchAll();

    return $base;
}

function fetchReviewStats(PDO $pdo, int $baseId): array
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS avg_rating
         FROM reviews
         WHERE base_id = :base_id AND status = :status'
    );
    $stmt->execute([
        ':base_id' => $baseId,
        ':status' => 'published',
    ]);
    $row = $stmt->fetch();
    return [
        'review_count' => (int) ($row['review_count'] ?? 0),
        'avg_rating' => (float) ($row['avg_rating'] ?? 0.0),
    ];
}

function fetchReviews(PDO $pdo, int $baseId, int $limit = 10): array
{
    $stmt = $pdo->prepare(
        'SELECT author_name, rating, review_text, created_at
         FROM reviews
         WHERE base_id = :base_id AND status = :status
         ORDER BY id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':base_id', $baseId, PDO::PARAM_INT);
    $stmt->bindValue(':status', 'published', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
