<?php

use PDO;
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();


$host     = getenv('DB_HOST');
$db_name  = getenv('DB_NAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');  

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
} catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}