<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$pdo = db($config);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности формы. Обновите страницу.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Введите email и пароль.';
        } else {
            $throttle = adminLoginThrottleStatus($config, $email);
            if (!$throttle['allowed']) {
                $waitSeconds = max(1, (int) ($throttle['retry_after'] ?? 0));
                $error = 'Слишком много попыток входа. Повторите через ' . $waitSeconds . ' сек.';
            } elseif (adminLogin($pdo, $email, $password, $config)) {
                header('Location: /admin/index.php');
                exit;
            } else {
                $error = 'Неверный email или пароль.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= yandexMetrikaCounterHtml() ?>
    <title>Вход в админ-панель</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= e(assetUrl('/public/assets/app.css')) ?>">
</head>
<body>
<header class="header">
    <div class="container header-row">
        <a class="brand" href="/">Базы отдыха</a>
        <p class="sub">Панель управления контентом</p>
    </div>
</header>

<main class="container auth-shell dashboard-shell">
    <section class="panel auth-panel">
        <h1>Вход в админ-панель</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert" aria-live="assertive"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="post" class="review-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Email
                <input type="email" name="email" required placeholder="admin@ramenskoye.local">
            </label>
            <label>Пароль
                <input type="password" name="password" required placeholder="••••••••">
            </label>
            <button type="submit">Войти</button>
        </form>
        <p class="muted">После входа вы сможете модерировать отзывы и управлять карточками баз.</p>
    </section>
</main>
</body>
</html>
