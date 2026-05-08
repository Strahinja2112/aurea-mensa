<?php

require 'src/db_config.php';

$dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        DBConfig::getDbHost(),
        DBConfig::getDbName()
);

$pdo = new PDO(
        $dsn,
        DBConfig::getDbUser(),
        DBConfig::getDbPassword(),
        [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
);

$users = $pdo->query("SELECT * FROM users");

var_dump($users);