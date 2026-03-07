<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

adminLogout();
header('Location: /admin/login.php');
exit;
