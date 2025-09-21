<?php
header('Content-Type: application/json');

// DB config - same as your sync.php
$host = 'localhost';  // Use localhost inside WEDOS hosting
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Allowed tables for safety
$allowedTables = [
    'refereessql',
    'matchesmysql',
    'competitionssql',
    'seasonssql',
    'news',
    // add others you want to expose
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Get 'table' from GET parameter, sanitize input
    $table = isset($_GET['table']) ? preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['table'])) : '';

    if (!in_array($table, $allowedTables)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing table parameter']);
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 100"); // limit for performance
    $rows = $stmt->fetchAll();

    echo json_encode($rows);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}