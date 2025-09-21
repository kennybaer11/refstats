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

$mutualId = $_GET['mutual_id'] ?? null;
if (!$mutualId) {
    echo json_encode(['error' => 'Missing mutual_id']);
    exit;
}
$mutualIdEsc = $conn->real_escape_string($mutualId);

// Get mutual info with team IDs and names
$mutualRes = $conn->query("
    SELECT 
        m.mutual_name,
        m.team1_id,
        t1.team_name AS team1_name,
        m.team2_id,
        t2.team_name AS team2_name
    FROM mutualsql m
    LEFT JOIN teamssql t1 ON m.team1_id = t1.team_id
    LEFT JOIN teamssql t2 ON m.team2_id = t2.team_id
    WHERE m.mutual_id = '{$mutualIdEsc}'
");
$mutualRow = $mutualRes->fetch_assoc();
$mutualName = $mutualRow['mutual_name'] ?? 'Unknown Mutual';
$team1Id = $mutualRow['team1_id'] ?? null;
$team1Name = $mutualRow['team1_name'] ?? null;
$team2Id = $mutualRow['team2_id'] ?? null;
$team2Name = $mutualRow['team2_name'] ?? null;

// --- Full detail: competition + season + phase + phasedetail ---
$sqlDetail = "
SELECT
    m.mutual_id,
    mu.mutual_name,
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
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
LEFT JOIN phasesql p    ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY m.mutual_id, m.competition_id, m.season_id, m.phase_id, m.phasedetail_id
ORDER BY matches DESC
";
$resDetail = $conn->query($sqlDetail);
$mutualDetail = [];
while ($row = $resDetail->fetch_assoc()) {
    $mutualDetail[] = $row;
}

// --- Phase-level aggregation (per competition + season + phase) ---
$sqlPhase = "
SELECT
    m.mutual_id,
    mu.mutual_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
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
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
LEFT JOIN phasesql p    ON m.phase_id = p.phase_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY m.mutual_id, m.competition_id, m.season_id, m.phase_id
ORDER BY matches DESC
";
$resPhase = $conn->query($sqlPhase);
$mutualPhase = [];
while ($row = $resPhase->fetch_assoc()) {
    $row['phasedetail_name'] = 'All';
    $mutualPhase[] = $row;
}

// --- Season-level aggregation (phase + phasedetail = All) ---
$sqlSeason = "
SELECT
    m.mutual_id,
    mu.mutual_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
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
JOIN competitionsql c   ON m.competition_id = c.competition_id
JOIN seasonssql s       ON m.season_id = s.season_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY m.mutual_id, m.competition_id, m.season_id
ORDER BY matches DESC
";
$resSeason = $conn->query($sqlSeason);
$mutualSeason = [];
while ($row = $resSeason->fetch_assoc()) {
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $mutualSeason[] = $row;
}

// --- pompetition-level aggregation  ---
$sqlCompetition = "
SELECT
    m.mutual_id,
    mu.mutual_name,
    m.competition_id,
    c.competition_name,
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
JOIN competitionsql c   ON m.competition_id = c.competition_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY m.mutual_id, m.competition_id
ORDER BY matches DESC
";
$resCompetition = $conn->query($sqlCompetition);
$mutualCompetition = [];
while ($row = $resCompetition->fetch_assoc()) {
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $mutualCompetition[] = $row;
}


// --- pompetition-level aggregation  ---
$sqlOverall = "
SELECT
    m.mutual_id,
    mu.mutual_name,
    m.competition_id,
    c.competition_name,
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
JOIN competitionsql c   ON m.competition_id = c.competition_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY m.mutual_id
ORDER BY matches DESC
";
$resOverall = $conn->query($sqlOverall);
$mutualOverall = [];
while ($row = $resOverall->fetch_assoc()) {
    $row['competition_name'] = 'All competitions';
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $mutualOverall[] = $row;
}


// Teams aggregated per competition+season+phase+phasedetail
$sqlTeamsDetail = "
SELECT 
    t.team_id,
    t.team_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    m.phasedetail_id,
    pd.phasedetail_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min
FROM matchesmysql m
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN teamssql t ON (m.hteam_id = t.team_id OR m.ateam_id = t.team_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY t.team_id, m.competition_id, m.season_id, m.phase_id, m.phasedetail_id
ORDER BY matches DESC
";
$resTeamDetail = $conn->query($sqlTeamsDetail);
$teamDetail = [];
while ($row = $resTeamDetail->fetch_assoc()) {
    $teamDetail[] = $row;
}

// Teams aggregated per competition+season+phase
$sqlTeamsPhase = "
SELECT 
    t.team_id,
    t.team_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    m.phasedetail_id,
    pd.phasedetail_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min
FROM matchesmysql m
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN teamssql t ON (m.hteam_id = t.team_id OR m.ateam_id = t.team_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY t.team_id, m.competition_id, m.season_id, m.phase_id
ORDER BY matches DESC
";
$resTeamPhase = $conn->query($sqlTeamsPhase);
$teamPhase = [];
while ($row = $resTeamPhase->fetch_assoc()) {
    $row['phasedetail_name'] = 'All';
    $teamPhase[] = $row;
}

// Teams aggregated per competition+season
$sqlTeamsSeason = "
SELECT 
    t.team_id,
    t.team_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    m.phasedetail_id,
    pd.phasedetail_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min
FROM matchesmysql m
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN teamssql t ON (m.hteam_id = t.team_id OR m.ateam_id = t.team_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY t.team_id, m.competition_id, m.season_id
ORDER BY matches DESC
";
$resTeamSeason = $conn->query($sqlTeamsSeason);
$teamSeason = [];
while ($row = $resTeamSeason->fetch_assoc()) {
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $teamSeason[] = $row;
}

// Teams aggregated per competition+season
$sqlTeamsCompetition = "
SELECT 
    t.team_id,
    t.team_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min
FROM matchesmysql m
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN teamssql t ON (m.hteam_id = t.team_id OR m.ateam_id = t.team_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY t.team_id, m.competition_id
ORDER BY matches DESC
";
$resTeamCompetition = $conn->query($sqlTeamsCompetition);
$teamCompetition = [];
while ($row = $resTeamCompetition->fetch_assoc()) {
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $teamCompetition[] = $row;
}

// Teams aggregated overall
$sqlTeamsOverall = "
SELECT 
    t.team_id,
    t.team_name,
    m.competition_id,
    c.competition_name,
    m.season_id,
    s.season_name,
    m.phase_id,
    p.phase_name,
    m.phasedetail_id,
    pd.phasedetail_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min_away,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_2min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_2min_away END),2) AS avg_2min,
    ROUND(AVG(CASE WHEN m.hteam_id = t.team_id THEN ms.total_5min_home 
                   WHEN m.ateam_id = t.team_id THEN ms.total_5min_away END),2) AS avg_5min
FROM matchesmysql m
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN teamssql t ON (m.hteam_id = t.team_id OR m.ateam_id = t.team_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY t.team_id
ORDER BY matches DESC
";
$resTeamOverall = $conn->query($sqlTeamsOverall);
$teamOverall = [];
while ($row = $resTeamOverall->fetch_assoc()) {
    $row['competition_name'] = 'All competition';
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $teamOverall[] = $row;
}


// Referees aggregated per competition+season+phase+phasedetail
$sqlRefsDetail = "
SELECT 
    r.referee_id,
    r.referee_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_5min_away END),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereessql r ON (m.ref1_id = r.referee_id OR m.ref2_id = r.referee_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY r.referee_id, m.competition_id, m.season_id, m.phase_id, m.phasedetail_id
ORDER BY matches DESC
";
$resRefsDetail = $conn->query($sqlRefsDetail);
$refereesDetail = [];
while ($row = $resRefsDetail->fetch_assoc()) {
    $refereesDetail[] = $row;
}

// Referees aggregated per competition+season+phase
$sqlRefsPhase = "
SELECT 
    r.referee_id,
    r.referee_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_5min_away END),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereessql r ON (m.ref1_id = r.referee_id OR m.ref2_id = r.referee_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY r.referee_id, m.competition_id, m.season_id, m.phase_id
ORDER BY matches DESC
";
$resRefsPhase = $conn->query($sqlRefsPhase);
$refereesPhase = [];
while ($row = $resRefsPhase->fetch_assoc()) {
    $row['phasedetail_name'] = 'All';
    $refereesPhase[] = $row;
}

// Referees aggregated per competition+season
$sqlRefsSeason = "
SELECT 
    r.referee_id,
    r.referee_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_5min_away END),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereessql r ON (m.ref1_id = r.referee_id OR m.ref2_id = r.referee_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY r.referee_id, m.competition_id, m.season_id
ORDER BY matches DESC
";
$resRefsSeason = $conn->query($sqlRefsSeason);
$refereesSeason = [];
while ($row = $resRefsSeason->fetch_assoc()) {
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereesSeason[] = $row;
}


// Referees aggregated per competition
$sqlRefsCompetition = "
SELECT 
    r.referee_id,
    r.referee_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_5min_away END),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereessql r ON (m.ref1_id = r.referee_id OR m.ref2_id = r.referee_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY r.referee_id, m.competition_id
ORDER BY matches DESC
";
$resRefsCompetition = $conn->query($sqlRefsCompetition);
$refereesCompetition = [];
while ($row = $resRefsCompetition->fetch_assoc()) {
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereesCompetition[] = $row;
}


// Referees aggregated overall
$sqlRefsOverall = "
SELECT 
    r.referee_id,
    r.referee_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_2min_home END),2) AS avg_2min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_2min_away END),2) AS avg_2min_away,
    ROUND(AVG(CASE WHEN m.ref1_id = r.referee_id THEN ms.total_5min_home END),2) AS avg_5min_home,
    ROUND(AVG(CASE WHEN m.ref2_id = r.referee_id THEN ms.total_5min_away END),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereessql r ON (m.ref1_id = r.referee_id OR m.ref2_id = r.referee_id)
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY r.referee_id
ORDER BY matches DESC
";
$resRefsOverall = $conn->query($sqlRefsOverall);
$refereesOverall = [];
while ($row = $resRefsOverall->fetch_assoc()) {
    $row['competition_name'] = 'All competitions';
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereesOverall[] = $row;
}

// Referee Pairs aggregated per competition+season+phase+phasedetail
$sqlPairsDetail = "
SELECT 
    rp.pair_id,
    rp.pair_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereepairsql rp ON m.pair_id = rp.pair_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY rp.pair_id, m.competition_id, m.season_id, m.phase_id, m.phasedetail_id
ORDER BY matches DESC
";
$resPairsDetail = $conn->query($sqlPairsDetail);
$refereePairsDetail = [];
while ($row = $resPairsDetail->fetch_assoc()) {
    $refereePairsDetail[] = $row;
}

// Referee Pairs aggregated per competition+season+phase
$sqlPairsPhase = "
SELECT 
    rp.pair_id,
    rp.pair_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereepairsql rp ON m.pair_id = rp.pair_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY rp.pair_id, m.competition_id, m.season_id, m.phase_id
ORDER BY matches DESC
";
$resPairsPhase = $conn->query($sqlPairsPhase);
$refereePairsPhase = [];
while ($row = $resPairsPhase->fetch_assoc()) {
    $row['phasedetail_name'] = 'All';
    $refereePairsPhase[] = $row;
}

// Referee Pairs aggregated per competition+season
$sqlPairsSeason = "
SELECT 
    rp.pair_id,
    rp.pair_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereepairsql rp ON m.pair_id = rp.pair_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY rp.pair_id, m.competition_id, m.season_id
ORDER BY matches DESC
";
$resPairsSeason = $conn->query($sqlPairsSeason);
$refereePairsSeason = [];
while ($row = $resPairsSeason->fetch_assoc()) {
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereePairsSeason[] = $row;
}


// Referee Pairs aggregated per competition
$sqlPairsCompetition = "
SELECT 
    rp.pair_id,
    rp.pair_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereepairsql rp ON m.pair_id = rp.pair_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY rp.pair_id, m.competition_id
ORDER BY matches DESC
";
$resPairsCompetition = $conn->query($sqlPairsCompetition);
$refereePairsCompetition = [];
while ($row = $resPairsCompetition->fetch_assoc()) {
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereePairsCompetition[] = $row;
}


// Referee Pairs aggregated overall
$sqlPairsOverall = "
SELECT 
    rp.pair_id,
    rp.pair_name,
    m.competition_id,
    c.competition_name,
    COUNT(m.match_id) AS matches,
    ROUND(AVG(ms.total_2min),2) AS avg_2min,
    ROUND(AVG(ms.total_5min),2) AS avg_5min,
    ROUND(AVG(ms.total_2min_home),2) AS avg_2min_home,
    ROUND(AVG(ms.total_2min_away),2) AS avg_2min_away,
    ROUND(AVG(ms.total_5min_home),2) AS avg_5min_home,
    ROUND(AVG(ms.total_5min_away),2) AS avg_5min_away
FROM matchesmysql m
JOIN matchesstatssql ms ON m.match_id = ms.match_id
JOIN competitionsql c ON m.competition_id = c.competition_id
JOIN seasonssql s ON m.season_id = s.season_id
LEFT JOIN phasesql p ON m.phase_id = p.phase_id
LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
JOIN refereepairsql rp ON m.pair_id = rp.pair_id
WHERE m.mutual_id = '{$mutualIdEsc}'
GROUP BY rp.pair_id
ORDER BY matches DESC
";
$resPairsOverall = $conn->query($sqlPairsOverall);
$refereePairsOverall = [];
while ($row = $resPairsOverall->fetch_assoc()) {
    $row['competition_name'] = 'All competitions';
    $row['season_name'] = 'All seasons';
    $row['phase_name'] = 'All phases';
    $row['phasedetail_name'] = 'All';
    $refereePairsOverall[] = $row;
}


// --- Return JSON ---
echo json_encode([
    'mutual_id' => $mutualId,
    'mutual_name' => $mutualName,
    'team1_id' => $team1Id,
    'team1_name' => $team1Name,
    'team2_id' => $team2Id,
    'team2_name' => $team2Name,
    'mutual_phasedetail' => $mutualDetail,
        'mutual_phase' => $mutualPhase,
            'mutual_season' => $mutualSeason,
                'mutual_competition' => $mutualCompetition,
                    'mutual_overall' => $mutualOverall,
    'teams_phasedetail' => $teamDetail,
        'teams_phase' => $teamPhase,
            'teams_season' => $teamSeason,
                'teams_competition' => $teamCompetition,
                    'teams_overall' => $teamOverall,
    'referees_phasedetail' => $refereesDetail,
        'referees_phase' => $refereesPhase,
            'referees_season' => $refereesSeason,
                'referees_competition' => $refereesCompetition,
                    'referees_overall' => $refereesOverall,
    'referee_pairs_phasedetail' => $refereePairsDetail,
        'referee_pairs_phase' => $refereePairsPhase,
            'referee_pairs_season' => $refereePairsSeason,
                'referee_pairs_competition' => $refereePairsCompetition,
                    'referee_pairs_overall' => $refereePairsOverall,
], JSON_PRETTY_PRINT);

$conn->close();
