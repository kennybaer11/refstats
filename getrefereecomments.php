<?php
header('Content-Type: application/json');

// --- Database config ---
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

// --- Connect to MySQL ---
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// --- Input: referee_id ---
$referee_id = isset($_GET['referee_id']) ? trim($_GET['referee_id']) : null;

try {
    if ($referee_id) {
        // Fetch only comments for this referee
        $stmt = $pdo->prepare("SELECT 
            comment_id, 
            referee_id, 
            COALESCE(user_name, 'Anonymous') AS user_name, 
            rating, 
            comment, 
            created_at, 
            approved,
            ip_address 
        FROM referee_comments
        WHERE referee_id = :referee_id
        ORDER BY created_at DESC");
        $stmt->execute(['referee_id' => $referee_id]);
    } else {
        // Fetch all comments if no referee_id is provided
        $stmt = $pdo->query("SELECT 
            comment_id, 
            referee_id, 
            COALESCE(user_name, 'Anonymous') AS user_name, 
            rating, 
            comment, 
            created_at, 
            approved,
            ip_address 
        FROM referee_comments
        ORDER BY created_at DESC");
    }

    $comments = $stmt->fetchAll();
    echo json_encode($comments);

} catch (\PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch comments: ' . $e->getMessage()]);
}
