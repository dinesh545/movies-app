<?php
// backend/register.php - User Registration Page
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?= htmlspecialchars($siteTitle) ?></title>
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
            width: 100%; max-width: 440px; background: var(--bg-card);
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
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.88rem; display: none; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }

        .auth-footer { text-align: center; margin-top: 24px; pt-3; border-top: 1px solid var(--border-color); font-size: 0.88rem; color: var(--text-muted); }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }

        /* OTP MODAL */
        .otp-modal {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(6px); z-index: 1000;
            align-items: center; justify-content: center; padding: 20px;
        }
        .otp-box {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius-lg); padding: 28px; width: 100%; max-width: 380px; text-align: center;
        }
        .otp-input {
            letter-spacing: 12px; font-size: 1.8rem; text-align: center; font-weight: 800;
            color: var(--primary); background: rgba(15,23,42,0.9); border: 1px solid var(--primary);
            border-radius: 12px; width: 100%; padding: 10px; margin: 16px 0;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-header">
        <?php if ($siteLogo): ?>
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo" style="max-height: 48px; margin-bottom: 12px; object-fit: contain;">
        <?php else: ?>
            <div class="brand-logo-box"><i class="fa-solid fa-user-plus"></i></div>
        <?php endif; ?>
        <h1 class="brand-title">Create Account</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Sign up to watch unlimited movies & web series</p>
    </div>

    <div class="alert alert-danger" id="errorAlert"></div>
    <div class="alert alert-success" id="successAlert"></div>

    <form id="registerForm">
        <div class="form-group">
            <label><i class="fa-solid fa-user" style="color: var(--primary); margin-right: 6px;"></i> Full Name *</label>
            <input type="text" id="name" class="form-control" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-mobile-screen" style="color: var(--primary); margin-right: 6px;"></i> 10-Digit Mobile Number *</label>
            <input type="tel" id="mobile" class="form-control" placeholder="10-digit mobile (e.g. 9876543210)" maxlength="10" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-envelope" style="color: var(--primary); margin-right: 6px;"></i> Email Address *</label>
            <input type="email" id="email" class="form-control" placeholder="Enter your email address" required>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-lock" style="color: var(--primary); margin-right: 6px;"></i> Password *</label>
            <input type="password" id="password" class="form-control" placeholder="Min. 6 characters" minlength="6" required>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <i class="fa-solid fa-user-check"></i> Register & Send OTP
        </button>
    </form>

    <div class="auth-footer" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color);">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

<!-- OTP VERIFICATION MODAL -->
<div class="otp-modal" id="otpModal">
    <div class="otp-box">
        <h2 style="font-size: 1.25rem; color: #fff; margin-bottom: 8px;"><i class="fa-solid fa-envelope-open-text" style="color: var(--primary);"></i> Email OTP Verification</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Enter the 6-digit verification code sent to your email (<span id="userEmailSpan" style="color:var(--primary);"></span>):</p>

        <div class="alert alert-danger" id="otpErrorAlert"></div>

        <input type="text" id="otpInput" class="otp-input" maxlength="6" placeholder="000000" autocomplete="off">

        <button type="button" class="btn-submit" id="verifyOtpBtn" onclick="submitOtp()">
            <i class="fa-solid fa-circle-check"></i> Verify & Activate Account
        </button>

        <button type="button" class="btn-submit" id="modalResendBtn" onclick="modalResendOtp()" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border-color); color: var(--text-main); margin-top: 10px;">
            <i class="fa-solid fa-rotate-right"></i> Resend OTP
        </button>
    </div>
</div>

<script>
    let registeredEmail = '';

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('name').value.trim();
        const mobile = document.getElementById('mobile').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();

        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');
        const submitBtn = document.getElementById('submitBtn');

        errorAlert.style.display = 'none';
        successAlert.style.display = 'none';

        if (!/^[6-9]\d{9}$/.test(mobile)) {
            errorAlert.innerText = 'Please enter a valid 10-digit mobile number.';
            errorAlert.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering...';

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('mobile', mobile);
            formData.append('email', email);
            formData.append('password', password);

            const res = await fetch('api/user_register.php', { method: 'POST', body: formData });
            const responseText = await res.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonErr) {
                console.error("Invalid JSON response:", responseText);
                throw new Error("Server response error. Please try again or check server logs.");
            }

            if (data.status === 'success') {
                registeredEmail = email;
                document.getElementById('userEmailSpan').innerText = email;
                document.getElementById('otpModal').style.display = 'flex';
            } else {
                throw new Error(data.message || 'Registration failed.');
            }
        } catch (err) {
            errorAlert.innerText = err.message;
            errorAlert.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-user-check"></i> Register & Send OTP';
        }
    });

    async function submitOtp() {
        const otp = document.getElementById('otpInput').value.trim();
        const otpErrorAlert = document.getElementById('otpErrorAlert');
        const verifyOtpBtn = document.getElementById('verifyOtpBtn');

        otpErrorAlert.style.display = 'none';

        if (!otp || otp.length !== 6) {
            otpErrorAlert.innerText = 'Please enter the full 6-digit OTP code.';
            otpErrorAlert.style.display = 'block';
            return;
        }

        verifyOtpBtn.disabled = true;
        verifyOtpBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

        try {
            const formData = new FormData();
            formData.append('email', registeredEmail);
            formData.append('otp', otp);

            const res = await fetch('api/user_verify_email.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                alert('Account verified successfully! Please log in now.');
                window.location.href = 'login.php';
            } else {
                throw new Error(data.message || 'Verification failed.');
            }
        } catch (err) {
            otpErrorAlert.innerText = err.message;
            otpErrorAlert.style.display = 'block';
        } finally {
            verifyOtpBtn.disabled = false;
            verifyOtpBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verify & Activate Account';
        }
    }

    async function modalResendOtp() {
        const modalResendBtn = document.getElementById('modalResendBtn');
        const otpErrorAlert = document.getElementById('otpErrorAlert');

        if (!registeredEmail) return;

        modalResendBtn.disabled = true;
        modalResendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resending...';

        try {
            const formData = new FormData();
            formData.append('email', registeredEmail);

            const res = await fetch('api/user_resend_otp.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                alert(data.message);
            } else {
                throw new Error(data.message || 'Failed to resend OTP.');
            }
        } catch (err) {
            otpErrorAlert.innerText = err.message;
            otpErrorAlert.style.display = 'block';
        } finally {
            modalResendBtn.disabled = false;
            modalResendBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend OTP';
        }
    }
</script>

</body>
</html>
