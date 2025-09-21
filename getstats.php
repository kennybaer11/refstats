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
$mutualId      = isset($_GET['mutual_id']) ? $conn->real_escape_string($_GET['mutual_id']) : null;
$teamId        = isset($_GET['team_id']) ? $conn->real_escape_string($_GET['team_id']) : null;
$competitionId = isset($_GET['competition_id']) ? $conn->real_escape_string($_GET['competition_id']) : null;
$seasonId      = isset($_GET['season_id']) ? $conn->real_escape_string($_GET['season_id']) : null;
$phaseId       = isset($_GET['phase_id']) ? $conn->real_escape_string($_GET['phase_id']) : null;
$phaseDetailId = isset($_GET['phasedetail_id']) ? $conn->real_escape_string($_GET['phasedetail_id']) : null;
$limit         = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

// Base SQL: fetch match-level data
$sql = "SELECT 
    m.match_id,
    c.competition_id,
    c.competition_name,
    s.season_id,
    s.season_name,
    ph.phase_id,
    ph.phase_name,
    pd.phasedetail_id,
    pd.phasedetail_name,
    t1.team_id AS home_team_id,
    t1.team_name AS home_team,
    t2.team_id AS away_team_id,
    t2.team_name AS away_team,
    r1.referee_id AS ref1_id,
    r1.referee_name AS referee1_name,
    r2.referee_id AS ref2_id,
    r2.referee_name AS referee2_name,
    p.pair_id,
    p.pair_name,
    p.ref1_id AS pair_ref1_id,
    r1_pair.referee_name AS pair_ref1_name,
    p.ref2_id AS pair_ref2_id,
    r2_pair.referee_name AS pair_ref2_name,
    mu.mutual_id,
    mu.mutual_name AS mutual_name,
    mu.team1_id AS mutual_team1_id,
    t3.team_name AS mutual_team1_name,
    mu.team2_id AS mutual_team2_id,
    t4.team_name AS mutual_team2_name,
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
LEFT JOIN refereessql r1_pair ON p.ref1_id = r1_pair.referee_id
LEFT JOIN refereessql r2_pair ON p.ref2_id = r2_pair.referee_id
LEFT JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql ph ON m.phase_id = ph.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
LEFT JOIN competitionsql c ON m.competition_id = c.competition_id
LEFT JOIN teamssql t1 ON m.hteam_id = t1.team_id
LEFT JOIN teamssql t2 ON m.ateam_id = t2.team_id
LEFT JOIN mutualsql mu ON m.mutual_id = mu.mutual_id
LEFT JOIN teamssql t3 ON mu.team1_id = t3.team_id
LEFT JOIN teamssql t4 ON mu.team2_id = t4.team_id";

// Build WHERE conditions dynamically
$conditions = [];
if ($mutualId)      $conditions[] = "m.mutual_id = '{$mutualId}'";
if ($teamId)        $conditions[] = "(m.hteam_id = '{$teamId}' OR m.ateam_id = '{$teamId}')";
if ($competitionId) $conditions[] = "m.competition_id = '{$competitionId}'";
if ($seasonId)      $conditions[] = "m.season_id = '{$seasonId}'";
if ($phaseId)       $conditions[] = "m.phase_id = '{$phaseId}'";
if ($phaseDetailId) $conditions[] = "m.phasedetail_id = '{$phaseDetailId}'";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Order by most recent matches
$sql .= " ORDER BY ms.DateTime DESC";

// Apply limit if requested
if ($limit) $sql .= " LIMIT {$limit}";

$result = $conn->query($sql);

// Prepare match data
$matches = [];
$teams = [];
$referees = [];
$pairs = [];
$seasons = [];
$phases = [];
$phaseDetails = [];
$mutuals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;

        // Collect unique teams
        if ($row['home_team_id']) $teams[$row['home_team_id']] = $row['home_team'];
        if ($row['away_team_id']) $teams[$row['away_team_id']] = $row['away_team'];

        // Collect unique referees
        if ($row['ref1_id']) $referees[$row['ref1_id']] = $row['referee1_name'];
        if ($row['ref2_id']) $referees[$row['ref2_id']] = $row['referee2_name'];

        // Collect unique pairs
        if ($row['pair_id']) $pairs[$row['pair_id']] = [
            'pair_name' => $row['pair_name'],
            'ref1_id' => $row['pair_ref1_id'],
            'ref1_name' => $row['pair_ref1_name'],
            'ref2_id' => $row['pair_ref2_id'],
            'ref2_name' => $row['pair_ref2_name']
        ];

        // Collect unique seasons
        if ($row['season_id']) $seasons[$row['season_id']] = $row['season_name'];

        // Collect unique phases
        if ($row['phase_id']) $phases[$row['phase_id']] = $row['phase_name'];

        // Collect unique phase details
        if ($row['phasedetail_id']) $phaseDetails[$row['phasedetail_id']] = $row['phasedetail_name'];

        // Collect unique mutuals
        if ($row['mutual_id']) $mutuals[$row['mutual_id']] = [
            'mutual_name' => $row['mutual_name'],
            'team1_id' => $row['mutual_team1_id'],
            'team1_name' => $row['mutual_team1_name'],
            'team2_id' => $row['mutual_team2_id'],
            'team2_name' => $row['mutual_team2_name']
        ];
    }
}

// Build final JSON response
$response = [
    'matches'      => $matches,
    'teams'        => array_map(fn($id,$name)=>['team_id'=>$id,'team_name'=>$name], array_keys($teams), $teams),
    'referees'     => array_map(fn($id,$name)=>['referee_id'=>$id,'referee_name'=>$name], array_keys($referees), $referees),
    'pairs'        => array_map(fn($id,$data)=>array_merge(['pair_id'=>$id], $data), array_keys($pairs), $pairs),
    'seasons'      => array_map(fn($id,$name)=>['season_id'=>$id,'season_name'=>$name], array_keys($seasons), $seasons),
    'phases'       => array_map(fn($id,$name)=>['phase_id'=>$id,'phase_name'=>$name], array_keys($phases), $phases),
    'phaseDetails' => array_map(fn($id,$name)=>['phasedetail_id'=>$id,'phasedetail_name'=>$name], array_keys($phaseDetails), $phaseDetails),
    'mutuals'      => array_map(fn($id,$data)=>array_merge(['mutual_id'=>$id], $data), array_keys($mutuals), $mutuals)
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
