<?php
header('Content-Type: application/json');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database credentials
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

// Create MySQLi connection
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset($charset);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Parse JSON body
$data = json_decode(file_get_contents("php://input"), true);

if (
    !$data ||
    !isset($data['referee_id'], $data['user_name'], $data['comment'], $data['rating'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$referee_id = $conn->real_escape_string($data['referee_id']);
$user_name  = $conn->real_escape_string($data['user_name']);
$comment    = $conn->real_escape_string($data['comment']);
$rating     = (int)$data['rating'];
$ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Check if this user has already posted for this referee in the last 30 min
$checkStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM referee_comments 
    WHERE referee_id = ? 
      AND (user_name = ? OR ip_address = ?) 
      AND created_at >= (NOW() - INTERVAL 30 MINUTE)
");
$checkStmt->bind_param("sss", $referee_id, $user_name, $ip);
$checkStmt->execute();
$checkStmt->bind_result($count);
$checkStmt->fetch();
$checkStmt->close();

if ($count > 0) {
    http_response_code(429); // Too Many Requests
    echo json_encode(['error' => 'You can only post one comment every 30 minutes.']);
    exit;
}

// Insert into DB
// Insert into DB
$stmt = $conn->prepare("
    INSERT INTO referee_comments (referee_id, user_name, rating, comment, created_at, approved, ip_address)
    VALUES (?, ?, ?, ?, NOW(), 0, ?)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssiss", $referee_id, $user_name, $rating, $comment, $ip);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
