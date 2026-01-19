<?php
require_once '../database/db_config.php';

try {
    // Add duration_minutes column to steno_tests table
    $sql = "ALTER TABLE steno_tests ADD COLUMN duration_minutes INT(11) NOT NULL AFTER test_title";
    $conn->exec($sql);
    echo "Column 'duration_minutes' added successfully to 'steno_tests'.<br>";

} catch(PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column name error code
         echo "Column 'duration_minutes' already exists.<br>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
