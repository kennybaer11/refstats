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

// Fetch all mutuals
$sql = "SELECT
    m.mutual_id,
    mu.mutual_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN ms.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN ms.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN ms.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN ms.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN ms.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN ms.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN mutualsql mu   ON m.mutual_id = mu.mutual_id
GROUP BY m.mutual_id
ORDER BY matches DESC";
$result = $conn->query($sql);

// Prepare response
$mutuals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mutuals[] = $row;
    }
}

// Return as JSON
echo json_encode($mutuals);
?>
