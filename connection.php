<?php
$dsn = '';
$host = '167.234.236.40';
$port = '3306';
$user = 'panico4420';
$password = '*Fera123456';
$database = 'db_estoque';

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo "Falha na conexão: " . $e->getMessage() . '<br><br>';
}
?>