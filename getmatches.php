<?php
// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Database credentials
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset($charset);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Optional GET parameters
$mutualId = isset($_GET['mutual_id']) ? $conn->real_escape_string($_GET['mutual_id']) : null;
$teamId   = isset($_GET['team_id'])   ? $conn->real_escape_string($_GET['team_id'])   : null;
$limit    = isset($_GET['limit'])     ? (int)$_GET['limit'] : null;

// Base SQL
$sql = "SELECT 
    m.match_id AS match_id,
    c.competition_name AS competition_name,
    s.season_name AS season_name,
    r1.referee_name AS referee1_name,
    r2.referee_name AS referee2_name,
    p.pair_name AS pair_name,
    ph.phase_name AS phase_name,
    pd.phasedetail_name AS phasedetail_name,
    t1.team_name AS home_team,
    t2.team_name AS away_team,
    mu.mutual_id,
    mu.mutual_name AS mutual_name,
    ms.DateTime,
    ms.total_2min,
    ms.total_5min,
    ms.total_2min_home,
    ms.total_2min_away,
    ms.total_5min_home,
    ms.total_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
LEFT JOIN refereessql r1 ON m.ref1_id = r1.referee_id
LEFT JOIN refereessql r2 ON m.ref2_id = r2.referee_id
LEFT JOIN refereepairsql p ON m.pair_id = p.pair_id
LEFT JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql ph ON m.phase_id = ph.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
LEFT JOIN competitionsql c ON m.competition_id = c.competition_id
LEFT JOIN teamssql t1 ON m.hteam_id = t1.team_id
LEFT JOIN teamssql t2 ON m.ateam_id = t2.team_id
LEFT JOIN mutualsql mu ON m.mutual_id = mu.mutual_id";

// Build WHERE conditions
$conditions = [];
if ($mutualId) {
    $conditions[] = "m.mutual_id = '{$mutualId}'";
}
if ($teamId) {
    $conditions[] = "(m.hteam_id = '{$teamId}' OR m.ateam_id = '{$teamId}')";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Order by most recent matches
$sql .= " ORDER BY ms.DateTime DESC";

// Apply limit if requested
if ($limit) {
    $sql .= " LIMIT {$limit}";
}

$result = $conn->query($sql);

// Prepare response
$matches = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
}

// Return JSON
echo json_encode($matches);
