<?php

declare(strict_types=1);

class Env
{
    public static string $MAILTRAP_API_KEY;
    public static string $MAILTRAP_DOMAIN;
    public static string $MAILTRAP_PASSWORD;
    public static string $MAILTRAP_USERNAME;
    public static string $MAILTRAP_PORT;
    
    public static function load(): void
    {
        $path = __DIR__ . '/../../.env';
        
        try {
            if (!file_exists($path)) {
                throw new Exception(".env file is missing at: " . $path);
            }
            
            $env = parse_ini_file($path, true);
            
            if (!$env) {
                throw new Exception("Failed to parse .env file!");
            }
            
            self::$MAILTRAP_API_KEY = $env['MAILTRAP_API_KEY'] ?? throw new Exception('Mailtrap API key is missing');
            self::$MAILTRAP_DOMAIN = $env['MAILTRAP_DOMAIN'] ?? throw new Exception('Mailtrap domain is missing');
            self::$MAILTRAP_PASSWORD = $env['MAILTRAP_PASSWORD'] ?? throw new Exception('Mailtrap password is missing');
            self::$MAILTRAP_USERNAME = $env['MAILTRAP_USERNAME'] ?? throw new Exception('Mailtrap username is missing');
            self::$MAILTRAP_PORT = $env['MAILTRAP_PORT'] ?? throw new Exception('Mailtrap port is missing');
        } catch (Exception $exception) {
            echo "Error: " . $exception->getMessage();
            exit;
        }
    }
}

Env::load();