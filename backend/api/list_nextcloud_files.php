<?php
// backend/api/list_nextcloud_files.php

require_once __DIR__ . '/../NextcloudClient.php';

header('Content-Type: application/json');

try {
    $client = new NextcloudClient();
    $files = $client->listDirectory(MOVIES_FOLDER);

    echo json_encode([
        'status' => 'success',
        'count' => count($files),
        'data' => $files
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
