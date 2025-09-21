<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';

if (!$title || !$content) {
    echo json_encode(['error' => 'Missing title or content']);
    exit;
}

// Handle banner
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

// Insert news
$stmt = $conn->prepare("INSERT INTO news (title, content, created_at, banner) VALUES (?, ?, NOW(), ?)");
$stmt->bind_param('sss', $title, $content, $bannerPath);

if (!$stmt->execute()) {
    echo json_encode(['error' => 'Insert failed']);
    exit;
}

$newsId = $stmt->insert_id;
$stmt->close();

// Insert tags
$tagTypes = ['referees' => 'referee', 'refereePairs' => 'referee_pair', 'competitions' => 'competition', 'teams' => 'team', 'mutuals' => 'mutual'];

foreach ($tagTypes as $field => $type) {
    if (!empty($_POST[$field])) {
        foreach ($_POST[$field] as $entityId) {
            $stmt = $conn->prepare("INSERT INTO news_tags (news_id, entity_type, entity_id) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $newsId, $type, $entityId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();
echo json_encode(['success' => true, 'id' => $newsId]);
