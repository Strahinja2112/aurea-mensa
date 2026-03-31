<?php

declare(strict_types=1);

$env = parse_ini_file(BASE_PATH . '/.env', true);

class Env
{
    public static string $MAILTRAP_API_KEY;
    public static string $MAILTRAP_DOMAIN;
    public static string $MAILTRAP_PASSWORD;
    public static string $MAILTRAP_USERNAME;
    public static string $MAILTRAP_PORT;
}

try {
    Env::$MAILTRAP_API_KEY = $env['MAILTRAP_API_KEY'];
    if (!Env::$MAILTRAP_API_KEY) {
        throw new Exception('Mailtrap API key is missing');
    }

    Env::$MAILTRAP_DOMAIN = $env['MAILTRAP_DOMAIN'];
    if (!Env::$MAILTRAP_DOMAIN) {
        throw new Exception('Mailtrap domain is missing');
    }

    Env::$MAILTRAP_PASSWORD = $env['MAILTRAP_PASSWORD'];
    if (!Env::$MAILTRAP_PASSWORD) {
        throw new Exception('Mailtrap password is missing');
    }

    Env::$MAILTRAP_USERNAME = $env['MAILTRAP_USERNAME'];
    if (!Env::$MAILTRAP_USERNAME) {
        throw new Exception('Mailtrap username is missing');
    }

    Env::$MAILTRAP_PORT = $env['MAILTRAP_PORT'];
    if (!Env::$MAILTRAP_PORT) {
        throw new Exception('Mailtrap port is missing');
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
    exit;
}
