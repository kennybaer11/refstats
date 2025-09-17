<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Database credentials
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset($charset);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get filters
$competition_id = isset($_GET['competition_id']) ? $_GET['competition_id'] : null;
$season_id      = isset($_GET['season_id']) ? $_GET['season_id'] : null;
$phase_id       = isset($_GET['phase_id']) ? $_GET['phase_id'] : null;
$phasedetail_id = isset($_GET['phasedetail_id']) ? $_GET['phasedetail_id'] : null;

// Grouping
$allowed_groups = ['competition','season','phase','phasedetail'];
$group = isset($_GET['group']) && in_array($_GET['group'], $allowed_groups) ? $_GET['group'] : 'overall';

// Build SELECT parts depending on grouping
$select_parts = [];
$groupBy = ["m.pair_id","m.ref1_id","r1.referee_name","m.ref2_id","r2.referee_name"];

// Competition
if ($group !== 'overall') {
    $select_parts[] = "m.competition_id";
    $select_parts[] = "c.competition_name";
    $groupBy[] = "m.competition_id";
    $groupBy[] = "c.competition_name";
} else {
    $select_parts[] = "'All' AS competition_id";
    $select_parts[] = "'All' AS competition_name";
}

// Season
if (in_array($group, ['season','phase','phasedetail'])) {
    $select_parts[] = "m.season_id";
    $select_parts[] = "se.season_name";
    $groupBy[] = "m.season_id";
    $groupBy[] = "se.season_name";
} else {
    $select_parts[] = "'All' AS season_id";
    $select_parts[] = "'All' AS season_name";
}

// Phase
if (in_array($group, ['phase','phasedetail'])) {
    $select_parts[] = "m.phase_id";
    $select_parts[] = "p.phase_name";
    $groupBy[] = "m.phase_id";
    $groupBy[] = "p.phase_name";
} else {
    $select_parts[] = "'All' AS phase_id";
    $select_parts[] = "'All' AS phase_name";
}

// Phase detail
if ($group === 'phasedetail') {
    $select_parts[] = "m.phasedetail_id";
    $select_parts[] = "pd.phasedetail_name";
    $groupBy[] = "m.phasedetail_id";
    $groupBy[] = "pd.phasedetail_name";
} else {
    $select_parts[] = "'All' AS phasedetail_id";
    $select_parts[] = "'All' AS phasedetail_name";
}

// Base SQL
$sql = "
    SELECT 
        m.pair_id,
        m.ref1_id,
        r1.referee_name AS ref1_name,
        m.ref2_id,
        r2.referee_name AS ref2_name,
        CONCAT(r1.referee_name, ' / ', r2.referee_name) AS pair_name,
        " . implode(",\n        ", $select_parts) . ",
        COUNT(m.match_id) AS matches,
        SUM(s.total_2min) AS total_2min,
        SUM(s.total_5min) AS total_5min,
        ROUND(AVG(s.total_2min), 2) AS avg_2min,
        ROUND(AVG(s.total_5min), 2) AS avg_5min,
        ROUND(SUM(CASE WHEN s.total_2min > 5 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_5,
        ROUND(SUM(CASE WHEN s.total_2min > 6 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_6,
        ROUND(SUM(CASE WHEN s.total_2min > 7 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_7,
        ROUND(SUM(CASE WHEN s.total_2min > 8 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_8,
        ROUND(SUM(CASE WHEN s.total_2min > 9 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_9,
        ROUND(SUM(CASE WHEN s.total_2min > 10 THEN 1 ELSE 0 END) / COUNT(m.match_id) * 100, 2) AS pct_over_10
    FROM matchesmysql m
    LEFT JOIN refereessql r1 ON m.ref1_id = r1.referee_id
    LEFT JOIN refereessql r2 ON m.ref2_id = r2.referee_id
    JOIN matchesstatssql s ON m.match_id = s.match_id
    LEFT JOIN competitionsql c ON m.competition_id = c.competition_id
    LEFT JOIN seasonssql se ON m.season_id = se.season_id
    LEFT JOIN phasesql p ON m.phase_id = p.phase_id
    LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
";

// Apply filters
$where = [];
if ($competition_id) $where[] = "m.competition_id = '" . $conn->real_escape_string($competition_id) . "'";
if ($season_id)      $where[] = "m.season_id = '" . $conn->real_escape_string($season_id) . "'";
if ($phase_id)       $where[] = "m.phase_id = '" . $conn->real_escape_string($phase_id) . "'";
if ($phasedetail_id) $where[] = "m.phasedetail_id = '" . $conn->real_escape_string($phasedetail_id) . "'";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Group by
$sql .= "\n GROUP BY " . implode(", ", $groupBy);
$sql .= " ORDER BY matches DESC";

// Execute query
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error, 'sql' => $sql]);
    exit;
}

// Collect stats
$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

// Filters for dropdowns
$filters = [
    "competitions" => [],
    "seasons" => [],
    "phases" => [],
    "phasedetails" => []
];

$filterQueries = [
    "competitions" => "SELECT DISTINCT c.competition_id, c.competition_name 
                       FROM matchesmysql m 
                       JOIN competitionsql c ON m.competition_id = c.competition_id
                       ORDER BY c.competition_name",
    "seasons"      => "SELECT DISTINCT se.season_id, se.season_name 
                       FROM matchesmysql m 
                       JOIN seasonssql se ON m.season_id = se.season_id
                       ORDER BY se.season_name DESC",
    "phases"       => "SELECT DISTINCT p.phase_id, p.phase_name 
                       FROM matchesmysql m 
                       JOIN phasesql p ON m.phase_id = p.phase_id
                       ORDER BY p.phase_name",
    "phasedetails" => "SELECT DISTINCT pd.phasedetail_id, pd.phasedetail_name 
                       FROM matchesmysql m 
                       JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
                       ORDER BY pd.phasedetail_name"
];

foreach ($filterQueries as $key => $query) {
    $res = $conn->query($query);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $filters[$key][] = [
                "id" => $row[array_key_first($row)],
                "name" => $row[array_key_last($row)]
            ];
        }
    }
}

// Output JSON
echo json_encode([
    "stats" => $stats,
    "filters" => $filters
], JSON_PRETTY_PRINT);
