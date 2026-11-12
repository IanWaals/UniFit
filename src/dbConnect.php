<?php

$host = 'localhost';
$port = '5432';
$dbname = 'postgres';

$username = 'postgres.your-tenant-id';
$password = 'fbdff60be23cbbb4171c975423a00eb9';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
