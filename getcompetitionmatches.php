<?php
header("Content-Type: application/json");

// DB connection
$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// Get competition_id from query string
$competitionId = $_GET['competition_id'] ?? null;
if (!$competitionId) {
    echo json_encode(["error" => "Missing competition_id"]);
    exit;
}

// fetch competition name
$compStmt = $pdo->prepare("SELECT competition_name FROM competitionsql WHERE competition_id = ?");
$compStmt->execute([$competitionId]);
$compData = $compStmt->fetch();
$competitionName = $compData['competition_name'] ?? null;

try {
    $sql = "
        SELECT 
            m.match_id,
            m.competition_id,
            c.competition_name,
            m.season_id,
            s.season_name,
            m.phase_id,
            p.phase_name,
            m.phasedetail_id,
            pd.phasedetail_name,
            ms.`DateTime`,
            hteam.team_id AS home_team_id,
            ateam.team_id AS away_team_id,
            hteam.team_name AS home_team_name,
            ateam.team_name AS away_team_name,
            m.mutual_id,    
            mu.mutual_name,
            m.ref1_id,
            m.ref2_id,
            r1.referee_name AS ref1_name,
            r2.referee_name AS ref2_name,
            m.pair_id,
            rp.pair_name,
            -- stats from matchesstatssql
            ms.total_2min,
            ms.total_5min,
            ms.total_2min_home,
            ms.total_2min_away,
            ms.total_5min_home,
            ms.total_5min_away
        FROM matchesmysql m
        JOIN competitionsql c ON m.competition_id = c.competition_id
        JOIN seasonssql s ON m.season_id = s.season_id
        JOIN teamssql hteam ON m.hteam_id = hteam.team_id
        JOIN teamssql ateam ON m.ateam_id = ateam.team_id
        JOIN mutualsql mu ON m.mutual_id = mu.mutual_id
        LEFT JOIN phasesql p ON m.phase_id = p.phase_id
        LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
        LEFT JOIN refereessql r1 ON m.ref1_id = r1.referee_id
        LEFT JOIN refereessql r2 ON m.ref2_id = r2.referee_id
        LEFT JOIN refereepairsql rp ON m.pair_id = rp.pair_id
        LEFT JOIN matchesstatssql ms ON m.match_id = ms.match_id
        WHERE m.competition_id = ?
        ORDER BY ms.`DateTime` DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$competitionId]);
    $matches = $stmt->fetchAll();

    echo json_encode([
        "competition_id" => $competitionId,
        "competition_name" => $competitionName,
        "matches" => $matches
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
