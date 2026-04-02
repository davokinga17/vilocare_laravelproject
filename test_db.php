<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=vilocare_laraveldb', 'root', '');
    echo "Database connection successful!\n";
    
    $result = $pdo->query('SHOW TABLES');
    $tables = $result->fetchAll();
    echo "Tables found: " . count($tables) . "\n";
    print_r($tables);
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
