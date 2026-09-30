<?php
// backend/api/upload_poster.php

require_once __DIR__ . '/../NextcloudClient.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed.']);
    exit();
}

if (!isset($_FILES['poster'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No poster image file provided.']);
    exit();
}

$file = $_FILES['poster'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowedExts)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid image format. Allowed: JPG, PNG, WEBP.']);
    exit();
}

$newFileName = 'poster_' . time() . '_' . rand(100, 999) . '.' . $ext;

// Save Local Backup copy for fast 50ms poster rendering
$localUploadDir = __DIR__ . '/../uploads/posters/';
if (!file_exists($localUploadDir)) {
    @mkdir($localUploadDir, 0777, true);
}
$localPosterPath = $localUploadDir . $newFileName;
@move_uploaded_file($file['tmp_name'], $localPosterPath);

try {
    $client = new NextcloudClient();
    $client->ensureFolderExists('Posters');

    $remotePath = 'Posters/' . $newFileName;
    // Upload local file copy to Nextcloud
    $uploadTarget = file_exists($localPosterPath) ? $localPosterPath : $file['tmp_name'];
    $success = $client->uploadFile($remotePath, $uploadTarget);

    if ($success) {
        $posterUrl = BASE_API_URL . '/api/stream.php?file=' . urlencode($remotePath);
        echo json_encode([
            'status' => 'success',
            'poster_url' => $posterUrl
        ]);
        exit();
    }
} catch (Exception $e) {
    // If Nextcloud cloud upload has issues, fallback to local poster URL!
    if (file_exists($localPosterPath)) {
        $localUrl = BASE_API_URL . '/uploads/posters/' . $newFileName;
        echo json_encode([
            'status' => 'success',
            'poster_url' => $localUrl
        ]);
        exit();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload poster: ' . $e->getMessage()]);
    exit();
}

if (file_exists($localPosterPath)) {
    $localUrl = BASE_API_URL . '/uploads/posters/' . $newFileName;
    echo json_encode([
        'status' => 'success',
        'poster_url' => $localUrl
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload poster image.']);
}
