<?php

declare(strict_types=1);

require_once __DIR__ . '/server/db_config.php';
require_once __DIR__ . '/server/Database.php';
require_once __DIR__ . '/server/Auth.php';

Auth::signOut();

header('Location: /login.php');
exit;