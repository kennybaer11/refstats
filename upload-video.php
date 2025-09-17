<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$uploadDir = __DIR__ . "/uploads/videos/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!isset($_FILES["video"])) {
    echo json_encode(["error" => "No file uploaded"]);
    exit;
}

$file = $_FILES["video"];
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$allowed = ["mp4", "webm", "ogg"];

if (!in_array(strtolower($ext), $allowed)) {
    echo json_encode(["error" => "Invalid file type"]);
    exit;
}

// generate unique filename
$filename = uniqid("video_", true) . "." . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file["tmp_name"], $targetPath)) {
    $url = "https://beta.kenyschulz.com/referee/api/uploads/videos/" . $filename;
    echo json_encode(["url" => $url]);
} else {
    echo json_encode(["error" => "Failed to save file"]);
}
