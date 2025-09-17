<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'md418.wedos.net';
$db   = 'd183088_refs';
$user = 'a183088_refs';
$pass = 'Dukla123.';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $competition_id = isset($_GET['competition_id']) ? $_GET['competition_id'] : null;

$sql = "
    SELECT *
    FROM (
        SELECT 
            m.match_id,
            m.competition_id,
            c.competition_name,
            p.phase_name,
            pd.phasedetail_name,
            s.season_name,
            t1.team_name AS home_team,
            t2.team_name AS away_team,
            m.DateTime,
            ROW_NUMBER() OVER (PARTITION BY m.competition_id ORDER BY m.DateTime ASC) AS rn
        FROM matchesmysql m
        LEFT JOIN seasonssql s ON m.season_id = s.season_id
        LEFT JOIN phasesql p ON m.phase_id = p.phase_id
        LEFT JOIN phasedetailsql pd ON m.phasedetail_id = pd.phasedetail_id
        LEFT JOIN competitionsql c ON m.competition_id = c.competition_id
        LEFT JOIN teamssql t1 ON m.hteam_id = t1.team_id
        LEFT JOIN teamssql t2 ON m.ateam_id = t2.team_id
        WHERE m.status = 'scheduled' AND m.DateTime > NOW()
          AND (? IS NULL OR m.competition_id = ?)
    ) sub
    WHERE rn <= ?
    ORDER BY sub.DateTime ASC;
";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $competition_id, $competition_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $fixtures = [];
    while ($row = $result->fetch_assoc()) {
        $fixtures[] = $row;
    }

    echo json_encode([
        "success" => true,
        "fixtures" => $fixtures
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
