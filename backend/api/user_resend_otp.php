<?php
// backend/api/user_resend_otp.php - Resend Email Verification OTP API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, is_email_verified FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email address.']);
        exit();
    }

    if ($user['is_email_verified'] == 1) {
        echo json_encode(['status' => 'success', 'message' => 'Your email is already verified. You can log in!']);
        exit();
    }

    // Generate new OTP
    $newOtp = sprintf('%06d', rand(100000, 999999));
    $updateStmt = $pdo->prepare("UPDATE users SET email_verification_otp = :otp WHERE id = :id");
    $updateStmt->execute([':otp' => $newOtp, ':id' => $user['id']]);

    // Send Email via SMTP
    $siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
    $subject = "Resent: Email Verification OTP - {$siteTitle}";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; background: #0f172a; color: #f8fafc; border-radius: 12px;'>
            <h2 style='color: #38bdf8; margin-top: 0;'>Verification Code Resent</h2>
            <p>Hi " . htmlspecialchars($user['name']) . ",</p>
            <p>Here is your new email verification OTP code:</p>
            <div style='background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; font-size: 2rem; font-weight: bold; letter-spacing: 6px; text-align: center; padding: 14px; border-radius: 10px; margin: 20px 0;'>
                {$newOtp}
            </div>
            <p style='font-size: 0.85rem; color: #94a3b8;'>Use this code to activate your account. Do not share it with anyone.</p>
        </div>
    ";

    try {
        send_smtp_email($pdo, $email, $subject, $body);
    } catch (Exception $mailErr) {
        error_log("Resend OTP mail delivery failed: " . $mailErr->getMessage());
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'New verification OTP sent successfully to your email!'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Resend OTP failed: ' . $e->getMessage()]);
}
