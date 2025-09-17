<?php
header('Content-Type: application/json');

$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset($charset);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$query = isset($_GET['q']) ? $_GET['q'] : '';
$query = $conn->real_escape_string($query);

$results = [];

// Example: news search
$res1 = $conn->query("SELECT id, title FROM news WHERE title LIKE '%$query%' OR content LIKE '%$query%' LIMIT 50");
while ($r = $res1->fetch_assoc()) $results[] = ['type'=>'news', 'id'=>$r['id'], 'title'=>$r['title']];

// Example: referees search
$res2 = $conn->query("SELECT referee_id, referee_name FROM refereessql WHERE referee_name LIKE '%$query%' LIMIT 50");
while ($r = $res2->fetch_assoc()) $results[] = ['type'=>'referee', 'id'=>$r['referee_id'], 'name'=>$r['referee_name']];

// Example: pairs search
$res3 = $conn->query("SELECT pair_id, pair_name FROM refereepairsql WHERE pair_name LIKE '%$query%' LIMIT 50");
while ($r = $res3->fetch_assoc()) $results[] = ['type'=>'pair', 'id'=>$r['pair_id'], 'name'=>$r['pair_name']];

// Example: teams search
$res4 = $conn->query("SELECT team_id, team_name FROM teamssql WHERE team_name LIKE '%$query%' LIMIT 50");
while ($r = $res4->fetch_assoc()) $results[] = ['type'=>'team', 'id'=>$r['team_id'], 'name'=>$r['team_name']];

// Example: seasons search
$res5 = $conn->query("SELECT season_id, season_name FROM seasonssql WHERE season_name LIKE '%$query%' LIMIT 50");
while ($r = $res5->fetch_assoc()) $results[] = ['type'=>'season', 'id'=>$r['season_id'], 'name'=>$r['season_name']];

// Example: phase search
$res6 = $conn->query("SELECT phase_id, phase_name FROM phasesql WHERE phase_name LIKE '%$query%' LIMIT 50");
while ($r = $res6->fetch_assoc()) $results[] = ['type'=>'phase', 'id'=>$r['phase_id'], 'name'=>$r['phase_name']];

// Example: phase detail search
$res7 = $conn->query("SELECT phasedetail_id, phasedetail_name FROM phasedetailsql WHERE phasedetail_name LIKE '%$query%' LIMIT 50");
while ($r = $res7->fetch_assoc()) $results[] = ['type'=>'phasedetail', 'id'=>$r['phasedetail_id'], 'name'=>$r['phasedetail_name']];

// Example: mutual search
$res8 = $conn->query("SELECT mutual_id, mutual_name FROM mutualsql WHERE mutual_name LIKE '%$query%' LIMIT 50");
while ($r = $res8->fetch_assoc()) $results[] = ['type'=>'mutual', 'id'=>$r['mutual_id'], 'name'=>$r['mutual_name']];

// Example: competition search
$res9 = $conn->query("SELECT competition_id, competition_name FROM competitionsql WHERE competition_name LIKE '%$query%' LIMIT 50");
while ($r = $res9->fetch_assoc()) $results[] = ['type'=>'competition', 'id'=>$r['competition_id'], 'name'=>$r['competition_name']];

// Example: comments search
$res10 = $conn->query("SELECT comment_id, referee_id, user_name, rating, comment FROM referee_comments WHERE comment LIKE '%$query%' or user_name LIKE '%$query%' LIMIT 50");
while ($r = $res10->fetch_assoc()) $results[] = ['type'=>'comments', 'id'=>$r['comment_id'], 'name'=>$r['comment']];

echo json_encode($results);
