<?php

declare(strict_types=1);

$env = parse_ini_file(BASE_PATH . '/.env', true);

class Env
{
    public static string $MAILTRAP_API_KEY;
}

Env::$MAILTRAP_API_KEY = $env['MAILTRAP_API_KEY'];