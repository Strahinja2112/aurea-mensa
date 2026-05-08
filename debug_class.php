<?php
// Let's see what happens when we try to load DBConfig
echo "Before autoload: ";
if (class_exists('DBConfig', false)) {
    echo "DBConfig class already exists\n";
} else {
    echo "DBConfig class does not exist yet\n";
}

require_once __DIR__ . '/autoload.php';

echo "After autoload registration: ";
if (class_exists('DBConfig', false)) {
    echo "DBConfig class exists\n";
} else {
    echo "DBConfig class does not exist\n";
}

// Now let's try to use it
try {
    echo "DB_HOST: " . DBConfig::getDbHost() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>