<?php
require_once __DIR__ . '/autoload.php';

echo "Testing database configuration from environment variables:\n";
echo "DB_HOST: " . DBConfig::getDbHost() . "\n";
echo "DB_USER: " . DBConfig::getDbUser() . "\n";
echo "DB_PASSWORD: '" . DBConfig::getDbPassword() . "'\n";
echo "DB_NAME: " . DBConfig::getDbName() . "\n\n";

try {
    $db = Database::getNewOrExistingInstance();
    echo "Database connection successful!\n";
    
    // Test a simple query
    $stmt = $db->pdo()->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Test query result: " . $result['test'] . "\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>