<?php

declare(strict_types=1);

require_once __DIR__ . '/classes/Env.php';

class DBConfig
{
    public static function getCredentials(): array
    {
        return [
            'DB_HOST' => Env::$DB_HOST,
            'DB_USER' => Env::$DB_USER,
            'DB_PASSWORD' => Env::$DB_PASSWORD,
            'DB_NAME' => Env::$DB_NAME,
        ];
    }

    public static function getDbHost(): string
    {
        return Env::$DB_HOST;
    }

    public static function getDbUser(): string
    {
        return Env::$DB_USER;
    }

    public static function getDbPassword(): string
    {
        return Env::$DB_PASSWORD;
    }

    public static function getDbName(): string
    {
        return Env::$DB_NAME;
    }
}
