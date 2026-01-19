<?php
$host = 'localhost';
$db_name = 'jghfrodu_paryag_computer';
$username = 'jghfrodu_paryag_computer';
$password = 'Rd14072003@./';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; 
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?>
