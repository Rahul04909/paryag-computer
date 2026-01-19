<?php
require_once '../database/db_config.php';

try {
    // Create typing_levels table
    $sql = "CREATE TABLE IF NOT EXISTS typing_levels (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        level_name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'typing_levels' created successfully.<br>";

    // Insert Default Levels
    $defaults = ['Easy', 'Medium', 'Hard', 'Expert'];
    $stmt = $conn->prepare("INSERT INTO typing_levels (level_name) VALUES (:name)");
    
    foreach ($defaults as $level) {
        // Check if exists
        $check = $conn->prepare("SELECT COUNT(*) FROM typing_levels WHERE level_name = :name");
        $check->execute(['name' => $level]);
        if ($check->fetchColumn() == 0) {
            $stmt->execute(['name' => $level]);
            echo "Inserted default level: $level<br>";
        } else {
            echo "Level $level already exists.<br>";
        }
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
