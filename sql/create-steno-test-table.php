<?php
require_once '../database/db_config.php';

try {
    // Create steno_tests table
    $sql = "CREATE TABLE IF NOT EXISTS steno_tests (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        category_id INT(11) NOT NULL,
        language_id INT(11) NOT NULL,
        test_title VARCHAR(255) NOT NULL,
        test_content TEXT NOT NULL,
        audio_file VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES steno_categories(id) ON DELETE CASCADE,
        FOREIGN KEY (language_id) REFERENCES typing_languages(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table 'steno_tests' created successfully.<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
