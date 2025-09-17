<?php
header('Content-Type: application/json');

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
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$pair_id = $_GET['pair_id'] ?? null;
if (!$pair_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing pair_id"]);
    exit;
}

// ---- Get pair name ----
$sqlPair = "    
    SELECT rp.pair_id, rp.pair_name, rp.ref1_id, rp.ref2_id,
           r1.referee_name AS ref1_name,
           r2.referee_name AS ref2_name
            FROM refereepairsql rp
           LEFT JOIN refereessql r1 ON rp.ref1_id = r1.referee_id
           LEFT JOIN refereessql r2 ON rp.ref2_id = r2.referee_id
           WHERE rp.pair_id = :pair";
$stmt = $pdo->prepare($sqlPair);
$stmt->execute(['pair' => $pair_id]);
$pairInfo = $stmt->fetch();
if (!$pairInfo) {
    http_response_code(404);
    echo json_encode(["error" => "Referee pair not found"]);
    exit;
}

// ---- Query 1: per season + per phase ----
$sqlPhases = "
    SELECT 
        m.competition_id,
        c.competition_name,
        m.season_id,
        se.season_name,
        m.phase_id, 
        p.phase_name,
        COUNT(m.match_id) AS matches,
        ROUND(AVG(s.total_2min),2) AS avg_2min,
        ROUND(AVG(s.total_5min),2) AS avg_5min,
        ROUND(AVG(s.total_2min_home),2) AS avg_2min_home,
        ROUND(AVG(s.total_2min_away),2) AS avg_2min_away,
        ROUND(AVG(s.total_5min_home),2) AS avg_5min_home,
        ROUND(AVG(s.total_5min_away),2) AS avg_5min_away,
        ROUND(SUM(CASE WHEN s.total_2min>5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
        ROUND(SUM(CASE WHEN s.total_2min>6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
        ROUND(SUM(CASE WHEN s.total_2min>7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
        ROUND(SUM(CASE WHEN s.total_2min>8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
        ROUND(SUM(CASE WHEN s.total_2min>9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
        ROUND(SUM(CASE WHEN s.total_2min>10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
    FROM matchesmysql m
    JOIN matchesstatssql s ON m.match_id = s.match_id
    JOIN competitionsql c ON c.competition_id = m.competition_id
    JOIN phasesql p ON p.phase_id = m.phase_id
    JOIN seasonssql se ON se.season_id = m.season_id
    WHERE m.pair_id = :pair
    GROUP BY m.competition_id, m.season_id, m.phase_id
    ORDER BY m.competition_id, m.season_id, m.phase_id
";
$stmt = $pdo->prepare($sqlPhases);
$stmt->execute(['pair' => $pair_id]);
$phases = $stmt->fetchAll();

// ---- Query 2: per competition career totals ----
$sqlCareer = "
    SELECT 
        m.competition_id,
        c.competition_name,
        COUNT(m.match_id) AS matches,
        ROUND(AVG(s.total_2min),2) AS avg_2min,
        ROUND(AVG(s.total_5min),2) AS avg_5min,
        ROUND(AVG(s.total_2min_home),2) AS avg_2min_home,
        ROUND(AVG(s.total_2min_away),2) AS avg_2min_away,
        ROUND(AVG(s.total_5min_home),2) AS avg_5min_home,
        ROUND(AVG(s.total_5min_away),2) AS avg_5min_away,
        ROUND(SUM(CASE WHEN s.total_2min>5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
        ROUND(SUM(CASE WHEN s.total_2min>6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
        ROUND(SUM(CASE WHEN s.total_2min>7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
        ROUND(SUM(CASE WHEN s.total_2min>8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
        ROUND(SUM(CASE WHEN s.total_2min>9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
        ROUND(SUM(CASE WHEN s.total_2min>10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
    FROM matchesmysql m
    JOIN matchesstatssql s ON m.match_id = s.match_id
    JOIN competitionsql c ON c.competition_id = m.competition_id
    WHERE m.pair_id = :pair
    GROUP BY m.competition_id
    ORDER BY m.competition_id
";
$stmt = $pdo->prepare($sqlCareer);
$stmt->execute(['pair' => $pair_id]);
$careers = $stmt->fetchAll();

// ---- Query 3: per competition career totals per phase ----
$sqlCareerPhases = "
    SELECT 
        m.competition_id,
        m.phase_id,
        p.phase_name,
        COUNT(m.match_id) AS matches,
        ROUND(AVG(s.total_2min),2) AS avg_2min,
        ROUND(AVG(s.total_5min),2) AS avg_5min,
        ROUND(AVG(s.total_2min_home),2) AS avg_2min_home,
        ROUND(AVG(s.total_2min_away),2) AS avg_2min_away,
        ROUND(AVG(s.total_5min_home),2) AS avg_5min_home,
        ROUND(AVG(s.total_5min_away),2) AS avg_5min_away,
        ROUND(SUM(CASE WHEN s.total_2min>5 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_5,
        ROUND(SUM(CASE WHEN s.total_2min>6 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_6,
        ROUND(SUM(CASE WHEN s.total_2min>7 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_7,
        ROUND(SUM(CASE WHEN s.total_2min>8 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_8,
        ROUND(SUM(CASE WHEN s.total_2min>9 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_9,
        ROUND(SUM(CASE WHEN s.total_2min>10 THEN 1 ELSE 0 END)/COUNT(m.match_id)*100,2) AS pct_over_10
    FROM matchesmysql m
    JOIN matchesstatssql s ON m.match_id = s.match_id
    JOIN phasesql p ON p.phase_id = m.phase_id
    WHERE m.pair_id = :pair
    GROUP BY m.competition_id, m.phase_id
    ORDER BY m.competition_id, m.phase_id
";
$stmt = $pdo->prepare($sqlCareerPhases);
$stmt->execute(['pair' => $pair_id]);
$careerPhases = $stmt->fetchAll();

// ---- Build nested structure ----
$output = [];
$output['pair_id'] = $pairInfo['pair_id'];
$output['pair_name'] = $pairInfo['pair_name'];
$output['ref1_id'] = $pairInfo['ref1_id'];
$output['ref1_name'] = $pairInfo['ref1_name'];
$output['ref2_id'] = $pairInfo['ref2_id'];
$output['ref2_name'] = $pairInfo['ref2_name'];

// per-phase per-season
foreach ($phases as $row) {
    $compId = $row['competition_id'];
    $seasonId = $row['season_id'];

    if (!isset($output['competitions'][$compId])) {
        $output['competitions'][$compId] = [
            "competition_id" => $row['competition_id'],
            "competition_name" => $row['competition_name'],
            "seasons" => [],
            "career" => null,
            "career_phases" => []
        ];
    }

    if (!isset($output['competitions'][$compId]['seasons'][$seasonId])) {
        $output['competitions'][$compId]['seasons'][$seasonId] = [
            "season_id" => $row['season_id'],
            "season_name" => $row['season_name'],
            "phases" => []
        ];
    }

    $output['competitions'][$compId]['seasons'][$seasonId]['phases'][] = $row;
}

// career totals
foreach ($careers as $row) {
    $compId = $row['competition_id'];
    $output['competitions'][$compId]['career'] = $row;
}

// career totals per phase
foreach ($careerPhases as $row) {
    $compId = $row['competition_id'];
    $output['competitions'][$compId]['career_phases'][$row['phase_id']] = $row;
}

// ---- Add "all_phases" aggregation per season ----
foreach ($output['competitions'] as $compId => &$comp) {
    foreach ($comp['seasons'] as $seasonId => &$season) {
        if (!empty($season['phases'])) {
            $all = [
                'phase_id' => '',
                'phase_name' => 'All phases',
                'matches' => 0,
                'avg_2min' => 0,
                'avg_5min' => 0,
                'avg_2min_home' => 0,
                'avg_2min_away' => 0,
                'avg_5min_home' => 0,
                'avg_5min_away' => 0,
                'pct_over_5' => 0,
                'pct_over_6' => 0,
                'pct_over_7' => 0,
                'pct_over_8' => 0,
                'pct_over_9' => 0,
                'pct_over_10' => 0,
            ];

            foreach ($season['phases'] as $phase) {
                $m = $phase['matches'];
                $all['matches'] += $m;
                $all['avg_2min'] += $phase['avg_2min'] * $m;
                $all['avg_5min'] += $phase['avg_5min'] * $m;
                $all['avg_2min_home'] += $phase['avg_2min_home'] * $m;
                $all['avg_2min_away'] += $phase['avg_2min_away'] * $m;
                $all['avg_5min_home'] += $phase['avg_5min_home'] * $m;
                $all['avg_5min_away'] += $phase['avg_5min_away'] * $m;
                $all['pct_over_5'] += $phase['pct_over_5'] * $m / 100;
                $all['pct_over_6'] += $phase['pct_over_6'] * $m / 100;
                $all['pct_over_7'] += $phase['pct_over_7'] * $m / 100;
                $all['pct_over_8'] += $phase['pct_over_8'] * $m / 100;
                $all['pct_over_9'] += $phase['pct_over_9'] * $m / 100;
                $all['pct_over_10'] += $phase['pct_over_10'] * $m / 100;
            }

            if ($all['matches'] > 0) {
                $all['avg_2min'] /= $all['matches'];
                $all['avg_5min'] /= $all['matches'];
                $all['avg_2min_home'] /= $all['matches'];
                $all['avg_2min_away'] /= $all['matches'];
                $all['avg_5min_home'] /= $all['matches'];
                $all['avg_5min_away'] /= $all['matches'];
                $all['pct_over_5'] = ($all['pct_over_5'] / $all['matches']) * 100;
                $all['pct_over_6'] = ($all['pct_over_6'] / $all['matches']) * 100;
                $all['pct_over_7'] = ($all['pct_over_7'] / $all['matches']) * 100;
                $all['pct_over_8'] = ($all['pct_over_8'] / $all['matches']) * 100;
                $all['pct_over_9'] = ($all['pct_over_9'] / $all['matches']) * 100;
                $all['pct_over_10'] = ($all['pct_over_10'] / $all['matches']) * 100;
            }

            $season['phases']['all_phases'] = $all;
        }
    }
}
unset($comp, $season);

// ---- Output JSON ----
echo json_encode($output, JSON_PRETTY_PRINT);
