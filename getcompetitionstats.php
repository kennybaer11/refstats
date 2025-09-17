<?php
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
$competition_id = $_GET['competition'] ?? null;
$season_id      = $_GET['season'] ?? null;
$phase_id       = $_GET['phase'] ?? null;
$phasedetail_id = $_GET['phasedetail'] ?? null;
$level          = $_GET['level'] ?? 'all';

// Build WHERE clause
$where = " WHERE 1=1";
if ($competition_id) $where .= " AND m.competition_id = '" . $conn->real_escape_string($competition_id) . "'";
if ($season_id)      $where .= " AND m.season_id = '" . $conn->real_escape_string($season_id) . "'";
if ($phase_id)       $where .= " AND m.phase_id = '" . $conn->real_escape_string($phase_id) . "'";
if ($phasedetail_id) $where .= " AND m.phasedetail_id = '" . $conn->real_escape_string($phasedetail_id) . "'";

// Decide grouping and select columns
switch ($level) {
    case 'competition':
        $groupBy = "c.competition_id, c.competition_name";
        $selectCols = "
            c.competition_name,
            " . ($season_id ? "se.season_name" : "'All' AS season_name") . ",
            " . ($phase_id ? "ph.phase_name" : "'All' AS phase_name") . ",
            " . ($phasedetail_id ? "pd.phasedetail_name" : "'All' AS phasedetail_name") . "
        ";
        break;

    case 'season':
        $groupBy = "c.competition_id, se.season_id";
        $selectCols = "
            c.competition_name,
            se.season_name,
            " . ($phase_id ? "ph.phase_name" : "'All' AS phase_name") . ",
            " . ($phasedetail_id ? "pd.phasedetail_name" : "'All' AS phasedetail_name") . "
        ";
        break;

    case 'phase':
        $groupBy = "c.competition_id, se.season_id, ph.phase_id";
        $selectCols = "
            c.competition_name,
            se.season_name,
            ph.phase_name,
            " . ($phasedetail_id ? "pd.phasedetail_name" : "'All' AS phasedetail_name") . "
        ";
        break;

    case 'phasedetail':
    default:
        $groupBy = "c.competition_id, se.season_id, ph.phase_id, pd.phasedetail_id";
        $selectCols = "
            c.competition_name,
            se.season_name,
            ph.phase_name,
            pd.phasedetail_name
        ";
        break;
}

// Main query
$sql = "
SELECT
    $selectCols,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(s.total_2min), 2) AS avg_2min,
    ROUND(AVG(s.total_5min), 2) AS avg_5min,
    ROUND(AVG(s.total_2min_home), 2) AS avg_2min_home,
    ROUND(AVG(s.total_2min_away), 2) AS avg_2min_away,
    ROUND(AVG(s.total_5min_home), 2) AS avg_5min_home,
    ROUND(AVG(s.total_5min_away), 2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN s.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN s.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN s.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN s.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN s.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN s.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM competitionsql c
JOIN matchesmysql m ON c.competition_id = m.competition_id
JOIN matchesstatssql s ON m.match_id = s.match_id
JOIN seasonssql se ON m.season_id = se.season_id
LEFT JOIN phasesql ph ON m.phase_id = ph.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
$where
GROUP BY $groupBy
ORDER BY c.competition_name, se.season_name, ph.phase_name, pd.phasedetail_name
";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

// Fetch stats
$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

// Build filter options
$filters = ['competitions'=>[], 'seasons'=>[], 'phases'=>[], 'phasedetails'=>[]];

// Competitions
$res = $conn->query("SELECT DISTINCT c.competition_id, c.competition_name FROM competitionsql c JOIN matchesmysql m ON c.competition_id = m.competition_id ORDER BY c.competition_name");
while($r = $res->fetch_assoc()) $filters['competitions'][] = $r;

// Seasons
$res = $conn->query("SELECT DISTINCT se.season_id, se.season_name FROM seasonssql se JOIN matchesmysql m ON se.season_id = m.season_id ORDER BY se.season_id DESC");
while($r = $res->fetch_assoc()) $filters['seasons'][] = $r;

// Phases
$res = $conn->query("SELECT DISTINCT ph.phase_id, ph.phase_name FROM phasesql ph JOIN matchesmysql m ON ph.phase_id = m.phase_id ORDER BY ph.phase_name");
while($r = $res->fetch_assoc()) $filters['phases'][] = $r;

// Phase details
$res = $conn->query("SELECT DISTINCT pd.phasedetail_id, pd.phasedetail_name FROM phasedetailsql pd JOIN matchesmysql m ON pd.phasedetail_id = m.phasedetail_id ORDER BY pd.phasedetail_name");
while($r = $res->fetch_assoc()) $filters['phasedetails'][] = $r;

echo json_encode(['stats'=>$stats, 'filters'=>$filters], JSON_PRETTY_PRINT);
