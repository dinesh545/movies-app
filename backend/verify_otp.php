<?php
// backend/verify_otp.php - Dedicated Email OTP Verification Page

require_once __DIR__ . '/db.php';

$emailParam = strtolower(trim($_GET['email'] ?? ''));
$siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$siteFavicon = get_site_setting($pdo, 'site_favicon', '');
$siteLogo = get_site_setting($pdo, 'site_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verify Email OTP - <?= htmlspecialchars($siteTitle) ?></title>
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
            --radius-md: 10px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }

        .auth-card {
            background-color: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius-lg); padding: 32px 28px; width: 100%; max-width: 420px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); text-align: center;
        }
        .brand-logo-box {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .brand-logo-box i { color: #fff; font-size: 1.25rem; }
        .brand-title { font-weight: 800; font-size: 1.35rem; color: #fff; letter-spacing: -0.5px; }

        .otp-input {
            letter-spacing: 12px; font-size: 1.8rem; text-align: center; font-weight: 800;
            color: var(--primary); background: rgba(15, 23, 42, 0.9); border: 1px solid var(--primary);
            border-radius: 12px; width: 100%; padding: 12px; margin: 20px 0 14px 0; outline: none;
            box-shadow: 0 0 12px var(--primary-glow);
        }
        .form-control {
            width: 100%; padding: 11px 16px; background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border-color); border-radius: var(--radius-md); color: #fff; font-size: 0.92rem;
            margin-bottom: 12px; text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border: none;
            padding: 12px 20px; border-radius: 20px; font-weight: 700; font-size: 0.92rem; cursor: pointer;
            width: 100%; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35); min-height: 44px;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45); }
        .btn-resend {
            background: rgba(255, 255, 255, 0.06); color: var(--text-main); border: 1px solid var(--border-color);
            padding: 10px 18px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer;
            width: 100%; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 10px; min-height: 40px;
        }
        .btn-resend:hover { background: rgba(56, 189, 248, 0.12); color: var(--primary); border-color: rgba(56, 189, 248, 0.3); }

        .alert { padding: 12px 14px; border-radius: var(--radius-md); margin-bottom: 16px; font-weight: 600; font-size: 0.88rem; display: none; text-align: left; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .alert-info { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); display: flex; align-items: center; gap: 8px; }

        /* TOAST NOTIFICATION STYLES */
        .toast-container {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px; max-width: 360px; width: calc(100% - 40px);
        }
        .toast {
            background: rgba(15, 23, 42, 0.95); border: 1px solid var(--border-color);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 14px 18px; border-radius: 14px; color: #fff; font-size: 0.88rem; font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6); display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .toast-warning { border-color: rgba(245, 158, 11, 0.4); background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(15, 23, 42, 0.95)); color: #fbbf24; }
        .toast-success { border-color: rgba(34, 197, 94, 0.4); background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(15, 23, 42, 0.95)); color: #4ade80; }
        .toast-error { border-color: rgba(239, 68, 68, 0.4); background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(15, 23, 42, 0.95)); color: #f87171; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="toast-container" id="toastContainer"></div>

<div class="auth-card">
    <div class="brand-logo-box">
        <i class="fa-solid fa-envelope-open-text"></i>
    </div>
    <h1 class="brand-title">Email OTP Verification</h1>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 6px;">
        Please enter the 6-digit verification code sent to your email.
    </p>

    <div class="alert alert-danger" id="errorAlert"></div>
    <div class="alert alert-success" id="successAlert"></div>

    <form id="otpForm">
        <div style="margin-top: 14px; text-align: left;">
            <label style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Registered Email Address *</label>
            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($emailParam) ?>" placeholder="enter your registered email" required>
        </div>

        <div>
            <input type="text" id="otp" class="otp-input" maxlength="6" placeholder="000000" autocomplete="off" required>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <i class="fa-solid fa-circle-check"></i> Verify & Activate Account
        </button>

        <button type="button" class="btn-resend" id="resendBtn" onclick="resendOtp()">
            <i class="fa-solid fa-rotate-right"></i> Resend Verification OTP
        </button>
    </form>

    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
        Already verified? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In</a>
    </div>
</div>

<script>
    function showToast(message, type = 'warning') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = 'fa-triangle-exclamation';
        if (type === 'success') icon = 'fa-circle-check';
        if (type === 'error') icon = 'fa-circle-exclamation';

        toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Check if redirected with notice
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('notice') === 'email_not_verified') {
        showToast("Your email is not verified yet. Please enter the OTP sent to your email.", "warning");
    }

    document.getElementById('otpForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value.trim();
        const otp = document.getElementById('otp').value.trim();
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');
        const submitBtn = document.getElementById('submitBtn');

        errorAlert.style.display = 'none';
        successAlert.style.display = 'none';

        if (!otp || otp.length !== 6) {
            errorAlert.innerText = 'Please enter the full 6-digit OTP code.';
            errorAlert.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('otp', otp);

            const res = await fetch('api/user_verify_email.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                showToast(data.message, 'success');
                successAlert.innerText = data.message + ' Redirecting to login...';
                successAlert.style.display = 'block';
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                throw new Error(data.message || 'Verification failed.');
            }
        } catch (err) {
            errorAlert.innerText = err.message;
            errorAlert.style.display = 'block';
            showToast(err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Activate Account';
        }
    });

    async function resendOtp() {
        const email = document.getElementById('email').value.trim();
        const resendBtn = document.getElementById('resendBtn');
        const errorAlert = document.getElementById('errorAlert');

        if (!email) {
            errorAlert.innerText = 'Please enter your registered email address first.';
            errorAlert.style.display = 'block';
            return;
        }

        resendBtn.disabled = true;
        resendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending OTP...';

        try {
            const formData = new FormData();
            formData.append('email', email);

            const res = await fetch('api/user_resend_otp.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                showToast(data.message, 'success');
            } else {
                throw new Error(data.message || 'Failed to resend OTP.');
            }
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend Verification OTP';
        }
    }
</script>
</body>
</html>
