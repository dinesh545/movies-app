<?php
// backend/api/stream_ping.php - Real-Time Live Playback Ping API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'] ?? 0;
$sessionToken = $_SESSION['user_session_token'] ?? '';

// Support App token / user_id parameter fallback
if ($userId <= 0 && isset($_REQUEST['user_id'])) {
    $userId = (int)$_REQUEST['user_id'];
}
if (empty($sessionToken) && isset($_REQUEST['session_token'])) {
    $sessionToken = trim($_REQUEST['session_token']);
}

if ($userId <= 0 && !empty($sessionToken)) {
    try {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE session_token = :st");
        $uStmt->execute([':st' => $sessionToken]);
        $fetchedId = $uStmt->fetchColumn();
        if ($fetchedId) {
            $userId = (int)$fetchedId;
        }
    } catch (Exception $e) {}
}

if ($userId <= 0) {
    $userId = 1; // Default fallback to user #1 if guest or offline token
}

$title = trim($_REQUEST['title'] ?? '');
$deviceType = trim($_REQUEST['device'] ?? 'Web');
$action = trim($_REQUEST['action'] ?? 'ping');

if (empty($title) && $action === 'ping') {
    echo json_encode(['status' => 'error', 'message' => 'Title missing.']);
    exit();
}

try {
    if ($action === 'stop') {
        $stmt = $pdo->prepare("DELETE FROM active_streams WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        echo json_encode(['status' => 'success', 'message' => 'Stream stopped.']);
        exit();
    }

    // Check if user active stream entry exists
    $stmt = $pdo->prepare("SELECT id FROM active_streams WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $updateStmt = $pdo->prepare("UPDATE active_streams SET media_title = :title, device_type = :device, last_ping = CURRENT_TIMESTAMP WHERE user_id = :uid");
        $updateStmt->execute([':title' => $title, ':device' => $deviceType, ':uid' => $userId]);
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO active_streams (user_id, media_title, device_type) VALUES (:uid, :title, :device)");
        $insertStmt->execute([':uid' => $userId, ':title' => $title, ':device' => $deviceType]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Live ping recorded.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ping error: ' . $e->getMessage()]);
}
