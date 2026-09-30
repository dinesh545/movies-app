<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_session_token']);

if (!$isUserLoggedIn) {
    header('Location: login.php?msg=login_required');
    exit();
}

$userName = $_SESSION['user_name'] ?? '';
$userSessionToken = $_SESSION['user_session_token'] ?? '';

$siteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$siteTagline = get_site_setting($pdo, 'site_tagline', 'Stream Movies Online');
$siteLogo = get_site_setting($pdo, 'site_logo', '');
$siteFavicon = get_site_setting($pdo, 'site_favicon', '');
$footerText = get_site_setting($pdo, 'footer_text', '© 2026 Soni Cinemas. All Rights Reserved.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($siteTitle) ?> - <?= htmlspecialchars($siteTagline) ?></title>
    <?php if ($siteFavicon): ?>
        <link rel="icon" href="<?= htmlspecialchars($siteFavicon) ?>">
    <?php endif; ?>
    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JS Media Transmuxer & WebCodecs WASM MKV Player (Movi Player) -->
    <script src="https://cdn.jsdelivr.net/npm/mux.js@6.3.0/dist/mux.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/movi-player/dist/element.js"></script>

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: #131b2e;
            --bg-header: rgba(15, 23, 42, 0.85);
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.35);
            --accent: #0284c7;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --radius-lg: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        /* HEADER NAVBAR */
        .navbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--bg-header); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px; display: flex; justify-content: space-between; align-items: center;
            gap: 12px; flex-wrap: nowrap;
        }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; }
        .brand-logo { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0284c7, #38bdf8); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 15px var(--primary-glow); flex-shrink: 0; }
        .brand-logo i { color: #fff; font-size: 1.15rem; }
        .brand-title { font-weight: 800; font-size: 1.25rem; color: #fff; letter-spacing: -0.5px; white-space: nowrap; }
        .brand-title span { color: var(--primary); }

        .search-box { position: relative; max-width: 380px; width: 100%; margin: 0 12px; flex: 1; }
        .search-box input {
            width: 100%; background: rgba(255, 255, 255, 0.06); border: 1px solid var(--border-color);
            padding: 9px 16px 9px 40px; border-radius: 20px; color: #fff; font-size: 0.88rem; transition: 0.2s ease;
        }
        .search-box input:focus { outline: none; border-color: var(--primary); background: rgba(255, 255, 255, 0.1); box-shadow: 0 0 12px var(--primary-glow); }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.88rem; }

        .btn-search-mobile-toggle {
            display: none; background: rgba(255, 255, 255, 0.06); border: 1px solid var(--border-color);
            color: var(--primary); width: 34px; height: 34px; border-radius: 50%; cursor: pointer;
            align-items: center; justify-content: center; font-size: 0.88rem; transition: 0.2s; flex-shrink: 0;
        }
        .btn-search-mobile-toggle:hover { background: rgba(56, 189, 248, 0.15); color: #fff; }

        .mobile-search-dropdown {
            display: none; position: absolute; top: 100%; left: 0; right: 0;
            background: rgba(15, 23, 42, 0.98); border-bottom: 1px solid var(--primary);
            padding: 10px 14px; backdrop-filter: blur(16px); z-index: 99;
            box-shadow: 0 10px 25px rgba(0,0,0,0.8);
        }
        .mobile-search-dropdown.open { display: flex; align-items: center; gap: 8px; }
        .mobile-search-dropdown input {
            flex: 1; background: rgba(255, 255, 255, 0.08); border: 1px solid var(--primary);
            padding: 8px 14px; border-radius: 18px; color: #fff; font-size: 0.88rem; outline: none;
        }
        .mobile-search-dropdown .btn-close-search {
            background: none; border: none; color: var(--text-muted); font-size: 1.1rem; cursor: pointer; padding: 4px 8px;
        }
        .mobile-search-dropdown .btn-close-search:hover { color: #fff; }

        .nav-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; flex-wrap: nowrap; }
        .nav-btn-action {
            padding: 7px 14px; border-radius: 20px; font-weight: 700; font-size: 0.82rem;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
            transition: 0.2s; user-select: none; line-height: 1; flex-shrink: 0;
        }
        .nav-btn-suggest {
            color: #f59e0b; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .nav-btn-suggest:hover { background: rgba(245, 158, 11, 0.22); }
        .nav-btn-signin {
            color: #fff; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .nav-btn-signin:hover { background: rgba(56, 189, 248, 0.25); }
        .nav-btn-register {
            color: #fff; background: linear-gradient(135deg, #0284c7, #0369a1); border: 1px solid rgba(56, 189, 248, 0.4);
        }
        .nav-btn-register:hover { opacity: 0.95; }

        .nav-user-badge {
            display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.05);
            padding: 5px 12px; border-radius: 20px; border: 1px solid var(--border-color); flex-shrink: 0;
        }
        .nav-user-name {
            font-size: 0.88rem; font-weight: 700; color: #fff; max-width: 120px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .nav-btn-logout {
            color: #f87171; text-decoration: none; font-size: 0.8rem; font-weight: 600;
            margin-left: 4px; padding: 4px 8px; background: rgba(239, 68, 68, 0.12); border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .nav-btn-logout:hover { background: rgba(239, 68, 68, 0.25); }

        .btn-upload {
            background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border: none;
            padding: 9px 18px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer;
            display: flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .btn-upload:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4); }

        /* MAIN CONTAINER & HERO */
        .main-container { max-width: 1280px; margin: 0 auto; padding: 24px; }

        .hero-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--border-color); border-radius: 20px; padding: 32px; margin-bottom: 32px;
            position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
            min-height: 180px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .hero-card::before {
            content: ''; position: absolute; right: -50px; top: -50px; width: 250px; height: 250px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%); pointer-events: none;
        }
        .hero-title { font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .hero-desc { color: var(--text-muted); font-size: 0.95rem; max-width: 650px; }

        /* UPLOADER MODAL */
        .uploader-modal { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 32px; display: none; }
        .upload-dropzone { border: 2px dashed var(--primary); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; background: rgba(56, 189, 248, 0.03); transition: 0.2s; }
        .upload-dropzone:hover { background: rgba(56, 189, 248, 0.08); }
        .progress-bar-bg { width: 100%; height: 10px; background: rgba(255, 255, 255, 0.1); border-radius: 10px; margin-top: 14px; overflow: hidden; display: none; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #0284c7, #38bdf8); width: 0%; transition: width 0.1s ease; }

        /* MOVIES GRID */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 1.3rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }
        .movie-count-badge { background: rgba(56, 189, 248, 0.15); color: var(--primary); padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }

        .movies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }

        .movie-card {
            background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color);
            overflow: hidden; transition: transform 0.25s ease, box-shadow 0.25s ease; cursor: pointer; display: flex; flex-direction: column;
            position: relative;
        }
        .movie-card:hover { transform: translateY(-6px); box-shadow: 0 12px 28px rgba(0,0,0,0.6), 0 0 15px var(--primary-glow); border-color: var(--primary); }
        
        .poster-wrapper { width: 100%; aspect-ratio: 16/9; background: #0b1120; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .poster-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .movie-card:hover .poster-wrapper img { transform: scale(1.05); }
        .play-overlay {
            position: absolute; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);
            display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s ease;
        }
        .movie-card:hover .play-overlay { opacity: 1; }
        .play-btn-circle { width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: #090d16; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; padding-left: 3px; box-shadow: 0 0 20px var(--primary); }

        .card-details { padding: 14px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .movie-name { font-weight: 700; font-size: 1rem; color: #f1f5f9; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .movie-meta-info { font-size: 0.78rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; }

        /* CUSTOM FULLSCREEN OVERLAY VIDEO PLAYER */
        .player-modal {
            position: fixed; inset: 0; z-index: 1000; background: #000;
            display: none; flex-direction: column; justify-content: center; align-items: center;
        }
        .player-modal.active { display: flex; }

        .video-container { position: relative; width: 100%; height: 100%; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        
        video#mainWebPlayer {
            width: 100%; height: 100%; object-fit: contain; background: #000; outline: none;
        }

        /* PLAYER OVERLAY CONTROLS */
        .player-top-bar {
            position: absolute; top: 0; left: 0; right: 0; z-index: 25;
            background: linear-gradient(180deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 70%, transparent 100%);
            padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;
            transition: opacity 0.3s ease; opacity: 1; pointer-events: auto;
        }
        .player-top-left { display: flex; align-items: center; gap: 14px; min-width: 0; flex-shrink: 1; }
        .btn-close-player {
            background: rgba(255,255,255,0.15); border: none; color: #fff; width: 38px; height: 38px;
            border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; transition: 0.2s; flex-shrink: 0;
        }
        .btn-close-player:hover { background: rgba(255,255,255,0.3); }
        .player-movie-title {
            font-size: 1.05rem; font-weight: 700; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.8);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;
        }

        .player-top-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .btn-player-action {
            background: rgba(15, 23, 42, 0.8); border: 1px solid var(--primary); color: #fff;
            padding: 7px 13px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; cursor: pointer;
            backdrop-filter: blur(8px); display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
            user-select: none; line-height: 1; text-decoration: none;
        }
        .btn-player-action i { color: var(--primary); font-size: 0.88rem; }
        .btn-player-action:hover, .btn-player-action:active { background: var(--primary); color: #0f172a; }
        .btn-player-action:hover i, .btn-player-action:active i { color: #0f172a; }
        .btn-player-action.active {
            background: var(--primary); color: #0f172a; font-weight: 700;
        }
        .btn-fs-toggle {
            width: 34px; height: 34px; min-width: 34px; border-radius: 50%;
            padding: 0; display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-fs-toggle i {
            display: flex; align-items: center; justify-content: center; margin: 0; font-size: 0.85rem;
        }
        .gesture-hud-badge {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 15;
            background: rgba(15, 23, 42, 0.92); border: 1.5px solid var(--primary); color: #fff;
            padding: 14px 26px; border-radius: 16px; font-size: 1.15rem; font-weight: 700; backdrop-filter: blur(12px);
            pointer-events: none; opacity: 0; transition: opacity 0.2s ease, transform 0.2s ease; box-shadow: 0 0 25px var(--primary-glow);
        }
        .gesture-hud-badge.show { opacity: 1; transform: translate(-50%, -50%) scale(1.05); }

        /* UNIFIED PLAYER DROPDOWN MENUS */
        .player-dropdown-menu {
            position: absolute; top: 60px; right: 16px; background: rgba(15, 23, 42, 0.96);
            border: 1px solid var(--primary); border-radius: 14px; overflow: hidden; display: none;
            flex-direction: column; z-index: 50; backdrop-filter: blur(20px);
            box-shadow: 0 12px 36px rgba(0,0,0,0.85); min-width: 190px; max-width: 90vw;
            max-height: calc(100vh - 80px); max-height: calc(100dvh - 80px);
            overflow-y: auto; -webkit-overflow-scrolling: touch;
            animation: playerMenuFade 0.15s ease-out;
        }
        @keyframes playerMenuFade {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .menu-title {
            padding: 10px 16px 8px; font-size: 0.74rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: var(--primary); border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.25);
        }
        .speed-item, .more-menu-item {
            padding: 11px 18px; font-size: 0.85rem; font-weight: 600; color: #f1f5f9;
            cursor: pointer; transition: 0.15s ease; display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.04); text-decoration: none;
        }
        .speed-item:last-child, .more-menu-item:last-child { border-bottom: none; }
        .speed-item:hover, .more-menu-item:hover, .speed-item.active {
            background: var(--primary); color: #0f172a;
        }
        .speed-item:hover i, .more-menu-item:hover i, .speed-item.active i {
            color: #0f172a;
        }
        .speed-item.active { font-weight: 700; }
        .check-icon { font-size: 0.8rem; margin-right: 2px; }

        /* CONTAINER FULLSCREEN STYLES */
        #videoContainer:fullscreen,
        #videoContainer:-webkit-full-screen,
        #videoContainer:-moz-full-screen,
        #videoContainer:-ms-fullscreen {
            width: 100vw !important;
            height: 100vh !important;
            background: #000 !important;
        }
        #videoContainer:fullscreen .player-top-bar,
        #videoContainer:-webkit-full-screen .player-top-bar,
        #videoContainer:-moz-full-screen .player-top-bar,
        #videoContainer:-ms-fullscreen .player-top-bar {
            display: flex !important;
        }

        /* RESPONSIVE & MOBILE OVERRIDES (PORTRAIT & MOBILE VIEW) */
        @media (max-width: 768px) {
            /* HEADER: All elements in single row */
            .navbar {
                padding: 10px 14px;
                gap: 8px;
                flex-wrap: nowrap;
                position: relative;
            }
            .brand { gap: 8px; flex-shrink: 0; }
            .brand-logo { width: 32px; height: 32px; border-radius: 8px; }
            .brand-logo i { font-size: 1rem; }
            .brand-title {
                font-size: 0.95rem;
                max-width: 105px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .search-box { display: none; }
            .btn-search-mobile-toggle { display: inline-flex; }

            .nav-actions { gap: 6px; flex-wrap: nowrap; }
            .nav-btn-action {
                width: 34px; height: 34px; padding: 0;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 50%;
            }
            .nav-btn-action i { font-size: 0.88rem; margin: 0; }
            .nav-btn-label { display: none; }

            .nav-user-badge { padding: 4px 6px; gap: 4px; border-radius: 16px; }
            .nav-user-name { display: none; }
            .nav-btn-logout { padding: 4px 6px; }

            .movies-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
            .card-details { padding: 10px; }
            .movie-name { font-size: 0.88rem; }

            /* PLAYER TOP BAR IN PORTRAIT MODE: Keep all controls on-screen */
            .player-top-bar {
                padding: 8px 16px 8px 10px;
                padding-right: max(18px, env(safe-area-inset-right, 18px)) !important;
                gap: 6px;
                flex-wrap: nowrap;
            }
            .player-top-left {
                gap: 6px;
                min-width: 0;
                flex: 1;
                overflow: hidden;
            }
            .btn-close-player {
                width: 32px; height: 32px;
                font-size: 0.9rem; flex-shrink: 0;
            }
            .player-movie-title {
                font-size: 0.82rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-width: 0;
                flex: 1;
            }
            .player-top-actions {
                gap: 5px;
                margin-right: 4px;
                flex-shrink: 0;
                flex-wrap: nowrap;
            }
            .player-top-actions .player-btn-label {
                display: none;
            }
            .btn-player-action {
                width: 32px; height: 32px; padding: 0;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 50%;
            }
            .btn-player-action i {
                font-size: 0.82rem;
                margin: 0;
            }
            #btnMoreMenuToggle,
            .btn-fs-toggle {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                border-radius: 50% !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }
            .btn-fs-toggle i,
            #btnMoreMenuToggle i {
                font-size: 0.82rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1 !important;
                text-align: center !important;
            }
            .player-dropdown-menu {
                right: 14px; top: 46px;
                min-width: 170px; max-width: calc(100vw - 16px);
            }
        }

        /* MOBILE LANDSCAPE & FULLSCREEN OVERRIDES (AUDIO & SUBTITLES FULLY VISIBLE WITH LABELS) */
        @media (max-height: 600px) and (orientation: landscape) {
            .player-top-bar {
                padding: 8px 24px 8px 16px;
                padding-right: max(55px, env(safe-area-inset-right, 55px)) !important;
                background: linear-gradient(180deg, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.6) 75%, transparent 100%);
            }
            .player-top-left { gap: 8px; max-width: 220px; }
            .btn-close-player { width: 32px; height: 32px; font-size: 0.85rem; }
            .player-movie-title { font-size: 0.82rem; }
            .player-top-actions {
                gap: 8px;
                margin-right: 15px !important;
            }
            .player-top-actions .player-btn-label { display: inline !important; }
            .btn-player-action {
                width: auto !important; height: auto !important;
                padding: 5px 11px !important; font-size: 0.74rem !important; gap: 5px !important;
                border-radius: 14px !important;
            }
            .btn-player-action i { font-size: 0.8rem !important; }

            /* Perfectly centered circular buttons for More & Fullscreen in Landscape */
            #btnMoreMenuToggle,
            .btn-fs-toggle {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                border-radius: 50% !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                box-sizing: border-box !important;
            }
            #btnFsToggle {
                margin-left: 10px !important;
                margin-right: 15px !important;
            }
            .btn-fs-toggle i,
            #btnMoreMenuToggle i {
                font-size: 0.85rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1 !important;
                text-align: center !important;
            }

            .player-dropdown-menu {
                right: max(55px, env(safe-area-inset-right, 55px)) !important;
                top: 46px; min-width: 175px;
                max-height: calc(100vh - 52px); max-height: calc(100dvh - 52px);
            }
            .menu-title { padding: 6px 12px; font-size: 0.68rem; }
            .speed-item, .more-menu-item { padding: 7px 14px; font-size: 0.78rem; gap: 8px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <?php if ($siteLogo): ?>
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteTitle) ?>" style="max-height: 34px; object-fit: contain; border-radius: 6px;">
            <?php else: ?>
                <div class="brand-logo"><i class="fa-solid fa-crown"></i></div>
            <?php endif; ?>
            <div class="brand-title"><?= htmlspecialchars($siteTitle) ?></div>
        </a>

        <!-- Desktop Search Box -->
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search movies, shows..." onkeyup="filterMovies()">
        </div>

        <div class="nav-actions">
            <!-- Mobile Search Toggle Button -->
            <button type="button" class="btn-search-mobile-toggle" id="btnMobileSearchToggle" onclick="toggleMobileSearch()" title="Search Movies">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <!-- Request / Suggest Movie Button -->
            <button type="button" class="nav-btn-action nav-btn-suggest" onclick="openSuggestionModal()" title="Request Movie or Suggestion">
                <i class="fa-solid fa-lightbulb"></i> <span class="nav-btn-label">Request</span>
            </button>

            <?php if ($isUserLoggedIn): ?>
                <div class="nav-user-badge">
                    <i class="fa-solid fa-circle-user" style="color: var(--primary); font-size: 1.05rem;"></i>
                    <span class="nav-user-name"><?= htmlspecialchars($userName) ?></span>
                    <a href="logout.php" class="nav-btn-logout" title="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-btn-action nav-btn-signin" title="Sign In">
                    <i class="fa-solid fa-right-to-bracket"></i> <span class="nav-btn-label">Sign In</span>
                </a>
                <a href="register.php" class="nav-btn-action nav-btn-register" title="Register">
                    <i class="fa-solid fa-user-plus"></i> <span class="nav-btn-label">Register</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Expandable Search Bar Dropdown -->
        <div class="mobile-search-dropdown" id="mobileSearchBar">
            <i class="fa-solid fa-magnifying-glass" style="color: var(--primary); font-size: 0.9rem;"></i>
            <input type="text" id="mobileSearchInput" placeholder="Search movies, series..." onkeyup="handleMobileSearch(this.value)">
            <button type="button" class="btn-close-search" onclick="toggleMobileSearch()"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </nav>

    <!-- MAIN BODY CONTAINER -->
    <div class="main-container">



        <!-- UPLOADER MODAL SECTION -->
        <div class="uploader-modal" id="uploaderCard">
            <h3 style="color: #fff; margin-bottom: 14px;"><i class="fa-solid fa-upload" style="color: var(--primary);"></i> Upload Movie to Cloud Storage (8MB Chunked)</h3>
            <form id="uploadForm">
                <div class="upload-dropzone" onclick="document.getElementById('fileInput').click()">
                    <i class="fa-solid fa-file-video" style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;"></i>
                    <p style="color: var(--text-main); font-weight: 600;">Click to select video file (.mp4, .mkv, .webm)</p>
                    <input type="file" id="fileInput" name="file" accept="video/*" style="display:none;" onchange="updateFileName(this)">
                    <div id="selectedFileName" style="color: var(--primary); margin-top: 8px; font-weight: 700;"></div>
                </div>
                
                <div class="progress-bar-bg" id="progressBg">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <div id="uploadStatus" style="margin-top: 10px; font-weight: 600; font-size: 0.85rem;"></div>

                <div style="margin-top: 16px; text-align: right;">
                    <button type="submit" class="btn-upload" id="startUploadBtn">Start Upload 🚀</button>
                </div>
            </form>
        </div>

        <!-- COMBINED MEDIA GRID SECTION -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-play"></i> Movies &amp; Web Series
                <span class="movie-count-badge" id="movieCount">0</span>
            </div>
        </div>

        <div class="movies-grid" id="moviesGrid">
            <div style="color: var(--text-muted); grid-column: 1/-1;">Loading content...</div>
        </div>
    </div>

    <!-- ADVANCED WEB PLAYER MODAL -->
    <div class="player-modal" id="playerModal">
        <div class="video-container" id="videoContainer">
            
            <!-- TOP OVERLAY CONTROL BAR -->
            <div class="player-top-bar" id="playerTopBar">
                <div class="player-top-left">
                    <button class="btn-close-player" onclick="closePlayer()" title="Back to Library"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="player-movie-title" id="playerTitle">Movie Stream</div>
                </div>

                <div class="player-top-actions">
                    <!-- Audio Track Toggle (Always visible in Landscape & Fullscreen) -->
                    <button class="btn-player-action" id="btnAudioTrack" onclick="toggleAudioMenu()" title="Select Audio Track">
                        <i class="fa-solid fa-volume-high"></i> <span class="player-btn-label" id="audioTrackText">Audio</span>
                    </button>

                    <!-- Subtitle Track Toggle (Always visible in Landscape & Fullscreen) -->
                    <button class="btn-player-action" id="btnSubtitleTrack" onclick="toggleSubtitleMenu()" title="Select Subtitles">
                        <i class="fa-solid fa-closed-captioning"></i> <span class="player-btn-label" id="subtitleTrackText">Subtitles</span>
                    </button>

                    <!-- Playback Speed Toggle -->
                    <button class="btn-player-action" id="btnSpeedToggle" onclick="toggleSpeedMenu()" title="Playback Speed">
                        <i class="fa-solid fa-gauge-high"></i> <span class="player-btn-label" id="speedBtnText">1.0x</span>
                    </button>

                    <!-- Secondary Actions Menu (VLC, Outplayer, Aspect, PiP, Download) -->
                    <button class="btn-player-action" id="btnMoreMenuToggle" onclick="toggleMoreMenu()" title="More Controls">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>

                    <!-- Container Fullscreen Toggle (Keeps Top Bar & Overlays visible on mobile) -->
                    <button class="btn-player-action btn-fs-toggle" id="btnFsToggle" onclick="toggleContainerFullscreen()" title="Toggle Fullscreen">
                        <i class="fa-solid fa-expand" id="fsIcon"></i>
                    </button>
                </div>
            </div>

            <!-- SPEED DROPDOWN MENU -->
            <div class="player-dropdown-menu" id="speedMenu">
                <div class="menu-title"><i class="fa-solid fa-gauge-high"></i> Playback Speed</div>
                <div class="speed-item" onclick="setSpeed(0.5)">0.5x (Slow)</div>
                <div class="speed-item active" onclick="setSpeed(1.0)"><i class="fa-solid fa-check check-icon"></i> 1.0x (Normal)</div>
                <div class="speed-item" onclick="setSpeed(1.25)">1.25x</div>
                <div class="speed-item" onclick="setSpeed(1.5)">1.5x (Fast)</div>
                <div class="speed-item" onclick="setSpeed(2.0)">2.0x (Double)</div>
            </div>

            <!-- AUDIO TRACK DROPDOWN MENU -->
            <div class="player-dropdown-menu" id="audioMenu">
                <div class="menu-title"><i class="fa-solid fa-volume-high"></i> Audio Tracks</div>
                <div id="audioMenuList">
                    <div class="speed-item active" onclick="selectAudioTrack(-1)"><i class="fa-solid fa-check check-icon"></i> Default Track</div>
                </div>
            </div>

            <!-- SUBTITLE TRACK DROPDOWN MENU -->
            <div class="player-dropdown-menu" id="subtitleMenu">
                <div class="menu-title"><i class="fa-solid fa-closed-captioning"></i> Subtitle Tracks</div>
                <div id="subtitleMenuList">
                    <div class="speed-item active" onclick="selectSubtitleTrack(-1)"><i class="fa-solid fa-check check-icon"></i> Off</div>
                </div>
            </div>

            <!-- MORE OPTIONS DROPDOWN MENU -->
            <div class="player-dropdown-menu" id="moreMenu">
                <div class="menu-title"><i class="fa-solid fa-sliders"></i> Player Controls</div>
                <div class="more-menu-item" id="btnAspectToggle" onclick="toggleAspectRatio()">
                    <i class="fa-solid fa-crop-simple"></i> <span>Aspect: <strong id="aspectText">Fit</strong></span>
                </div>
                <div class="more-menu-item" onclick="togglePip()">
                    <i class="fa-solid fa-window-restore"></i> <span>Picture-in-Picture</span>
                </div>
                <a id="btnVlcAction" href="#" class="more-menu-item" title="Open stream in VLC Player">
                    <i class="fa-solid fa-cone"></i> <span>Play in VLC Player</span>
                </a>
                <a id="btnOutplayerAction" href="#" class="more-menu-item" style="display:none;" title="Open stream in Outplayer on iOS">
                    <i class="fa-solid fa-play"></i> <span>Play in Outplayer</span>
                </a>
                <a id="btnDownloadStream" href="#" download class="more-menu-item" title="Download video stream">
                    <i class="fa-solid fa-download"></i> <span>Download File</span>
                </a>
            </div>

            <!-- HUD GESTURE OVERLAY BADGE -->
            <div class="gesture-hud-badge" id="gestureBadge">⏩ +10s</div>

            <!-- HTML5 INLINE VIDEO PLAYER (Safari & iOS Playsinline Compatible, Container Fullscreen) -->
            <video id="mainWebPlayer" playsinline webkit-playsinline controls controlsList="nodownload nofullscreen" preload="auto" style="width:100%; height:100%; object-fit:contain; background:#000;">
                Your browser does not support inline video playback.
            </video>

            <!-- MOVI PLAYER CONTAINER (WASM/WebCodecs MKV Engine for iOS & modern browsers) -->
            <div id="moviPlayerContainer" style="display:none; width:100%; height:100%; position:relative; background:#000;"></div>

            <!-- IOS HELPER / FALLBACK NOTICE -->
            <div id="iosNotice" style="display:none; position:absolute; bottom:25px; left:50%; transform:translateX(-50%); z-index:35; background:rgba(15,23,42,0.92); border:1px solid var(--primary); border-radius:14px; padding:10px 18px; font-size:0.82rem; color:#fff; backdrop-filter:blur(12px); box-shadow:0 10px 30px rgba(0,0,0,0.8); text-align:center; max-width:92vw;">
                <span>🍎 iPhone Tip: If MKV does not start, tap <a id="iosVlcBtn" href="#" style="color:var(--primary); font-weight:700; text-decoration:underline;">VLC</a>, <a id="iosOutplayerBtn" href="#" style="color:var(--primary); font-weight:700; text-decoration:underline;">Outplayer</a> or <a id="iosDownloadBtn" href="#" download style="color:var(--primary); font-weight:700; text-decoration:underline;">Download</a></span>
            </div>
        </div>
    </div>

    <script>
        const IS_USER_LOGGED_IN = <?= json_encode($isUserLoggedIn) ?>;
        const USER_SESSION_TOKEN = <?= json_encode($userSessionToken) ?>;
        let allMoviesData = [];
        let currentAspectIndex = 0;
        const ASPECT_MODES = ['contain', 'cover', 'fill'];
        const ASPECT_LABELS = ['📺 Fit', '✂️ Zoom', '↔️ Fill'];

        function toggleUploader() {
            const card = document.getElementById('uploaderCard');
            card.style.display = card.style.display === 'block' ? 'none' : 'block';
        }

        function updateFileName(input) {
            const file = input.files[0];
            document.getElementById('selectedFileName').innerText = file ? `Selected: ${file.name} (${formatBytes(file.size)})` : '';
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Single Device Session Heartbeat (detects login on another device/browser/app)
        if (IS_USER_LOGGED_IN) {
            setInterval(async () => {
                try {
                    const checkRes = await fetch('api/user_session_check.php');
                    const checkData = await checkRes.json();
                    if (checkData.status === 'session_expired') {
                        alert('⚠️ Logged out because your account was logged in on another device!');
                        window.location.href = 'login.php?msg=session_expired';
                    }
                } catch (e) {}
            }, 10000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        async function fetchMovies() {
            try {
                const res = await fetch('api/movies.php?t=' + Date.now());
                const result = await res.json();
                const countEl = document.getElementById('movieCount');

                if (result.status === 'success') {
                    allMoviesData = result.data;
                    countEl.innerText = result.count;
                    renderMovies(allMoviesData);
                } else {
                    document.getElementById('moviesGrid').innerHTML = `<div style="color:#ef4444; grid-column:1/-1;">Failed to fetch content: ${result.message}</div>`;
                }
            } catch (err) {
                document.getElementById('moviesGrid').innerHTML = '<div style="color:#ef4444; grid-column:1/-1;">Error connecting to backend API.</div>';
            }
        }

        async function openSeriesEpisodes(seriesId, seriesTitle) {
            try {
                const res = await fetch('api/series.php?id=' + seriesId);
                const data = await res.json();
                if (data.status === 'success' && data.data && data.data.seasons) {
                    showEpisodesModal(data.data);
                } else {
                    alert((data && data.message) ? data.message : 'No episodes found for this series.');
                }
            } catch (e) {
                console.error('Error loading series details:', e);
                alert('Error loading series details: ' + (e.message || e));
            }
        }

        function renderMovies(items) {
            const grid = document.getElementById('moviesGrid');
            if (!items || items.length === 0) {
                grid.innerHTML = '<div style="color: var(--text-muted); grid-column: 1/-1;">No content found.</div>';
                return;
            }

            grid.innerHTML = items.map((m, index) => {
                const name = m.title || m.name || 'Untitled';
                const posterUrl = m.poster_url || `uploads/posters/${name}.jpg`;
                const isSeries = (m.type === 'series');

                if (isSeries) {
                    return `
                        <div class="movie-card">
                            <div class="poster-wrapper" onclick="openSeriesEpisodes(${m.id}, '${escapeHtml(name)}')">
                                <img src="${posterUrl}" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&q=80';" alt="${escapeHtml(name)}">
                                <div class="play-overlay">
                                    <div class="play-btn-circle"><i class="fa-solid fa-list"></i></div>
                                </div>
                            </div>
                            <div class="card-details">
                                <div class="movie-name">${escapeHtml(name)}</div>
                                <div class="movie-meta-info">
                                    <span><i class="fa-solid fa-star" style="color:#fbbf24;"></i> ${m.rating || '10'}</span>
                                    <button onclick="openSeriesEpisodes(${m.id}, '${escapeHtml(name)}')" style="background:var(--primary); color:#0f172a; border:none; padding:4px 10px; border-radius:12px; font-weight:800; font-size:0.75rem; cursor:pointer;">EPISODES</button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                return `
                    <div class="movie-card" onclick="openPlayerByIndex(${index})">
                        <div class="poster-wrapper">
                            <img src="${posterUrl}" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&q=80';" alt="${escapeHtml(name)}">
                            <div class="play-overlay">
                                <div class="play-btn-circle"><i class="fa-solid fa-play"></i></div>
                            </div>
                        </div>
                        <div class="card-details">
                            <div class="movie-name">${escapeHtml(name)}</div>
                            <div class="movie-meta-info">
                                <span><i class="fa-solid fa-hard-drive"></i> ${m.formatted_size || 'HD'}</span>
                                <span><i class="fa-solid fa-circle-play" style="color:var(--primary);"></i> Stream</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateFileName(input) {
            const file = input.files[0];
            document.getElementById('selectedFileName').innerText = file ? `Selected: ${file.name} (${formatBytes(file.size)})` : '';
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function toggleMobileSearch() {
            const bar = document.getElementById('mobileSearchBar');
            if (!bar) return;
            const isOpen = bar.classList.toggle('open');
            if (isOpen) {
                const input = document.getElementById('mobileSearchInput');
                if (input) {
                    input.value = document.getElementById('searchInput').value;
                    setTimeout(() => input.focus(), 100);
                }
            }
        }

        function handleMobileSearch(query) {
            const desk = document.getElementById('searchInput');
            if (desk) desk.value = query;
            filterMovies();
        }

        function filterMovies() {
            const deskInput = document.getElementById('searchInput');
            const mobInput = document.getElementById('mobileSearchInput');
            const query = (deskInput ? deskInput.value : (mobInput ? mobInput.value : '')).toLowerCase();
            if (deskInput && mobInput && deskInput.value !== mobInput.value) {
                if (document.activeElement === mobInput) deskInput.value = mobInput.value;
                else if (document.activeElement === deskInput) mobInput.value = deskInput.value;
            }
            const filtered = allMoviesData.filter(m => {
                const name = (m.title || m.name || '').toLowerCase();
                return name.includes(query);
            });
            renderMovies(filtered);
        }

        /* CUSTOM WEB PLAYER LOGIC WITH GESTURES & MEDIA SOURCE API DEMUXING */
        const playerModal = document.getElementById('playerModal');
        const video = document.getElementById('mainWebPlayer');
        const moviContainer = document.getElementById('moviPlayerContainer');
        const playerTitle = document.getElementById('playerTitle');
        const gestureBadge = document.getElementById('gestureBadge');
        const topBar = document.getElementById('playerTopBar');
        const speedMenu = document.getElementById('speedMenu');
        const iosNotice = document.getElementById('iosNotice');
        let currentMovieKey = '';
        let hideBarTimeout = null;
        let lastTapTime = 0;
        let holdTimeout = null;

        const isIosDevice = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        function getActivePlayer() {
            const movi = document.getElementById('mainMoviPlayer');
            if (movi && moviContainer && moviContainer.style.display !== 'none') {
                return movi;
            }
            return video;
        }

        function openPlayerByIndex(index) {
            if (!IS_USER_LOGGED_IN) {
                alert('🔐 Login Required: Please sign in or register to watch movies & web series.');
                window.location.href = 'login.php?msg=login_required';
                return;
            }

            const m = allMoviesData[index];
            if (!m) return;
            const movieTitle = m.title || m.name || 'Movie Stream';
            let rawStreamUrl = m.stream_url;
            if (window.location.protocol === 'https:') {
                rawStreamUrl = rawStreamUrl.replace(/^http:\/\//i, 'https://');
            }
            openPlayer(movieTitle, rawStreamUrl);
        }

        function openPlayer(title, rawStreamUrl) {
            playerTitle.innerText = title;
            currentMovieKey = 'web_pos_' + btoa(title).replace(/=/g, '');
            topBar.style.display = 'flex';
            topBar.style.opacity = '1';
            topBar.style.pointerEvents = 'auto';
            playerModal.classList.add('active');

            // Reset labels and close open menus
            document.getElementById('audioTrackText').innerText = 'Audio';
            document.getElementById('subtitleTrackText').innerText = 'Subtitles';
            document.getElementById('speedBtnText').innerText = '1.0x';
            closeAllMenus();
            updateFullscreenIcon();

            // Set Action Links
            document.getElementById('btnDownloadStream').href = rawStreamUrl;
            document.getElementById('iosDownloadBtn').href = rawStreamUrl;

            const fullUrl = rawStreamUrl.startsWith('http') ? rawStreamUrl : (window.location.origin + '/' + rawStreamUrl.replace(/^\//, ''));
            const vlcUrl = isIosDevice 
                ? ('vlc-x-callback://x-callback-url/stream?url=' + encodeURIComponent(fullUrl))
                : ('vlc://' + fullUrl);
            const outplayerUrl = 'outplayer://' + fullUrl.replace(/^https?:\/\//i, '');

            document.getElementById('btnVlcAction').href = vlcUrl;
            document.getElementById('iosVlcBtn').href = vlcUrl;
            document.getElementById('btnOutplayerAction').href = outplayerUrl;
            document.getElementById('iosOutplayerBtn').href = outplayerUrl;

            // Only show Outplayer button on iOS devices
            document.getElementById('btnOutplayerAction').style.display = isIosDevice ? 'flex' : 'none';

            const isMkv = title.toLowerCase().endsWith('.mkv') || rawStreamUrl.toLowerCase().includes('.mkv');

            if (isMkv) {
                // Matroska container: Use Movi Player WebAssembly engine
                video.pause();
                video.src = '';
                video.style.display = 'none';

                moviContainer.style.display = 'block';
                moviContainer.innerHTML = `<movi-player id="mainMoviPlayer" src="${rawStreamUrl}" playsinline controls fallback="native" style="width:100%; height:100%; object-fit:contain; background:#000;"></movi-player>`;

                showGestureBadge('▶ Streaming MKV Engine');

                if (isIosDevice && iosNotice) {
                    iosNotice.style.display = 'block';
                    setTimeout(() => { if (iosNotice) iosNotice.style.display = 'none'; }, 9000);
                }
            } else {
                // Native MP4 / WebM video player
                moviContainer.innerHTML = '';
                moviContainer.style.display = 'none';
                if (iosNotice) iosNotice.style.display = 'none';

                video.style.display = 'block';
                video.src = rawStreamUrl;
                video.load();
                video.play().catch(e => {
                    video.muted = true;
                    video.play().catch(err => console.log('iOS Autoplay handling:', err));
                    showGestureBadge('🔊 Tap screen to Unmute Audio');
                });
            }

            // Restore position
            const savedPos = localStorage.getItem(currentMovieKey);
            if (savedPos && parseFloat(savedPos) > 3) {
                setTimeout(() => {
                    try {
                        const curEl = getActivePlayer();
                        if (curEl && 'currentTime' in curEl) curEl.currentTime = parseFloat(savedPos);
                        showGestureBadge(`▶ Resumed watching`);
                    } catch(e) {}
                }, 500);
            }

            // Start Real-Time Live Playback Ping for Admin Dashboard
            startLivePing(title);

            resetHideBarTimer();
        }

        let livePingInterval = null;
        function startLivePing(movieTitle) {
            stopLivePing();
            const sendPing = () => {
                const formData = new FormData();
                formData.append('title', movieTitle);
                formData.append('device', isIosDevice ? 'iOS Browser' : 'Web Browser');
                fetch('api/stream_ping.php', { method: 'POST', body: formData }).catch(e => {});
            };
            sendPing();
            livePingInterval = setInterval(sendPing, 5000);
        }

        function stopLivePing() {
            if (livePingInterval) {
                clearInterval(livePingInterval);
                livePingInterval = null;
            }
            const formData = new FormData();
            formData.append('action', 'stop');
            fetch('api/stream_ping.php', { method: 'POST', body: formData }).catch(e => {});
        }

        function closePlayer() {
            stopLivePing();
            const curEl = getActivePlayer();
            if (curEl && curEl.currentTime > 3 && curEl.duration && (curEl.duration - curEl.currentTime > 10)) {
                localStorage.setItem(currentMovieKey, curEl.currentTime);
            }
            video.pause();
            video.src = '';
            video.style.display = 'none';

            if (moviContainer) {
                const movi = document.getElementById('mainMoviPlayer');
                if (movi) {
                    try { movi.pause(); movi.src = ''; } catch(e){}
                }
                moviContainer.innerHTML = '';
                moviContainer.style.display = 'none';
            }

            if (iosNotice) iosNotice.style.display = 'none';
            playerModal.classList.remove('active');
            closeAllMenus();

            // Exit fullscreen if active
            if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
                if (document.exitFullscreen) document.exitFullscreen().catch(e => {});
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
                else if (document.msExitFullscreen) document.msExitFullscreen();
            }
        }

        // UNIFIED MENUS CONTROLLER
        function closeAllMenus() {
            const menuIds = ['speedMenu', 'audioMenu', 'subtitleMenu', 'moreMenu'];
            menuIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            document.querySelectorAll('.btn-player-action').forEach(b => {
                if (b.id !== 'btnFsToggle') b.classList.remove('active');
            });
        }

        function isAnyMenuOpen() {
            const menuIds = ['speedMenu', 'audioMenu', 'subtitleMenu', 'moreMenu'];
            return menuIds.some(id => {
                const el = document.getElementById(id);
                return el && el.style.display === 'flex';
            });
        }

        function toggleMenu(menuId, triggerBtnId) {
            const menu = document.getElementById(menuId);
            if (!menu) return;
            const wasOpen = (menu.style.display === 'flex');
            closeAllMenus();
            if (!wasOpen) {
                menu.style.display = 'flex';
                if (triggerBtnId) {
                    const btn = document.getElementById(triggerBtnId);
                    if (btn) btn.classList.add('active');
                }
                resetHideBarTimer();
            }
        }

        function toggleAudioMenu() {
            toggleMenu('audioMenu', 'btnAudioTrack');
            populateAudioTracks();
        }

        function toggleSubtitleMenu() {
            toggleMenu('subtitleMenu', 'btnSubtitleTrack');
            populateSubtitleTracks();
        }

        function toggleSpeedMenu() {
            toggleMenu('speedMenu', 'btnSpeedToggle');
        }

        function toggleMoreMenu() {
            toggleMenu('moreMenu', 'btnMoreMenuToggle');
        }

        // POPULATE AUDIO TRACKS
        function populateAudioTracks() {
            const list = document.getElementById('audioMenuList');
            if (!list) return;
            list.innerHTML = '';

            const curEl = getActivePlayer();
            let tracksFound = false;

            // HTML5 Video audioTracks API (Safari / compliant modern browsers)
            if (curEl && curEl.audioTracks && curEl.audioTracks.length > 0) {
                tracksFound = true;
                for (let i = 0; i < curEl.audioTracks.length; i++) {
                    const track = curEl.audioTracks[i];
                    const label = track.label || (track.language ? track.language.toUpperCase() : `Track #${i+1}`);
                    const isSelected = track.enabled;
                    const item = document.createElement('div');
                    item.className = 'speed-item' + (isSelected ? ' active' : '');
                    item.innerHTML = `${isSelected ? '<i class="fa-solid fa-check check-icon"></i> ' : ''}<span>${escapeHtml(label)}</span>`;
                    item.onclick = () => {
                        for (let j = 0; j < curEl.audioTracks.length; j++) {
                            curEl.audioTracks[j].enabled = (j === i);
                        }
                        document.getElementById('audioTrackText').innerText = label;
                        closeAllMenus();
                        showGestureBadge(`🔊 Audio: ${label}`);
                    };
                    list.appendChild(item);
                }
            }

            // Movi Player custom element audioTracks (MKV WebAssembly Engine)
            const movi = document.getElementById('mainMoviPlayer');
            if (movi && movi.audioTracks && movi.audioTracks.length > 0) {
                tracksFound = true;
                movi.audioTracks.forEach((track, i) => {
                    const label = track.label || (track.language ? track.language.toUpperCase() : `Track #${i+1}`);
                    const isSelected = track.enabled;
                    const item = document.createElement('div');
                    item.className = 'speed-item' + (isSelected ? ' active' : '');
                    item.innerHTML = `${isSelected ? '<i class="fa-solid fa-check check-icon"></i> ' : ''}<span>${escapeHtml(label)}</span>`;
                    item.onclick = () => {
                        if (typeof movi.setAudioTrack === 'function') movi.setAudioTrack(i);
                        document.getElementById('audioTrackText').innerText = label;
                        closeAllMenus();
                        showGestureBadge(`🔊 Audio: ${label}`);
                    };
                    list.appendChild(item);
                });
            }

            if (!tracksFound) {
                const defaultItem = document.createElement('div');
                defaultItem.className = 'speed-item active';
                defaultItem.innerHTML = '<i class="fa-solid fa-check check-icon"></i> <span>Default Audio</span>';
                defaultItem.onclick = () => { closeAllMenus(); };
                list.appendChild(defaultItem);

                const infoTip = document.createElement('div');
                infoTip.style.cssText = 'padding: 8px 14px; font-size: 0.72rem; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.06); line-height: 1.3;';
                infoTip.innerText = 'Multi-audio tracks are extracted automatically for MKV and supported streams.';
                list.appendChild(infoTip);
            }
        }

        // POPULATE SUBTITLE TRACKS
        function populateSubtitleTracks() {
            const list = document.getElementById('subtitleMenuList');
            if (!list) return;
            list.innerHTML = '';

            const curEl = getActivePlayer();
            let tracksFound = false;
            let anyShowing = false;

            const textTracks = (curEl && curEl.textTracks) ? Array.from(curEl.textTracks).filter(t => t.kind === 'subtitles' || t.kind === 'captions') : [];
            const movi = document.getElementById('mainMoviPlayer');
            const moviTracks = (movi && movi.textTracks) ? Array.from(movi.textTracks) : [];

            // Off Option
            const offItem = document.createElement('div');
            offItem.className = 'speed-item';
            offItem.innerHTML = '<span>Off</span>';
            offItem.onclick = () => selectSubtitleTrack(-1);
            list.appendChild(offItem);

            if (textTracks.length > 0) {
                tracksFound = true;
                textTracks.forEach((track, i) => {
                    const label = track.label || (track.language ? track.language.toUpperCase() : `Subtitle #${i+1}`);
                    const isSelected = (track.mode === 'showing');
                    if (isSelected) anyShowing = true;

                    const item = document.createElement('div');
                    item.className = 'speed-item' + (isSelected ? ' active' : '');
                    item.innerHTML = `${isSelected ? '<i class="fa-solid fa-check check-icon"></i> ' : ''}<span>${escapeHtml(label)}</span>`;
                    item.onclick = () => {
                        textTracks.forEach((t, idx) => {
                            t.mode = (idx === i) ? 'showing' : 'disabled';
                        });
                        document.getElementById('subtitleTrackText').innerText = label;
                        closeAllMenus();
                        showGestureBadge(`💬 Subtitle: ${label}`);
                    };
                    list.appendChild(item);
                });
            }

            if (moviTracks.length > 0) {
                tracksFound = true;
                moviTracks.forEach((track, i) => {
                    const label = track.label || (track.language ? track.language.toUpperCase() : `Subtitle #${i+1}`);
                    const isSelected = (track.mode === 'showing');
                    if (isSelected) anyShowing = true;

                    const item = document.createElement('div');
                    item.className = 'speed-item' + (isSelected ? ' active' : '');
                    item.innerHTML = `${isSelected ? '<i class="fa-solid fa-check check-icon"></i> ' : ''}<span>${escapeHtml(label)}</span>`;
                    item.onclick = () => {
                        if (typeof movi.setSubtitleTrack === 'function') movi.setSubtitleTrack(i);
                        document.getElementById('subtitleTrackText').innerText = label;
                        closeAllMenus();
                        showGestureBadge(`💬 Subtitle: ${label}`);
                    };
                    list.appendChild(item);
                });
            }

            if (!anyShowing) {
                offItem.classList.add('active');
                offItem.innerHTML = '<i class="fa-solid fa-check check-icon"></i> <span>Off</span>';
            }

            if (!tracksFound) {
                const noSubTip = document.createElement('div');
                noSubTip.style.cssText = 'padding: 8px 14px; font-size: 0.72rem; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.06); line-height: 1.3;';
                noSubTip.innerText = 'No subtitle tracks found in this stream.';
                list.appendChild(noSubTip);
            }
        }

        function selectSubtitleTrack(index) {
            const curEl = getActivePlayer();
            if (curEl && curEl.textTracks) {
                for (let i = 0; i < curEl.textTracks.length; i++) {
                    curEl.textTracks[i].mode = (i === index) ? 'showing' : 'disabled';
                }
            }
            const movi = document.getElementById('mainMoviPlayer');
            if (movi && typeof movi.setSubtitleTrack === 'function') {
                movi.setSubtitleTrack(index);
            }
            document.getElementById('subtitleTrackText').innerText = 'Subtitles';
            closeAllMenus();
            showGestureBadge(index === -1 ? '💬 Subtitles Off' : '💬 Subtitles Selected');
        }

        // Set Playback Rate
        function setSpeed(rate) {
            const curEl = getActivePlayer();
            if (curEl) curEl.playbackRate = rate;
            document.getElementById('speedBtnText').innerText = `${rate}x`;
            closeAllMenus();
            showGestureBadge(`⚡ Speed: ${rate}x`);

            document.querySelectorAll('#speedMenu .speed-item').forEach(el => {
                const isActive = el.innerText.includes(`${rate}x`);
                el.classList.toggle('active', isActive);
                if (isActive && !el.querySelector('.check-icon')) {
                    el.innerHTML = `<i class="fa-solid fa-check check-icon"></i> ${el.innerText.trim()}`;
                } else if (!isActive && el.querySelector('.check-icon')) {
                    el.innerHTML = el.innerText.trim();
                }
            });
        }

        // CONTAINER FULLSCREEN CONTROLLER (Keeps HTML UI controls visible on mobile)
        async function toggleContainerFullscreen() {
            const container = document.getElementById('videoContainer');
            const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);

            if (!isFs) {
                try {
                    if (container.requestFullscreen) {
                        await container.requestFullscreen();
                    } else if (container.webkitRequestFullscreen) {
                        await container.webkitRequestFullscreen();
                    } else if (container.mozRequestFullScreen) {
                        await container.mozRequestFullScreen();
                    } else if (container.msRequestFullscreen) {
                        await container.msRequestFullscreen();
                    }
                    if (screen.orientation && screen.orientation.lock) {
                        screen.orientation.lock('landscape').catch(() => {});
                    }
                } catch (e) {
                    console.log('Fullscreen request notice:', e);
                    showGestureBadge('📱 Rotate device to Landscape');
                }
            } else {
                try {
                    if (document.exitFullscreen) {
                        await document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        await document.webkitExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        await document.mozCancelFullScreen();
                    } else if (document.msExitFullscreen) {
                        await document.msExitFullscreen();
                    }
                    if (screen.orientation && screen.orientation.unlock) {
                        screen.orientation.unlock();
                    }
                } catch (e) {}
            }
            updateFullscreenIcon();
        }

        function updateFullscreenIcon() {
            const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
            const fsIcon = document.getElementById('fsIcon');
            if (fsIcon) {
                if (isFs) {
                    fsIcon.classList.remove('fa-expand');
                    fsIcon.classList.add('fa-compress');
                } else {
                    fsIcon.classList.remove('fa-compress');
                    fsIcon.classList.add('fa-expand');
                }
            }
        }

        document.addEventListener('fullscreenchange', updateFullscreenIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
        document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
        document.addEventListener('MSFullscreenChange', updateFullscreenIcon);

        // Picture-in-Picture (PiP)
        async function togglePip() {
            closeAllMenus();
            const curEl = getActivePlayer();
            try {
                if (document.pictureInPictureElement) {
                    await document.exitPictureInPicture();
                } else if (curEl && curEl.requestPictureInPicture) {
                    await curEl.requestPictureInPicture();
                }
            } catch(err) {
                alert('Picture-in-Picture is not supported by your browser.');
            }
        }

        // Aspect Ratio Toggle
        function toggleAspectRatio() {
            currentAspectIndex = (currentAspectIndex + 1) % ASPECT_MODES.length;
            const curEl = getActivePlayer();
            if (curEl) curEl.style.objectFit = ASPECT_MODES[currentAspectIndex];
            document.getElementById('aspectText').innerText = ASPECT_LABELS[currentAspectIndex];
            showGestureBadge(`Aspect Ratio: ${ASPECT_LABELS[currentAspectIndex]}`);
        }

        // Show HUD Badge
        function showGestureBadge(text) {
            gestureBadge.innerText = text;
            gestureBadge.classList.add('show');
            setTimeout(() => gestureBadge.classList.remove('show'), 1200);
        }

        function formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60);
            return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
        }

        // Auto-Hide Control Bar (Does not hide when user is browsing audio/subtitles/speed/more menus)
        function resetHideBarTimer() {
            topBar.style.opacity = '1';
            topBar.style.pointerEvents = 'auto';
            clearTimeout(hideBarTimeout);
            hideBarTimeout = setTimeout(() => {
                const curEl = getActivePlayer();
                if (curEl && !curEl.paused && !isAnyMenuOpen()) {
                    topBar.style.opacity = '0';
                    topBar.style.pointerEvents = 'none';
                }
            }, 3500);
        }

        document.getElementById('videoContainer').addEventListener('mousemove', resetHideBarTimer);
        document.getElementById('videoContainer').addEventListener('touchstart', resetHideBarTimer);

        function seekNativeVideo(targetTime) {
            const curEl = getActivePlayer();
            targetTime = Math.max(0, Math.min((curEl && curEl.duration) || targetTime, targetTime));
            if (curEl && 'fastSeek' in curEl) {
                try { curEl.fastSeek(targetTime); }
                catch(e) { curEl.currentTime = targetTime; }
            } else if (curEl) {
                curEl.currentTime = targetTime;
            }
            if (curEl && curEl.play) curEl.play().catch(e => {});
        }

        // GESTURE CONTROLS: Double Click/Tap (-10s / +10s) & Hold (1.5x Speed Boost)
        const container = document.getElementById('videoContainer');

        container.addEventListener('click', (e) => {
            if (e.target.closest('#playerTopBar') || e.target.closest('.player-dropdown-menu') || e.target.closest('#iosNotice')) return;

            // If a dropdown menu is open, tapping outside closes it
            if (isAnyMenuOpen()) {
                closeAllMenus();
                return;
            }

            // If the top bar is hidden, single tap reveals it
            if (topBar.style.opacity === '0') {
                resetHideBarTimer();
                return;
            }

            const now = Date.now();
            const rect = container.getBoundingClientRect();
            const clickX = e.clientX - rect.left;

            if (now - lastTapTime < 300) {
                // Double Click / Tap detected
                const curEl = getActivePlayer();
                const curTime = curEl ? curEl.currentTime : 0;
                if (clickX < rect.width / 2) {
                    seekNativeVideo(curTime - 10);
                    showGestureBadge('⏪ -10s');
                } else {
                    seekNativeVideo(curTime + 10);
                    showGestureBadge('⏩ +10s');
                }
            } else {
                resetHideBarTimer();
            }
            lastTapTime = now;
        });

        // Touch & Hold / Mouse Hold for 1.5x Speed
        const startSpeedBoost = (e) => {
            if (e.target.closest('#playerTopBar') || e.target.closest('.player-dropdown-menu') || e.target.closest('#iosNotice')) return;
            holdTimeout = setTimeout(() => {
                const curEl = getActivePlayer();
                if (curEl) curEl.playbackRate = 1.5;
                showGestureBadge('⚡ 1.5x Speed Boost');
            }, 400);
        };

        const stopSpeedBoost = () => {
            clearTimeout(holdTimeout);
            const curEl = getActivePlayer();
            if (curEl && curEl.playbackRate === 1.5 && document.getElementById('speedBtnText').innerText === '1.0x') {
                curEl.playbackRate = 1.0;
                showGestureBadge('▶ 1.0x Normal Speed');
            }
        };

        container.addEventListener('mousedown', startSpeedBoost);
        container.addEventListener('mouseup', stopSpeedBoost);
        container.addEventListener('touchstart', startSpeedBoost);
        container.addEventListener('touchend', stopSpeedBoost);

        // FULL KEYBOARD SHORTCUTS
        document.addEventListener('keydown', (e) => {
            if (!playerModal.classList.contains('active')) return;
            if (e.target.tagName === 'INPUT') return;

            const curEl = getActivePlayer();

            switch(e.key.toLowerCase()) {
                case ' ':
                case 'k':
                    e.preventDefault();
                    if (curEl) {
                        curEl.paused ? curEl.play() : curEl.pause();
                        showGestureBadge(curEl.paused ? '⏸ Pause' : '▶ Play');
                    }
                    break;
                case 'f':
                    e.preventDefault();
                    toggleContainerFullscreen();
                    break;
                case 'm':
                    e.preventDefault();
                    if (curEl) {
                        curEl.muted = !curEl.muted;
                        showGestureBadge(curEl.muted ? '🔇 Muted' : '🔊 Unmuted');
                    }
                    break;
                case 'arrowleft':
                case 'j':
                    e.preventDefault();
                    if (curEl) {
                        seekNativeVideo(curEl.currentTime - 10);
                        showGestureBadge('⏪ -10s');
                    }
                    break;
                case 'arrowright':
                case 'l':
                    e.preventDefault();
                    if (curEl) {
                        seekNativeVideo(curEl.currentTime + 10);
                        showGestureBadge('⏩ +10s');
                    }
                    break;
                case 'arrowup':
                    e.preventDefault();
                    if (curEl) {
                        curEl.volume = Math.min(1.0, curEl.volume + 0.1);
                        showGestureBadge(`🔊 Vol: ${Math.round(curEl.volume * 100)}%`);
                    }
                    break;
                case 'arrowdown':
                    e.preventDefault();
                    if (curEl) {
                        curEl.volume = Math.max(0.0, curEl.volume - 0.1);
                        showGestureBadge(`🔊 Vol: ${Math.round(curEl.volume * 100)}%`);
                    }
                    break;
                case 'escape':
                    closePlayer();
                    break;
            }
        });

        // Save playback position periodically
        setInterval(() => {
            const curEl = getActivePlayer();
            if (curEl && !curEl.paused && curEl.currentTime > 5) {
                localStorage.setItem(currentMovieKey, curEl.currentTime);
            }
        }, 5000);

        /* SMART CHUNKED UPLOADER SCRIPT */
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            if (!file) return alert('Select a file first!');

            const CHUNK_SIZE = 8 * 1024 * 1024;
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
            const fileId = Date.now() + '_' + Math.random().toString(36).substring(2, 8);
            const startTime = Date.now();
            let bytesUploadedBefore = 0;

            document.getElementById('progressBg').style.display = 'block';
            const fill = document.getElementById('progressFill');
            const status = document.getElementById('uploadStatus');

            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end = Math.min(file.size, start + CHUNK_SIZE);
                const chunk = file.slice(start, end);

                const fd = new FormData();
                fd.append('file', chunk, file.name);
                fd.append('chunk_index', i);
                fd.append('total_chunks', totalChunks);
                fd.append('file_name', file.name);
                fd.append('file_id', fileId);

                try {
                    const res = await uploadChunk('api/upload_chunk.php', fd, (loaded) => {
                        const totalUploaded = bytesUploadedBefore + loaded;
                        const percent = Math.min(99, Math.round((totalUploaded / file.size) * 100));
                        const speed = ((totalUploaded / (1024 * 1024)) / ((Date.now() - startTime) / 1000)).toFixed(2);
                        fill.style.width = percent + '%';
                        status.style.color = 'var(--primary)';
                        status.innerText = `Uploading Chunk ${i+1}/${totalChunks} (${percent}%) - ${speed} MB/s`;
                    });

                    if (res.status === 'success') {
                        fill.style.width = '100%';
                        status.style.color = '#22c55e';
                        status.innerText = '✅ Upload Complete!';
                        setTimeout(() => { toggleUploader(); fetchMovies(); }, 2000);
                        return;
                    }
                    bytesUploadedBefore += chunk.size;
                } catch (err) {
                    status.style.color = '#ef4444';
                    status.innerText = '❌ Upload Error: ' + err.message;
                    return;
                }
            }
        });

        function uploadChunk(url, formData, onProgress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.upload.onprogress = (e) => { if (e.lengthComputable) onProgress(e.loaded); };
                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) resolve(JSON.parse(xhr.responseText));
                    else reject(new Error(`HTTP ${xhr.status}`));
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(formData);
            });
        }

        fetchMovies();
    </script>

    <!-- SUGGESTION / REQUEST MODAL -->
    <div id="suggestionModal" style="position: fixed; inset: 0; z-index: 1050; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #131b2e; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 20px; width: 100%; max-width: 520px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.9); position: relative; color: #fff;">
            <button onclick="closeSuggestionModal()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem;">✕</button>
            
            <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> Request Movie or Suggestion
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                Have a movie request or suggestion to improve our app/website? Let us know!
            </p>

            <form id="suggestionForm" onsubmit="submitUserSuggestion(event)">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #94a3b8; margin-bottom: 6px;">Select Category *</label>
                    <select id="sugCategory" required style="width: 100%; padding: 10px 14px; border-radius: 10px; background: #090d16; border: 1px solid var(--border-color); color: #fff; font-size: 0.9rem; outline: none;">
                        <option value="Movie / Web Series Request">🎬 Movie / Web Series Request</option>
                        <option value="App / Website Feature Improvement">🚀 App / Website Feature Improvement</option>
                        <option value="Technical Bug / Video Issue">🐛 Technical Bug / Video Playback Issue</option>
                        <option value="General Feedback">💬 General Feedback</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #94a3b8; margin-bottom: 6px;">Your Name</label>
                        <input type="text" id="sugName" value="<?= htmlspecialchars($userName) ?>" placeholder="Enter your name" style="width: 100%; padding: 10px 14px; border-radius: 10px; background: #090d16; border: 1px solid var(--border-color); color: #fff; font-size: 0.9rem; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #94a3b8; margin-bottom: 6px;">Email / Mobile</label>
                        <input type="text" id="sugContact" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" placeholder="Contact details" style="width: 100%; padding: 10px 14px; border-radius: 10px; background: #090d16; border: 1px solid var(--border-color); color: #fff; font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #94a3b8; margin-bottom: 6px;">Your Suggestion / Request Message *</label>
                    <textarea id="sugText" rows="4" required placeholder="Write your movie name, episode details, or feedback here..." style="width: 100%; padding: 12px; border-radius: 10px; background: #090d16; border: 1px solid var(--border-color); color: #fff; font-size: 0.9rem; outline: none; resize: vertical;"></textarea>
                </div>

                <div id="sugAlert" style="display: none; margin-bottom: 14px; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; font-weight: 600;"></div>

                <button type="submit" id="btnSubmitSug" style="width: 100%; padding: 12px; border-radius: 12px; background: linear-gradient(135deg, #0284c7, #38bdf8); border: none; color: #0f172a; font-weight: 800; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </form>
        </div>
    </div>

    <script>
        function openSuggestionModal() {
            document.getElementById('suggestionModal').style.display = 'flex';
        }
        function closeSuggestionModal() {
            document.getElementById('suggestionModal').style.display = 'none';
        }
        async function submitUserSuggestion(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitSug');
            const alertBox = document.getElementById('sugAlert');
            const category = document.getElementById('sugCategory').value;
            const name = document.getElementById('sugName').value;
            const contact = document.getElementById('sugContact').value;
            const text = document.getElementById('sugText').value;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            alertBox.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('category', category);
                formData.append('name', name);
                formData.append('email', contact);
                formData.append('suggestion_text', text);

                const res = await fetch('api/submit_suggestion.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.status === 'success') {
                    alertBox.style.background = 'rgba(34, 197, 94, 0.15)';
                    alertBox.style.color = '#4ade80';
                    alertBox.style.border = '1px solid rgba(34, 197, 94, 0.3)';
                    alertBox.innerText = data.message;
                    alertBox.style.display = 'block';
                    document.getElementById('sugText').value = '';
                    setTimeout(() => {
                        closeSuggestionModal();
                        alertBox.style.display = 'none';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to submit suggestion.');
                }
            } catch (err) {
                alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                alertBox.style.color = '#f87171';
                alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                alertBox.innerText = err.message;
                alertBox.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
            }
        }
    </script>

    <!-- WEB SERIES EPISODES MODAL -->
    <div id="episodesModal" style="position: fixed; inset: 0; z-index: 1050; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #131b2e; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 20px; width: 100%; max-width: 600px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.9); position: relative; color: #fff; max-height: 85vh; overflow-y: auto;">
            <button onclick="closeEpisodesModal()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem;">✕</button>
            
            <h3 id="epModalSeriesTitle" style="font-size: 1.25rem; font-weight: 800; margin-bottom: 4px; color: #fff;">
                Series Title
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Select Season &amp; Episode to play</p>

            <div id="epModalContent"></div>
        </div>
    </div>

    <script>
        let currentSeriesData = null;

        function showEpisodesModal(seriesData) {
            currentSeriesData = seriesData;
            document.getElementById('epModalSeriesTitle').innerText = seriesData.title || 'Web Series';
            const content = document.getElementById('epModalContent');

            if (!seriesData.seasons || seriesData.seasons.length === 0) {
                content.innerHTML = '<div style="color:var(--text-muted); padding:20px; text-align:center;">No seasons found.</div>';
            } else {
                content.innerHTML = seriesData.seasons.map((s, sIndex) => `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color:var(--primary); font-size:1rem; font-weight:700; margin-bottom:10px; border-bottom:1px solid var(--border-color); padding-bottom:6px;">
                            ${escapeHtml(s.title || 'Season ' + s.season_number)}
                        </h4>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            ${(s.episodes || []).map((ep, epIndex) => `
                                <div onclick="playEpisodeByIndices(${sIndex}, ${epIndex})" style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); border:1px solid var(--border-color); padding:12px 16px; border-radius:12px; cursor:pointer; transition:0.2s;">
                                    <div>
                                        <strong style="color:#fff; font-size:0.92rem;">Ep ${ep.episode_number}: ${escapeHtml(ep.title)}</strong>
                                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">Duration: ${escapeHtml(ep.duration || '45m')}</div>
                                    </div>
                                    <button style="background:var(--primary); color:#0f172a; border:none; padding:6px 14px; border-radius:20px; font-weight:800; font-size:0.8rem; cursor:pointer;">PLAY ▶</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `).join('');
            }

            document.getElementById('episodesModal').style.display = 'flex';
        }

        function closeEpisodesModal() {
            document.getElementById('episodesModal').style.display = 'none';
        }

        function playEpisodeByIndices(sIndex, epIndex) {
            if (!IS_USER_LOGGED_IN) {
                alert('🔐 Login Required: Please sign in or register to watch movies & web series.');
                window.location.href = 'login.php?msg=login_required';
                return;
            }
            if (!currentSeriesData || !currentSeriesData.seasons) return;
            const season = currentSeriesData.seasons[sIndex];
            if (!season || !season.episodes) return;
            const ep = season.episodes[epIndex];
            if (!ep) return;

            closeEpisodesModal();

            const title = `${currentSeriesData.title} - S${season.season_number}E${ep.episode_number}: ${ep.title}`;
            let rawStreamUrl = ep.stream_url || '';
            if (window.location.protocol === 'https:') {
                rawStreamUrl = rawStreamUrl.replace(/^http:\/\//i, 'https://');
            }
            openPlayer(title, rawStreamUrl);
        }

        function playEpisode(url, title) {
            if (!IS_USER_LOGGED_IN) {
                alert('🔐 Login Required: Please sign in or register to watch movies & web series.');
                window.location.href = 'login.php?msg=login_required';
                return;
            }
            closeEpisodesModal();
            let rawStreamUrl = url || '';
            if (window.location.protocol === 'https:') {
                rawStreamUrl = rawStreamUrl.replace(/^http:\/\//i, 'https://');
            }
            openPlayer(title, rawStreamUrl);
        }
    </script>
</body>
</html>
