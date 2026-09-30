<?php
// backend/api/user_login.php - User Login & Single Device Session Token API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$loginId = trim($_POST['login_id'] ?? ''); // Email or 10-digit Mobile
$password = trim($_POST['password'] ?? '');

if (empty($loginId) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in both Email/Mobile and Password.']);
    exit();
}

try {
    // Find user by Email OR Mobile
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :login OR mobile = :login");
    $stmt->execute([':login' => strtolower($loginId)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Login ID or Password.']);
        exit();
    }

    if ($user['is_email_verified'] != 1) {
        echo json_encode([
            'status' => 'email_not_verified',
            'message' => 'Your email is not verified yet. Please enter the OTP sent to your email.',
            'email' => $user['email']
        ]);
        exit();
    }

    if (!isset($user['is_admin_approved']) || $user['is_admin_approved'] != 1) {
        echo json_encode([
            'status' => 'pending_approval',
            'message' => 'Your account is verified but pending Admin Approval. Please wait for administrator approval before logging in.'
        ]);
        exit();
    }

    // Single Session Lock: Generate new random session token
    $newSessionToken = bin2hex(random_bytes(16)) . '_' . time();

    // Save new session token in database (invalidates any existing session on another device/browser/app)
    $updateStmt = $pdo->prepare("UPDATE users SET session_token = :token WHERE id = :id");
    $updateStmt->execute([':token' => $newSessionToken, ':id' => $user['id']]);

    // Store in Web Session
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_mobile'] = $user['mobile'];
    $_SESSION['user_session_token'] = $newSessionToken;

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful!',
        'session_token' => $newSessionToken,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'mobile' => $user['mobile']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Login failed: ' . $e->getMessage()]);
}
