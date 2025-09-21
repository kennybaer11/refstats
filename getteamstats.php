<?php
header('Content-Type: application/json');

// Database connection
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

$teamId = $_GET['team_id'] ?? null;
if (!$teamId) {
    echo json_encode(['error' => 'Missing team_id']);
    exit;
}
$teamIdEsc = $conn->real_escape_string($teamId);

// Get team_name
$teamRes = $conn->query("SELECT team_name FROM teamssql WHERE team_id = '{$teamIdEsc}'");
$teamRow = $teamRes->fetch_assoc();
$teamName = $teamRow['team_name'] ?? 'Unknown Team';

// --- Full detail: competition + season + phase + phasedetail ---
$sqlDetail = "
SELECT
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    m.phasedetail_id,
    pd.phasedetail_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN ms.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN ms.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN ms.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN ms.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN ms.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN ms.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
LEFT JOIN phasesql p    ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
WHERE m.hteam_id = '{$teamIdEsc}' OR m.ateam_id = '{$teamIdEsc}'
GROUP BY m.competition_id, m.season_id, m.phase_id, m.phasedetail_id
ORDER BY c.competition_name, s.season_name, p.phase_name, pd.phasedetail_name
";
$resDetail = $conn->query($sqlDetail);
$statsDetail = [];
while ($row = $resDetail->fetch_assoc()) {
    $statsDetail[] = $row;
}




// --- Phase-level aggregation (per competition + season + phase) ---
$sqlPhase = "
SELECT
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN ms.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN ms.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN ms.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN ms.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN ms.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN ms.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
LEFT JOIN phasesql p    ON m.phase_id = p.phase_id
WHERE m.hteam_id = '{$teamIdEsc}' OR m.ateam_id = '{$teamIdEsc}'
GROUP BY m.competition_id, m.season_id, m.phase_id
ORDER BY c.competition_name, s.season_name, p.phase_name
";
$resPhase = $conn->query($sqlPhase);
$statsPhase = [];
while ($row = $resPhase->fetch_assoc()) {
    $row['phasedetail_name'] = 'All';
    $statsPhase[] = $row;
}



// --- Season-level aggregation (phase + phasedetail = All) ---
$sqlSeason = "
SELECT
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN ms.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN ms.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN ms.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN ms.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN ms.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN ms.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
WHERE m.hteam_id = '{$teamIdEsc}' OR m.ateam_id = '{$teamIdEsc}'
GROUP BY m.competition_id, m.season_id
ORDER BY c.competition_name, s.season_name
";
$resSeason = $conn->query($sqlSeason);
$statsSeason = [];
while ($row = $resSeason->fetch_assoc()) {
    $row['phase_name'] = 'All Phases';
    $row['phasedetail_name'] = 'All';
    $statsSeason[] = $row;
}

// --- Overall stats ---
$sqlOverall = "
SELECT
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_2min ELSE NULL END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = '{$teamIdEsc}' THEN ms.total_5min ELSE NULL END),2) AS avg_5min_away,
    ROUND(SUM(CASE WHEN ms.total_2min > 5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
    ROUND(SUM(CASE WHEN ms.total_2min > 6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
    ROUND(SUM(CASE WHEN ms.total_2min > 7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
    ROUND(SUM(CASE WHEN ms.total_2min > 8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
    ROUND(SUM(CASE WHEN ms.total_2min > 9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
    ROUND(SUM(CASE WHEN ms.total_2min > 10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
WHERE m.hteam_id = '{$teamIdEsc}' OR m.ateam_id = '{$teamIdEsc}'
";
$resOverall = $conn->query($sqlOverall);
$overall = $resOverall->fetch_assoc();

// --- Return JSON ---
echo json_encode([
    'team_id' => $teamId,
    'team_name' => $teamName,
    'stats_phasedetail' => $statsDetail,
    'stats_phase' => $statsPhase,
    'stats_season' => $statsSeason,
    'overall' => $overall
], JSON_PRETTY_PRINT);

$conn->close();
