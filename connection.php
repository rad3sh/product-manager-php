<?php
$dsn = '';
$host = '';
$port = '3306';
$user = '';
$password = '';
$database = 'db_estoque';

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo "Falha na conexão: " . $e->getMessage() . '<br><br>';
}
?>