<?php
// backend/api/user_session_check.php - Heartbeat / Session Check API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$token = trim($_REQUEST['session_token'] ?? '');
if (empty($token) && isset($_SESSION['user_session_token'])) {
    $token = $_SESSION['user_session_token'];
}

if (empty($token)) {
    echo json_encode([
        'status' => 'unauthenticated',
        'message' => 'No active user session token found.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, mobile, email, session_token FROM users WHERE session_token = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        // Clear web session if invalid
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_mobile'], $_SESSION['user_session_token']);
        echo json_encode([
            'status' => 'session_expired',
            'message' => 'Logged out because your account was logged in on another device.'
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Session valid',
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'mobile' => $user['mobile']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
