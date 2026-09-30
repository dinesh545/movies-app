<?php
// backend/admin/smtp_settings.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../api/mail_helper.php';
require_once __DIR__ . '/header.php';

$msg = '';
$error = '';

// Handle Test Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_smtp') {
    try {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail)) {
            throw new Exception("Please enter a recipient email address to send test email.");
        }
        $subject = "SMTP Test Email - " . get_site_setting($pdo, 'site_title', 'Soni Cinemas');
        $body = "<h3>SMTP Test Connection Successful! 🎉</h3><p>Your SMTP mail settings are configured correctly for " . htmlspecialchars(get_site_setting($pdo, 'site_title', 'Soni Cinemas')) . ".</p>";
        
        send_smtp_email($pdo, $testEmail, $subject, $body);
        $msg = "Test verification email sent successfully to " . htmlspecialchars($testEmail) . "!";
    } catch (Exception $e) {
        $error = "Test Email Failed: " . $e->getMessage();
    }
}
// Handle Form Submission for SMTP Settings
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpUser = trim($_POST['smtp_user'] ?? '');
        $smtpPass = trim($_POST['smtp_pass'] ?? '');
        $smtpEnc = trim($_POST['smtp_encryption'] ?? 'tls');
        $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
        $smtpFromName = trim($_POST['smtp_from_name'] ?? 'Soni Cinemas');

        $settingsToSave = [
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_user' => $smtpUser,
            'smtp_pass' => $smtpPass,
            'smtp_encryption' => $smtpEnc,
            'smtp_from_email' => $smtpFromEmail,
            'smtp_from_name' => $smtpFromName
        ];

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (key_name, key_value) VALUES (:key, :val)");
        foreach ($settingsToSave as $key => $val) {
            $stmt->execute([':key' => $key, ':val' => $val]);
        }

        $msg = 'SMTP Email Verification settings updated successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (Error $e) {
        $error = $e->getMessage();
    }
}

// Fetch Current SMTP Values
$curSmtpHost = get_site_setting($pdo, 'smtp_host', '');
$curSmtpPort = get_site_setting($pdo, 'smtp_port', '587');
$curSmtpUser = get_site_setting($pdo, 'smtp_user', '');
$curSmtpPass = get_site_setting($pdo, 'smtp_pass', '');
$curSmtpEnc  = get_site_setting($pdo, 'smtp_encryption', 'tls');
$curSmtpFromEmail = get_site_setting($pdo, 'smtp_from_email', '');
$curSmtpFromName  = get_site_setting($pdo, 'smtp_from_name', 'Soni Cinemas');
?>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header-flex">
        <h2><i class="fa-solid fa-envelope-circle-check" style="color: var(--primary);"></i> SMTP Email Verification Settings</h2>
    </div>

    <form method="POST" style="margin-top: 16px;">
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 20px;">
            Configure your SMTP server settings to enable email verification OTPs for new user registrations and password reset requests.
        </p>

        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <div class="form-group">
                <label><i class="fa-solid fa-server" style="color: var(--primary);"></i> SMTP Host Server *</label>
                <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($curSmtpHost) ?>" placeholder="e.g. smtp.hostinger.com or smtp.gmail.com" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-network-wired" style="color: var(--primary);"></i> SMTP Port *</label>
                <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($curSmtpPort) ?>" placeholder="587 (TLS) or 465 (SSL)" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> Encryption Security</label>
                <select name="smtp_encryption" class="form-control">
                    <option value="tls" <?= $curSmtpEnc === 'tls' ? 'selected' : '' ?>>TLS (Port 587 - Recommended)</option>
                    <option value="ssl" <?= $curSmtpEnc === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                    <option value="none" <?= $curSmtpEnc === 'none' ? 'selected' : '' ?>>None (Port 25)</option>
                </select>
            </div>
        </div>

        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <div class="form-group">
                <label><i class="fa-solid fa-user" style="color: var(--primary);"></i> SMTP Username / Email *</label>
                <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($curSmtpUser) ?>" placeholder="noreply@mybhiwani.in" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-key" style="color: var(--primary);"></i> SMTP Password *</label>
                <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($curSmtpPass) ?>" placeholder="SMTP Account Password">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-paper-plane" style="color: var(--primary);"></i> From Email Address *</label>
                <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($curSmtpFromEmail) ?>" placeholder="noreply@mybhiwani.in" required>
            </div>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-id-card" style="color: var(--primary);"></i> Sender Name</label>
            <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($curSmtpFromName) ?>" placeholder="Soni Cinemas Verification">
        </div>

        <div style="margin-top: 24px; text-align: right;">
            <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Save SMTP Settings</button>
        </div>
    </form>
</div>

<!-- Test SMTP Card -->
<div class="card" style="margin-top: 24px;">
    <h2><i class="fa-solid fa-vial" style="color: #4ade80;"></i> Test SMTP Email Delivery</h2>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; margin-bottom: 16px;">
        Send a test verification email to ensure your SMTP server settings are working properly.
    </p>

    <form method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="action" value="test_smtp">
        <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
            <label>Recipient Email Address *</label>
            <input type="email" name="test_email" class="form-control" placeholder="Enter recipient email (e.g. user@gmail.com)" required>
        </div>
        <button type="submit" class="btn" style="background: linear-gradient(135deg, #16a34a, #15803d);">
            <i class="fa-solid fa-paper-plane"></i> Send Test Verification Email
        </button>
    </form>
</div>

</div>
</main>
</div>
</body>
</html>
