<?php
$host = '127.0.0.1';
$port = 3306;
$db = 'attendance_system';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "TABLES: " . implode(',', $tables) . "\n";
    if (in_array('users', $tables, true)) {
        $count = $pdo->query('SELECT count(*) FROM users')->fetchColumn();
        echo "USERS: " . $count . "\n";
    }
    if (in_array('attendances', $tables, true)) {
        $count = $pdo->query('SELECT count(*) FROM attendances')->fetchColumn();
        echo "ATTENDANCES: " . $count . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
