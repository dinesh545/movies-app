<?php
// backend/api/movies.php - Returns DB Movies & Web Series Combined (Strictly Database Created)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

try {
    // Fetch DB items (support ?type=movie, ?type=series, or all by default)
    $typeFilter = $_GET['type'] ?? '';
    if ($typeFilter === 'movie') {
        $stmt = $pdo->prepare("SELECT * FROM media_items WHERE type = 'movie' ORDER BY id DESC");
        $stmt->execute();
    } elseif ($typeFilter === 'series') {
        $stmt = $pdo->prepare("SELECT * FROM media_items WHERE type = 'series' ORDER BY id DESC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM media_items ORDER BY id DESC");
        $stmt->execute();
    }
    $items = $stmt->fetchAll();

    $formatted = array_map(function($m) {
        $type = $m['type'] ?? 'movie';
        $rawUrl = $m['stream_url'] ?? '';
        if ($type === 'movie') {
            if (empty($rawUrl)) {
                $rawUrl = BASE_API_URL . '/api/stream.php?file=' . urlencode(MOVIES_FOLDER . '/' . $m['title']) . '&raw=1';
            } elseif (!str_contains($rawUrl, 'raw=1') && str_contains($rawUrl, 'stream.php')) {
                $rawUrl .= '&raw=1';
            }
        }
        return [
            'id' => (int)$m['id'],
            'type' => $type,
            'title' => $m['title'],
            'name' => $m['title'],
            'description' => $m['description'] ?? '',
            'poster_url' => !empty($m['poster_url']) ? $m['poster_url'] : 'uploads/posters/' . $m['title'] . '.jpg',
            'release_year' => (int)($m['release_year'] ?? 2026),
            'rating' => $m['rating'] ?? '8.0',
            'stream_url' => $rawUrl,
            'formatted_size' => !empty($m['release_year']) ? (string)$m['release_year'] : 'HD'
        ];
    }, $items);

    echo json_encode([
        'status' => 'success',
        'count' => count($formatted),
        'data' => $formatted
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
