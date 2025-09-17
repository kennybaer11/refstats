<?php
header('Content-Type: application/json');
require 'db.php'; // use your PDO connection code

$id = $_POST['comment_id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Missing comment ID']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM referee_comments WHERE comment_id = ?");
$success = $stmt->execute([$id]);

echo json_encode(['success' => $success]);
