<?php
require_once '../database/db_config.php';

try {
    // Create typing_languages table
    $sql = "CREATE TABLE IF NOT EXISTS typing_languages (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        language_name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'typing_languages' created successfully.<br>";

    // Insert Default Languages (English, Hindi)
    $defaults = ['English', 'Hindi'];
    $stmt = $conn->prepare("INSERT INTO typing_languages (language_name) VALUES (:name)");
    
    foreach ($defaults as $lang) {
        // Check if exists
        $check = $conn->prepare("SELECT COUNT(*) FROM typing_languages WHERE language_name = :name");
        $check->execute(['name' => $lang]);
        if ($check->fetchColumn() == 0) {
            $stmt->execute(['name' => $lang]);
            echo "Inserted default language: $lang<br>";
        } else {
            echo "Language $lang already exists.<br>";
        }
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
