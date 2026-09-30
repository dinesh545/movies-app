<?php
// backend/api/user_reset_password.php - Verify OTP & Reset Password API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$otp = trim($_POST['otp'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');

if (empty($email) || empty($otp) || empty($newPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide Email, OTP code, and New Password.']);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters long.']);
    exit();
}

try {
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $userStmt->execute([':email' => $email]);
    $user = $userStmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User account not found.']);
        exit();
    }

    // Verify OTP code and check expiration
    $resetStmt = $pdo->prepare("
        SELECT id FROM user_password_resets 
        WHERE user_id = :user_id AND otp_code = :otp AND expires_at >= :now
    ");
    $resetStmt->execute([
        ':user_id' => $user['id'],
        ':otp' => $otp,
        ':now' => date('Y-m-d H:i:s')
    ]);
    $reset = $resetStmt->fetch();

    if (!$reset) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP code. Please request a new OTP.']);
        exit();
    }

    // Update password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upStmt = $pdo->prepare("UPDATE users SET password_hash = :hash, session_token = NULL WHERE id = :id");
    $upStmt->execute([':hash' => $newHash, ':id' => $user['id']]);

    // Delete used OTP
    $delStmt = $pdo->prepare("DELETE FROM user_password_resets WHERE user_id = :id");
    $delStmt->execute([':id' => $user['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Password reset successfully! You can now log in with your new password.'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Reset failed: ' . $e->getMessage()]);
}
