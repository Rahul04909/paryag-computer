<?php
require_once '../database/db_config.php';

try {
    // Create typing_tests table
    // test_type: Lesson or Practice Test
    // language_id: Foreign key to typing_languages (optional constraint logic here or app level)
    // level_id: Foreign key to typing_levels
    $sql = "CREATE TABLE IF NOT EXISTS typing_tests (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        test_title VARCHAR(255) NOT NULL,
        test_type VARCHAR(50) NOT NULL,
        language_id INT(11) NOT NULL,
        level_id INT(11) NOT NULL,
        duration_minutes INT(11) NOT NULL,
        test_content LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Attempt to add column if it doesn't exist (for existing tables)
    try {
        $conn->exec("ALTER TABLE typing_tests ADD COLUMN test_title VARCHAR(255) NOT NULL AFTER id");
    } catch (PDOException $e) {
        // Column likely exists
    }

    echo "Table 'typing_tests' updated/created successfully.<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
