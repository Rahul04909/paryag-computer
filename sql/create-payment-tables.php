<?php
require_once '../database/db_config.php';

try {
    // Create bank_settings table
    $sql = "CREATE TABLE IF NOT EXISTS bank_settings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        bank_name VARCHAR(255) NOT NULL,
        account_no VARCHAR(255) NOT NULL,
        holder_name VARCHAR(255) NOT NULL,
        ifsc_code VARCHAR(50) NOT NULL,
        branch_address TEXT NOT NULL,
        qr_code_1 VARCHAR(255) NOT NULL,
        qr_code_2 VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'bank_settings' created successfully.<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
