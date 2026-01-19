<?php
require_once '../database/db_config.php';

try {
    // Create smtp_settings table
    $sql = "CREATE TABLE IF NOT EXISTS smtp_settings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        host VARCHAR(255) NOT NULL,
        port INT(5) NOT NULL,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        encryption VARCHAR(20) NOT NULL,
        from_email VARCHAR(255) NOT NULL,
        from_name VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'smtp_settings' created successfully.<br>";

    // Check if settings exist, if not insert default
    $stmt = $conn->query("SELECT COUNT(*) FROM smtp_settings");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO smtp_settings (host, port, username, password, encryption, from_email, from_name) 
                VALUES ('smtp.example.com', 587, 'user@example.com', 'password', 'tls', 'no-reply@example.com', 'Typing Master')";
        $conn->exec($sql);
        echo "Default SMTP settings inserted.<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
