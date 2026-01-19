<?php
require_once '../database/db_config.php';

try {
    // Create admins table
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'admins' created successfully.<br>";

    // Check if admin exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE username = :username");
    $check_stmt->execute(['username' => 'admin']);
    $count = $check_stmt->fetchColumn();

    if ($count == 0) {
        // Insert default admin
        $username = 'admin';
        $email = 'admin@example.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        
        $stmt->execute();
        echo "Default admin user created successfully.<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Admin user already exists.<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
