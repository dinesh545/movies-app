<?php
// backend/admin/login.php

require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid Username or Password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

require_once __DIR__ . '/header.php';
?>

<div style="max-width: 440px; margin: 40px auto; padding: 0 10px;">
    <div class="card" style="box-shadow: 0 20px 50px rgba(0,0,0,0.6); border-color: rgba(56, 189, 248, 0.2);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="brand-logo" style="width: 56px; height: 56px; margin: 0 auto 14px auto; font-size: 1.5rem;">
                <i class="fa-solid fa-crown"></i>
            </div>
            <h2 style="justify-content: center; font-size: 1.4rem;">Soni Cinemas Admin</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Sign in to access your streaming management console</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-user" style="margin-right: 6px; color: var(--primary);"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter admin username" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-lock" style="margin-right: 6px; color: var(--primary);"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter admin password" required>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn" style="width: 100%;"><i class="fa-solid fa-right-to-bracket"></i> Login to Admin Panel</button>
            </div>
        </form>
    </div>
</div>
</main>
</div>
</body>
</html>
