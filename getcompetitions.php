<?php
header('Content-Type: application/json');

// Replace with your actual database credentials from Wedos
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch all competitions
$sql = "SELECT competition_id, competition_name FROM competitionsql";
$result = $conn->query($sql);

// Prepare response
$competitions = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $competitions[] = $row;
    }
}

// Return as JSON
echo json_encode($competitions);
?>
