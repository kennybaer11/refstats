<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}
$conn->set_charset('utf8mb4');

$title   = $_POST['title']   ?? '';
$content = $_POST['content'] ?? '';
$tagsRaw = $_POST['tags']    ?? '[]';

if (!$title || !$content) {
    echo json_encode(['error' => 'Missing title or content']);
    exit;
}

// Decode tags JSON
$tags = json_decode($tagsRaw, true);
if (!is_array($tags)) $tags = [];

// Handle banner upload
$bannerPath = null;
if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/news/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = time() . '_' . basename($_FILES['banner']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['banner']['tmp_name'], $targetPath)) {
        $bannerPath = $targetPath;
    } else {
        echo json_encode(['error' => 'Failed to upload banner']);
        exit;
    }
}

// Insert into news table
$stmt = $conn->prepare("INSERT INTO news (title, content, created_at, banner) VALUES (?, ?, NOW(), ?)");
$stmt->bind_param('sss', $title, $content, $bannerPath);

if ($stmt->execute()) {
    $newsId = $stmt->insert_id;

    // Insert tags into news_tags table
    if (!empty($tags)) {
        $tagStmt = $conn->prepare("INSERT INTO news_tags (news_id, entity_type, entity_id) VALUES (?, ?, ?)");
        foreach ($tags as $tag) {
            $entityId   = $tag['id'] ?? null;
            $entityType = strtolower($tag['category']); // normalize category to entity_type
            $tagStmt->bind_param('iss', $newsId, $entityType, $entityId);
            $tagStmt->execute();
        }
        $tagStmt->close();
    }

    echo json_encode([
        'success' => true,
        'id' => $newsId,
        'banner' => $bannerPath,
        'tags' => $tags
    ]);
} else {
    echo json_encode(['error' => 'Insert failed']);
}

$stmt->close();
$conn->close();
