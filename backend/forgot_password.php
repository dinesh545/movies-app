<?php
// backend/forgot_password.php - Forgot Password Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$siteFavicon = get_site_setting($pdo, 'site_favicon', '');
$siteLogo = get_site_setting($pdo, 'site_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= htmlspecialchars($siteTitle) ?></title>
    <?php if ($siteFavicon): ?>
        <link rel="icon" href="<?= htmlspecialchars($siteFavicon) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: #131b2e;
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.35);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --radius-lg: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }

        .auth-card {
            width: 100%; max-width: 420px; background: var(--bg-card);
            border: 1px solid var(--border-color); border-radius: var(--radius-lg);
            padding: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }
        .brand-header { text-align: center; margin-bottom: 24px; }
        .brand-logo-box {
            width: 54px; height: 54px; border-radius: 14px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px auto; box-shadow: 0 0 20px rgba(220, 38, 38, 0.35);
        }
        .brand-logo-box i { color: #fff; font-size: 1.5rem; }
        .brand-title { font-size: 1.35rem; font-weight: 800; color: #fff; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-weight: 600; font-size: 0.85rem; }
        .form-control {
            width: 100%; padding: 12px 16px; background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border-color); border-radius: 10px; color: #fff; font-size: 0.92rem;
            transition: 0.2s ease;
        }
        .form-control:focus { border-color: var(--primary); outline: none; background: rgba(15, 23, 42, 0.95); box-shadow: 0 0 12px var(--primary-glow); }

        .btn-submit {
            width: 100%; background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff; border: none; padding: 13px; border-radius: 20px;
            font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4); }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.88rem; display: none; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }

        .auth-footer { text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); font-size: 0.88rem; color: var(--text-muted); }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-header">
        <?php if ($siteLogo): ?>
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo" style="max-height: 48px; margin-bottom: 12px; object-fit: contain;">
        <?php else: ?>
            <div class="brand-logo-box"><i class="fa-solid fa-key"></i></div>
        <?php endif; ?>
        <h1 class="brand-title">Reset Password</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Send reset OTP to your registered email</p>
    </div>

    <div class="alert alert-danger" id="errorAlert"></div>
    <div class="alert alert-success" id="successAlert"></div>

    <!-- Step 1: Request OTP -->
    <form id="requestOtpForm">
        <div class="form-group">
            <label><i class="fa-solid fa-envelope" style="color: var(--primary); margin-right: 6px;"></i> Registered Email or Mobile *</label>
            <input type="text" id="login_id" class="form-control" placeholder="Enter Email or 10-digit Mobile" required>
        </div>

        <button type="submit" class="btn-submit" id="sendOtpBtn">
            <i class="fa-solid fa-paper-plane"></i> Send Password Reset OTP
        </button>
    </form>

    <!-- Step 2: Set New Password with OTP (hidden by default) -->
    <form id="resetPasswordForm" style="display: none;">
        <div class="form-group">
            <label><i class="fa-solid fa-shield" style="color: var(--primary); margin-right: 6px;"></i> 6-Digit OTP Code *</label>
            <input type="text" id="otpCode" class="form-control" maxlength="6" placeholder="Enter 6-digit OTP code" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-lock" style="color: var(--primary); margin-right: 6px;"></i> New Password *</label>
            <input type="password" id="newPassword" class="form-control" placeholder="Min. 6 characters" minlength="6" required>
        </div>

        <button type="submit" class="btn-submit" id="resetBtn" style="background: linear-gradient(135deg, #16a34a, #15803d);">
            <i class="fa-solid fa-lock-open"></i> Reset Password Now
        </button>
    </form>

    <div class="auth-footer">
        Remember your password? <a href="login.php">Sign In</a>
    </div>
</div>

<script>
    let resetEmail = '';

    document.getElementById('requestOtpForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const loginId = document.getElementById('login_id').value.trim();
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');
        const sendOtpBtn = document.getElementById('sendOtpBtn');

        errorAlert.style.display = 'none';
        successAlert.style.display = 'none';

        sendOtpBtn.disabled = true;
        sendOtpBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending OTP...';

        try {
            const formData = new FormData();
            formData.append('login_id', loginId);

            const res = await fetch('api/user_forgot_password.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                resetEmail = data.email;
                successAlert.innerText = data.message;
                successAlert.style.display = 'block';

                document.getElementById('requestOtpForm').style.display = 'none';
                document.getElementById('resetPasswordForm').style.display = 'block';
            } else {
                throw new Error(data.message || 'Failed to send OTP.');
            }
        } catch (err) {
            errorAlert.innerText = err.message;
            errorAlert.style.display = 'block';
        } finally {
            sendOtpBtn.disabled = false;
            sendOtpBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Password Reset OTP';
        }
    });

    document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const otp = document.getElementById('otpCode').value.trim();
        const newPassword = document.getElementById('newPassword').value.trim();
        const errorAlert = document.getElementById('errorAlert');
        const resetBtn = document.getElementById('resetBtn');

        errorAlert.style.display = 'none';

        resetBtn.disabled = true;
        resetBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting...';

        try {
            const formData = new FormData();
            formData.append('email', resetEmail);
            formData.append('otp', otp);
            formData.append('new_password', newPassword);

            const res = await fetch('api/user_reset_password.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                alert('Password reset successfully! Please sign in with your new password.');
                window.location.href = 'login.php';
            } else {
                throw new Error(data.message || 'Reset failed.');
            }
        } catch (err) {
            errorAlert.innerText = err.message;
            errorAlert.style.display = 'block';
        } finally {
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Reset Password Now';
        }
    });
</script>

</body>
</html>
