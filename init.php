<?php

declare(strict_types=1);

require 'config.php';
require 'helpers.php';
require 'env.php';

spl_autoload_register(function (string $class): void {
    $directories = [
        Helpers::getFullPath('server'),
        Helpers::getFullPath('server', 'classes'),
        Helpers::getFullPath('components'),
    ];

    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});