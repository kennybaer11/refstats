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
    echo json_encode([]);
    exit;
}

// Helper function to query tags
function fetchTags($conn, $table, $id, $valueCol, $category) {
    $res = $conn->query("SELECT $id, $valueCol FROM $table");
    $items = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = [
                'value' => $row[$valueCol],
                'id' => $row[$id],
                'category' => $category
            ];
        }
    }
    return $items;
}

// If news_id is provided, return only tags for that news
if (isset($_GET['news_id'])) {
    $news_id = intval($_GET['news_id']);
    $sql = "SELECT nt.entity_type, nt.entity_id,
                   CASE nt.entity_type
                     WHEN 'referee' THEN r.referee_name
                     WHEN 'referee_pair' THEN p.pair_name
                     WHEN 'team' THEN t.team_name
                     WHEN 'competition' THEN c.competition_name
                     WHEN 'mutual' THEN m.mutual_name
                   END as value
            FROM news_tags nt
            LEFT JOIN refereessql r ON nt.entity_type='referee' AND nt.entity_id = r.referee_id
            LEFT JOIN refereepairsql p ON nt.entity_type='referee_pair' AND nt.entity_id = p.pair_id
            LEFT JOIN teamssql t ON nt.entity_type='team' AND nt.entity_id = t.team_id
            LEFT JOIN competitionsql c ON nt.entity_type='competition' AND nt.entity_id = c.competition_id
            LEFT JOIN mutualsql m ON nt.entity_type='mutual' AND nt.entity_id = m.mutual_id
            WHERE nt.news_id = $news_id";

    $res = $conn->query($sql);
    $tags = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tags[] = [
                'id' => $row['entity_id'],
                'value' => $row['value'],
                'category' => ucfirst(str_replace('_', ' ', $row['entity_type']))
            ];
        }
    }
    echo json_encode($tags);
    $conn->close();
    exit;
}

// Otherwise, return all possible tags (for the add-news form)
$tags = array_merge(
    fetchTags($conn, 'refereessql', 'referee_id', 'referee_name', 'Referee'),
    fetchTags($conn, 'teamssql', 'team_id', 'team_name', 'Team'),
    fetchTags($conn, 'competitionsql', 'competition_id', 'competition_name', 'Competition'),
    fetchTags($conn, 'refereepairsql', 'pair_id', 'pair_name', 'Referee_Pair'),
    fetchTags($conn, 'mutualsql', 'mutual_id', 'mutual_name', 'Mutual')
);

echo json_encode($tags);
$conn->close();
