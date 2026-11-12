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

    $stmt = $pdo->query("SELECT * FROM students LIMIT 10");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Table Data:</h1>";
    echo "<pre>";
    print_r($rows);
    echo "</pre>";

} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

function dbConnect() {}
