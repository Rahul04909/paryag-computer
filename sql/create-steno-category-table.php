<?php
require_once '../database/db_config.php';

try {
    // Create steno_categories table
    $sql = "CREATE TABLE IF NOT EXISTS steno_categories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) NOT NULL UNIQUE,
        category_logo VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'steno_categories' created successfully.<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
