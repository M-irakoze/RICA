<?php
$path = __DIR__ . '/database/database.sqlite';
if (!file_exists($path)) {
    echo "MISSING\n";
    exit(1);
}
$pdo = new PDO('sqlite:' . $path);
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES: " . implode(',', $tables) . "\n";
$count = $pdo->query('SELECT count(*) FROM users')->fetchColumn();
echo "USERS: " . $count . "\n";
$data = $pdo->query('SELECT id, name, email, created_at FROM users LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as $row) {
    echo json_encode($row) . "\n";
}
