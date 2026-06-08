<?php

declare(strict_types=1);

require 'src/classes/Helpers.php';
require 'src/classes/Logger.php';

Logger::init();

require 'src/classes/Env.php';

spl_autoload_register(function (string $class): void {
    if (class_exists($class, false)) {
        return;
    }

    $directories = [
        Helpers::getRelativePath('src'),
        Helpers::getRelativePath('src/classes'),
        Helpers::getRelativePath('src/components'),
        Helpers::getRelativePath('src/enums'),
    ];


    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
