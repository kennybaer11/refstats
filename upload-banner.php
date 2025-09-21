<?php
header('Content-Type: application/json');

// DB not needed here, just upload
if (!isset($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

// Validate file type (image only)
$allowed = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['banner']['type'], $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Upload directory
$uploadDir = 'uploads/news/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Generate unique filename
$filename = time() . '_' . basename($_FILES['banner']['name']);
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($_FILES['banner']['tmp_name'], $targetPath)) {
    echo json_encode(['success' => true, 'url' => $targetPath]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
}
