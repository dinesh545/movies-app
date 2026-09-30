<?php
// backend/api/admin_live_streams.php - Real-Time Active Streams Fetcher for Admin Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

// Verify Admin Session
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

try {
    // Delete stale pings older than 15 seconds
    $pdo->exec("DELETE FROM active_streams WHERE (strftime('%s', 'now') - strftime('%s', last_ping)) > 15");

    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.user_id,
            s.media_title,
            s.device_type,
            s.last_ping,
            (strftime('%s', 'now') - strftime('%s', s.last_ping)) as seconds_ago,
            u.name as user_name,
            u.email as user_email
        FROM active_streams s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.last_ping DESC
    ");
    
    $activeStreams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($activeStreams as &$st) {
        if (empty($st['user_name'])) {
            $st['user_name'] = 'User #' . $st['user_id'];
        }
        if (empty($st['user_email'])) {
            $st['user_email'] = 'Active User';
        }
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($activeStreams),
        'data' => $activeStreams
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
