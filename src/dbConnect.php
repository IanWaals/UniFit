<?php

$conn = dbConnect();

function dbConnect() {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'postgres';
    $username = 'postgres.your-tenant-id';
    $password = 'fbdff60be23cbbb4171c975423a00eb9';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;

    } catch (PDOException $e) {
        echo "Query failed: " . $e->getMessage();
    }
}

function getStudents() {
    $stsm = $conn->query("SELECT * FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($students);
}

?>
