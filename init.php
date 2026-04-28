<?php

declare(strict_types=1);

require 'src/helpers.php';
require 'src/env.php';

spl_autoload_register(function (string $class): void {
    if (class_exists($class, false)) {
        return;
    }

    $directories = [
        Helpers::getFullPath('src'),
        Helpers::getFullPath('src', 'classes'),
        Helpers::getFullPath('components'),
    ];

    foreach ($directories as $directory) {
        $file = $directory . DIRECTORY_SEPARATOR . $class . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});