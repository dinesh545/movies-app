<?php
// backend/admin/settings.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../api/mail_helper.php';
require_once __DIR__ . '/header.php';

$msg = '';
$error = '';

// Handle Main Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $siteTitle = trim($_POST['site_title'] ?? '');
        $siteTagline = trim($_POST['site_tagline'] ?? '');
        $footerText = trim($_POST['footer_text'] ?? '');
        $siteLogoUrl = trim($_POST['site_logo_url'] ?? '');
        $siteFaviconUrl = trim($_POST['site_favicon_url'] ?? '');

        $uploadDir = __DIR__ . '/../uploads/settings/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle Logo Upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (in_array($ext, $allowed)) {
                $filename = 'logo_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . $filename)) {
                    $siteLogoUrl = 'uploads/settings/' . $filename;
                }
            } else {
                throw new Exception('Invalid Logo file format. Allowed formats: JPG, PNG, WEBP, SVG');
            }
        }

        // Handle Favicon Upload
        if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['ico', 'png', 'jpg', 'jpeg', 'webp', 'svg'];
            if (in_array($ext, $allowed)) {
                $filename = 'favicon_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $uploadDir . $filename)) {
                    $siteFaviconUrl = 'uploads/settings/' . $filename;
                }
            } else {
                throw new Exception('Invalid Favicon file format. Allowed formats: ICO, PNG, WEBP, SVG');
            }
        }

        // Save Settings to Database
        $settingsToSave = [
            'site_title' => $siteTitle,
            'site_tagline' => $siteTagline,
            'footer_text' => $footerText,
            'site_logo' => $siteLogoUrl,
            'site_favicon' => $siteFaviconUrl
        ];

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (key_name, key_value) VALUES (:key, :val)");
        foreach ($settingsToSave as $key => $val) {
            $stmt->execute([':key' => $key, ':val' => $val]);
        }

        $msg = 'Site settings updated successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (Error $e) {
        $error = $e->getMessage();
    }
}

// Fetch Current Settings
$currentTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$currentTagline = get_site_setting($pdo, 'site_tagline', 'Stream Movies & Web Series Online');
$currentFooter = get_site_setting($pdo, 'footer_text', '© 2026 Soni Cinemas. All Rights Reserved.');
$currentLogo = get_site_setting($pdo, 'site_logo', '');
$currentFavicon = get_site_setting($pdo, 'site_favicon', '');
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
        <h2><i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Site Settings & SMTP Configuration</h2>
    </div>

    <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
        <!-- Branding Section -->
        <h3 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-paint-roller"></i> General Branding
        </h3>

        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="form-group">
                <label><i class="fa-solid fa-heading" style="color: var(--primary);"></i> Website Title *</label>
                <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($currentTitle) ?>" required placeholder="e.g. Soni Cinemas">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-quote-left" style="color: var(--primary);"></i> Site Tagline</label>
                <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($currentTagline) ?>" placeholder="e.g. Stream HD Movies & Web Series">
            </div>
        </div>

        <div class="sidebar-divider" style="margin: 20px 0;"></div>

        <!-- Logo Section -->
        <h3 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-image"></i> Site Logo Setup
        </h3>

        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); align-items: start;">
            <div class="form-group">
                <label><i class="fa-solid fa-upload" style="color: var(--primary);"></i> Upload New Logo Image</label>
                <input type="file" name="logo_file" class="form-control" accept="image/*">
                <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 6px; display: block;">Supported formats: PNG, WEBP, SVG, JPG</span>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-link" style="color: var(--primary);"></i> Or Direct Logo Image URL</label>
                <input type="text" name="site_logo_url" class="form-control" value="<?= htmlspecialchars($currentLogo) ?>" placeholder="https://example.com/logo.png or uploads/...">
            </div>

            <div class="form-group">
                <label>Current Logo Preview</label>
                <div style="background: rgba(15, 23, 42, 0.8); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center; min-height: 70px; display: flex; align-items: center; justify-content: center;">
                    <?php if ($currentLogo): ?>
                        <img src="../<?= htmlspecialchars($currentLogo) ?>" alt="Site Logo" style="max-height: 50px; max-width: 100%; object-fit: contain;">
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;"><i class="fa-solid fa-crown" style="color: var(--primary);"></i> Default Brand Header Used</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="sidebar-divider" style="margin: 20px 0;"></div>

        <!-- Favicon Section -->
        <h3 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-icons"></i> Site Favicon Setup
        </h3>

        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); align-items: start;">
            <div class="form-group">
                <label><i class="fa-solid fa-upload" style="color: var(--primary);"></i> Upload New Favicon Icon</label>
                <input type="file" name="favicon_file" class="form-control" accept=".ico,.png,.webp,.svg,.jpg">
                <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 6px; display: block;">Supported formats: ICO, PNG, WEBP, SVG</span>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-link" style="color: var(--primary);"></i> Or Direct Favicon URL</label>
                <input type="text" name="site_favicon_url" class="form-control" value="<?= htmlspecialchars($currentFavicon) ?>" placeholder="https://example.com/favicon.ico or uploads/...">
            </div>

            <div class="form-group">
                <label>Current Favicon Preview</label>
                <div style="background: rgba(15, 23, 42, 0.8); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center; min-height: 70px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <?php if ($currentFavicon): ?>
                        <img src="../<?= htmlspecialchars($currentFavicon) ?>" alt="Site Favicon" style="width: 32px; height: 32px; object-fit: contain;">
                        <span style="font-size: 0.85rem; color: var(--text-main);"><?= htmlspecialchars(basename($currentFavicon)) ?></span>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;"><i class="fa-solid fa-globe"></i> Default Browser Favicon Used</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="sidebar-divider" style="margin: 20px 0;"></div>

        <!-- Footer Section -->
        <h3 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-copyright"></i> Footer Customization
        </h3>

        <div class="form-group">
            <label><i class="fa-solid fa-signature" style="color: var(--primary);"></i> Footer Copyright Text</label>
            <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($currentFooter) ?>" placeholder="© 2026 Soni Cinemas. All Rights Reserved.">
        </div>

        <div style="margin-top: 24px; text-align: right;">
            <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Site Settings</button>
        </div>
    </form>
</div>

</div>
</main>
</div>
</body>
</html>

