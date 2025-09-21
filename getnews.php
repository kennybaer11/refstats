<?php
header('Content-Type: application/json');

// Database config
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset($charset);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Optional filters
$entityType = $_GET['entity_type'] ?? null;
$entityId   = $_GET['entity_id'] ?? null;

// Base query: news with tags
$sql = "SELECT n.id, n.title, n.content, n.created_at, n.banner,
               nt.entity_type, nt.entity_id
        FROM news n
        LEFT JOIN news_tags nt ON n.id = nt.news_id";

// Apply filter if provided
$params = [];
$types = '';
if ($entityType && $entityId) {
    $sql .= " WHERE nt.entity_type = ? AND nt.entity_id = ?";
    $types = 'ss';
    $params = [$entityType, $entityId];
}

$sql .= " ORDER BY n.created_at DESC";

// Prepare & execute
if ($stmt = $conn->prepare($sql)) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

// Collect news and attach tags
$news = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    if (!isset($news[$id])) {
        $news[$id] = [
            'id' => $id,
            'title' => $row['title'],
            'content' => $row['content'],
            'created_at' => $row['created_at'],
            'banner' => $row['banner'],
            'tags' => []
        ];
    }
    if ($row['entity_type']) {
        $news[$id]['tags'][] = [
            'entity_type' => $row['entity_type'],
            'entity_id' => $row['entity_id']
        ];
    }
}

echo json_encode(array_values($news));
$conn->close();
