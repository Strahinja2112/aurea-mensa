<?php
require_once __DIR__ . '/autoload.php';

spl_autoload_register(function ($class) {
    echo "Trying to load class: $class\n";
    $directories = [
        __DIR__ . '/src',
        __DIR__ . '/src/classes',
        __DIR__ . '/src/components',
    ];

    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';
        echo "Checking file: $file\n";
        if (is_file($file)) {
            echo "Found file, requiring: $file\n";
            require_once $file;
            return;
        }
    }
    echo "File not found for class: $class\n";
}, true, true); // Prepend to the autoloader queue

// Test if DBConfig class is loaded
if (class_exists('DBConfig')) {
    echo "DBConfig class exists\n";
} else {
    echo "DBConfig class does not exist\n";
}
?>