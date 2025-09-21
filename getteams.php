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

// Force UTF-8
$conn->set_charset($charset);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch all teams
$sql = "SELECT team_id, team_name, team_logo FROM teamssql";
$result = $conn->query($sql);

// Prepare response
$teams = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
}

// Return as JSON
echo json_encode($teams);
?>
