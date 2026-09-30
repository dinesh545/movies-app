<?php
// backend/api/user_register.php - User Registration API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit();
}

$name = trim($_POST['name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = trim($_POST['password'] ?? '');

if (empty($name) || empty($mobile) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields (Name, Mobile, Email, Password).']);
    exit();
}

// 10-digit Indian Mobile Validation
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
    exit();
}

try {
    // Check for duplicate Email or Mobile
    $stmt = $pdo->prepare("SELECT id, email, mobile, is_email_verified FROM users WHERE email = :email OR mobile = :mobile");
    $stmt->execute([':email' => $email, ':mobile' => $mobile]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['email'] === $email) {
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An account with this 10-digit mobile number already exists.']);
        }
        exit();
    }

    $otp = sprintf('%06d', rand(100000, 999999));
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, mobile, email, password_hash, is_email_verified, email_verification_otp)
        VALUES (:name, :mobile, :email, :hash, 0, :otp)
    ");
    $insertStmt->execute([
        ':name' => $name,
        ':mobile' => $mobile,
        ':email' => $email,
        ':hash' => $passwordHash,
        ':otp' => $otp
    ]);

    $userId = $pdo->lastInsertId();

    // Send Verification Email via SMTP
    $siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
    $subject = "Verify Your Email - {$siteTitle}";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; background: #0f172a; color: #f8fafc; border-radius: 12px;'>
            <h2 style='color: #38bdf8; margin-top: 0;'>Welcome to {$siteTitle}!</h2>
            <p>Hi " . htmlspecialchars($name) . ",</p>
            <p>Thank you for registering. Please use the verification OTP code below to activate your account:</p>
            <div style='background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; font-size: 2rem; font-weight: bold; letter-spacing: 6px; text-align: center; padding: 14px; border-radius: 10px; margin: 20px 0;'>
                {$otp}
            </div>
            <p style='font-size: 0.85rem; color: #94a3b8;'>This code will expire shortly. Do not share this OTP with anyone.</p>
        </div>
    ";

    try {
        send_smtp_email($pdo, $email, $subject, $body);
    } catch (Exception $mailErr) {
        error_log("Registration email delivery error: " . $mailErr->getMessage());
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful! Verification OTP sent to your email.',
        'user_id' => (int)$userId,
        'email' => $email
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
}
