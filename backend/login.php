<?php
// backend/login.php - User Login Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_session_token'])) {
    header('Location: index.php');
    exit();
}

$siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$siteFavicon = get_site_setting($pdo, 'site_favicon', '');
$siteLogo = get_site_setting($pdo, 'site_logo', '');

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= htmlspecialchars($siteTitle) ?></title>
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
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px auto; box-shadow: 0 0 20px var(--primary-glow);
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
        .alert-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

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
            <div class="brand-logo-box"><i class="fa-solid fa-right-to-bracket"></i></div>
        <?php endif; ?>
        <h1 class="brand-title">Sign In</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Access your streaming subscription</p>
    </div>

    <?php if ($msg === 'session_expired'): ?>
        <div class="alert alert-warning" style="display: block;">
            <i class="fa-solid fa-triangle-exclamation"></i> Logged out because your account was logged in on another device!
        </div>
    <?php endif; ?>

    <div class="alert alert-danger" id="errorAlert"></div>

    <form id="loginForm">
        <div class="form-group">
            <label><i class="fa-solid fa-user-tag" style="color: var(--primary); margin-right: 6px;"></i> Email or 10-Digit Mobile *</label>
            <input type="text" id="login_id" class="form-control" placeholder="Enter Email or 10-digit Mobile" required>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label style="margin-bottom: 0;"><i class="fa-solid fa-lock" style="color: var(--primary); margin-right: 6px;"></i> Password *</label>
                <a href="forgot_password.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 600;">Forgot?</a>
            </div>
            <input type="password" id="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
        </button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Create Account</a>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const loginId = document.getElementById('login_id').value.trim();
        const password = document.getElementById('password').value.trim();

        const errorAlert = document.getElementById('errorAlert');
        const submitBtn = document.getElementById('submitBtn');

        errorAlert.style.display = 'none';

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing In...';

        try {
            const formData = new FormData();
            formData.append('login_id', loginId);
            formData.append('password', password);

            const res = await fetch('api/user_login.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                // Save session token locally for web/app
                localStorage.setItem('user_session_token', data.session_token);
                window.location.href = 'index.php';
            } else if (data.status === 'email_not_verified') {
                const unverifiedEmail = encodeURIComponent(data.email || loginId);
                window.location.href = `verify_otp.php?email=${unverifiedEmail}&notice=email_not_verified`;
            } else if (data.status === 'pending_approval') {
                errorAlert.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i> ' + data.message;
                errorAlert.style.display = 'block';
            } else {
                throw new Error(data.message || 'Login failed.');
            }
        } catch (err) {
            errorAlert.innerText = err.message;
            errorAlert.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In';
        }
    });
</script>

</body>
</html>
