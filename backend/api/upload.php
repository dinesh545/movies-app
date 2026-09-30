<?php
// backend/api/upload.php

require_once __DIR__ . '/../NextcloudClient.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(45);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed.']);
    exit();
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded under form key "file".']);
    exit();
}

$uploadedFile = $_FILES['file'];
$fileName = basename($uploadedFile['name']);
$tmpPath = $uploadedFile['tmp_name'];

if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $uploadedFile['error']]);
    exit();
}

try {
    $client = new NextcloudClient();
    $client->ensureFolderExists(MOVIES_FOLDER);

    $remotePath = MOVIES_FOLDER . '/' . $fileName;
    $success = $client->uploadFile($remotePath, $tmpPath);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully to Nextcloud!',
            'remote_path' => $remotePath,
            'stream_url' => BASE_API_URL . '/api/stream.php?file=' . urlencode($remotePath)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file to Nextcloud server.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
