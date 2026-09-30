<?php
// backend/api/user_verify_email.php - Email OTP Verification API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$otp = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide email and 6-digit OTP code.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, is_email_verified, email_verification_otp FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User account not found.']);
        exit();
    }

    if ($user['is_email_verified'] == 1) {
        echo json_encode(['status' => 'success', 'message' => 'Email is already verified. You can log in now!']);
        exit();
    }

    if ($user['email_verification_otp'] !== $otp) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid verification OTP code. Please check and try again.']);
        exit();
    }

    // Mark verified
    $updateStmt = $pdo->prepare("UPDATE users SET is_email_verified = 1, email_verification_otp = NULL WHERE id = :id");
    $updateStmt->execute([':id' => $user['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Email verified successfully! You can now log in.'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Verification failed: ' . $e->getMessage()]);
}
