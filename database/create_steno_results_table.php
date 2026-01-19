<?php
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS steno_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT DEFAULT NULL,
        test_id INT NOT NULL,
        total_words INT DEFAULT 0,
        typed_words INT DEFAULT 0,
        mistakes INT DEFAULT 0,
        accuracy DECIMAL(5,2) DEFAULT 0.00,
        wpm INT DEFAULT 0,
        duration_taken INT DEFAULT 0 COMMENT 'In seconds',
        original_content_snapshot TEXT,
        typed_content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (student_id),
        INDEX (test_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->exec($sql);
    echo "Table 'steno_results' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
