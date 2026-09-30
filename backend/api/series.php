<?php
// backend/api/series.php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$seriesId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($seriesId > 0) {
        // Fetch Single Series Details with Seasons & Episodes
        $stmt = $pdo->prepare("SELECT * FROM media_items WHERE id = :id AND type = 'series'");
        $stmt->execute([':id' => $seriesId]);
        $series = $stmt->fetch();

        if (!$series) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Series not found']);
            exit();
        }

        // Fetch Seasons
        $seasonStmt = $pdo->prepare("SELECT * FROM seasons WHERE series_id = :series_id ORDER BY season_number ASC");
        $seasonStmt->execute([':series_id' => $seriesId]);
        $seasons = $seasonStmt->fetchAll();

        foreach ($seasons as &$season) {
            $epStmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = :season_id ORDER BY episode_number ASC");
            $epStmt->execute([':season_id' => $season['id']]);
            $season['episodes'] = $epStmt->fetchAll();
        }

        $series['seasons'] = $seasons;

        echo json_encode([
            'status' => 'success',
            'data' => $series
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    } else {
        // Fetch All Series List
        $stmt = $pdo->prepare("SELECT * FROM media_items WHERE type = 'series' ORDER BY id DESC");
        $stmt->execute();
        $allSeries = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'count' => count($allSeries),
            'data' => $allSeries
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
