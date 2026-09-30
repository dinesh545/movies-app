<?php
// backend/api/upload_chunk.php

@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../NextcloudClient.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed.']);
    exit();
}

$chunkIndex = isset($_POST['chunk_index']) ? (int)$_POST['chunk_index'] : 0;
$totalChunks = isset($_POST['total_chunks']) ? (int)$_POST['total_chunks'] : 1;
$fileName = isset($_POST['file_name']) ? basename($_POST['file_name']) : '';
$fileId = isset($_POST['file_id']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['file_id']) : '';

if (empty($fileName) || empty($fileId) || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing chunk parameter or file payload.']);
    exit();
}

$tmpPath = $_FILES['file']['tmp_name'];

try {
    $client = new NextcloudClient();
    $client->ensureFolderExists(MOVIES_FOLDER);

    // Stream this 5MB chunk IMMEDIATELY to Nextcloud Native Upload Session
    $chunkSaved = $client->uploadChunkToNextcloud($fileId, $chunkIndex, $tmpPath);

    if (!$chunkSaved) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Failed to upload chunk {$chunkIndex} to Nextcloud."]);
        exit();
    }

    // Check if this is NOT the last chunk
    if ($chunkIndex < $totalChunks - 1) {
        echo json_encode([
            'status' => 'chunk_received',
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'message' => "Chunk {$chunkIndex} of {$totalChunks} streamed to Nextcloud."
        ]);
        exit();
    }

    // THIS IS THE FINAL CHUNK -> Instantly finalize and merge on Nextcloud (< 1 second!)
    $finalized = $client->finalizeNextcloudChunkUpload($fileId, $fileName);

    if ($finalized) {
        $remotePath = MOVIES_FOLDER . '/' . $fileName;
        echo json_encode([
            'status' => 'success',
            'message' => 'Movie uploaded and assembled instantly on Nextcloud!',
            'remote_path' => $remotePath,
            'stream_url' => BASE_API_URL . '/api/stream.php?file=' . urlencode($remotePath)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to finalize chunked assembly on Nextcloud server.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
