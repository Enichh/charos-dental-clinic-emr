<?php
require 'vendor/autoload.php'

use PDO; 
$dotenv = new Dotenv(__DIR__);
$dotenv->load();

$host = getenv('DB_HOST'); 
$db_name = getenv('DB_NAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

try{
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password
}
catch(PDOException $e){
    echo "Connection error: " . $e->getMessage();
}