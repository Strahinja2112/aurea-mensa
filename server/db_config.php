<?php

declare(strict_types=1);

class DBConfig
{
    public static function getCredentials(): array
    {
        $hostname = $_SERVER['HTTP_HOST'] ?? '';
        
        if ($hostname === 'sn.stud.vts.su.ac.rs') {
            return [
                    'DB_HOST' => 'localhost',
                    'DB_USER' => 'sn',
                    'DB_PASSWORD' => '7v1zTOMzp3aHZ3g',
                    'DB_NAME' => 'sn',
            ];
        }
        
        return [
                'DB_HOST' => 'localhost',
                'DB_USER' => 'root',
                'DB_PASSWORD' => '',
                'DB_NAME' => 'Restaurant',
        ];
    }
    
    public static function getDbHost(): string
    {
        return self::getCredentials()['DB_HOST'];
    }
    
    public static function getDbUser(): string
    {
        return self::getCredentials()['DB_USER'];
    }
    
    public static function getDbPassword(): string
    {
        return self::getCredentials()['DB_PASSWORD'];
    }
    
    public static function getDbName(): string
    {
        return self::getCredentials()['DB_NAME'];
    }
}