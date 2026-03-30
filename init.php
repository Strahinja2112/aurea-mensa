<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once SERVER_PATH . '/helpers.php';

spl_autoload_register(function (string $class): void {
    $directories = [
        SERVER_PATH,
        COMPONENTS_PATH,
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';
        
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});