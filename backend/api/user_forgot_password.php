<?php
// backend/api/user_forgot_password.php - Send Forgot Password OTP API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$emailOrMobile = strtolower(trim($_POST['login_id'] ?? ''));

if (empty($emailOrMobile)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your registered Email address or Mobile number.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, mobile FROM users WHERE email = :id OR mobile = :id");
    $stmt->execute([':id' => $emailOrMobile]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No registered account found with provided Email/Mobile.']);
        exit();
    }

    $otp = sprintf('%06d', rand(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 mins expiry

    // Delete existing reset OTPs for this user
    $delStmt = $pdo->prepare("DELETE FROM user_password_resets WHERE user_id = :id");
    $delStmt->execute([':id' => $user['id']]);

    // Insert new OTP
    $insStmt = $pdo->prepare("INSERT INTO user_password_resets (user_id, otp_code, expires_at) VALUES (:user_id, :otp, :exp)");
    $insStmt->execute([':user_id' => $user['id'], ':otp' => $otp, ':exp' => $expiresAt]);

    // Send Email via SMTP
    $siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
    $subject = "Password Reset Request - {$siteTitle}";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; background: #0f172a; color: #f8fafc; border-radius: 12px;'>
            <h2 style='color: #ef4444; margin-top: 0;'>Password Reset Request</h2>
            <p>Hi " . htmlspecialchars($user['name']) . ",</p>
            <p>We received a request to reset your account password for {$siteTitle}. Please use the OTP code below:</p>
            <div style='background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; font-size: 2rem; font-weight: bold; letter-spacing: 6px; text-align: center; padding: 14px; border-radius: 10px; margin: 20px 0;'>
                {$otp}
            </div>
            <p style='font-size: 0.85rem; color: #94a3b8;'>This OTP is valid for 15 minutes. If you did not request a password reset, please ignore this email.</p>
        </div>
    ";

    send_smtp_email($pdo, $user['email'], $subject, $body);

    echo json_encode([
        'status' => 'success',
        'message' => 'Password reset OTP sent to your registered email (' . htmlspecialchars($user['email']) . ').',
        'email' => $user['email']
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to process request: ' . $e->getMessage()]);
}
