<?php
// backend/admin/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

if (!isset($_SESSION['admin_logged_in']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit();
}

$adminSiteTitle = get_site_setting($pdo, 'site_title', 'Soni Cinemas');
$adminSiteFavicon = get_site_setting($pdo, 'site_favicon', '');
$adminSiteLogo = get_site_setting($pdo, 'site_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($adminSiteTitle) ?> - Admin Panel</title>
    <?php if ($adminSiteFavicon): ?>
        <link rel="icon" href="../<?= htmlspecialchars($adminSiteFavicon) ?>">
    <?php endif; ?>
    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: #131b2e;
            --bg-header: rgba(15, 23, 42, 0.95);
            --bg-sidebar: #0f172a;
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.35);
            --accent: #0284c7;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --radius-lg: 16px;
            --radius-md: 10px;
            --sidebar-width: 250px;
            --header-height: 64px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }

        /* BRAND */
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand-logo {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .brand-logo i { color: #fff; font-size: 1.1rem; }
        .brand-title { font-weight: 800; font-size: 1.25rem; color: #fff; letter-spacing: -0.5px; }
        .brand-title span { color: var(--primary); }

        /* TOP NAVIGATION BAR */
        .top-navbar {
            height: var(--header-height); background-color: var(--bg-header);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 0 20px; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
        }
        .top-nav-left { display: flex; align-items: center; gap: 16px; }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.06); border: 1px solid var(--border-color);
            color: #fff; width: 40px; height: 40px; border-radius: 10px; font-size: 1.1rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
        }
        .toggle-btn:hover { background: rgba(56, 189, 248, 0.15); color: var(--primary); border-color: rgba(56, 189, 248, 0.3); }

        .top-nav-right { display: flex; align-items: center; gap: 14px; }
        .admin-profile-badge {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 14px; background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color); border-radius: 20px;
        }
        .admin-profile-badge .profile-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(56, 189, 248, 0.15); color: var(--primary);
            display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .admin-profile-badge .profile-info { display: flex; flex-direction: column; text-align: left; }
        .admin-profile-badge .profile-name { font-weight: 700; font-size: 0.85rem; color: #fff; line-height: 1.1; }
        .admin-profile-badge .profile-role { font-size: 0.7rem; color: var(--text-muted); }

        /* LAYOUT & SIDEBAR STYLES */
        .admin-layout { display: flex; flex: 1; position: relative; }

        .sidebar {
            width: var(--sidebar-width); background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color); display: flex; flex-direction: column;
            position: fixed; top: var(--header-height); bottom: 0; left: 0; z-index: 90;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.collapsed { transform: translateX(-260px); }

        .sidebar-nav { padding: 16px 12px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .sidebar-nav a {
            color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.88rem;
            padding: 12px 16px; border-radius: 12px; transition: all 0.2s ease;
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-nav a i { font-size: 1.1rem; width: 22px; text-align: center; color: var(--text-muted); transition: color 0.2s ease; }
        .sidebar-nav a:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
        .sidebar-nav a:hover i { color: var(--primary); }
        .sidebar-nav a.active {
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.25), rgba(56, 189, 248, 0.15));
            color: var(--primary); border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .sidebar-nav a.active i { color: var(--primary); }
        .sidebar-nav a.logout-btn { margin-top: auto; color: #f87171; }
        .sidebar-nav a.logout-btn i { color: #f87171; }
        .sidebar-nav a.logout-btn:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

        .sidebar-divider { height: 1px; background: var(--border-color); margin: 12px 6px; }
        .sidebar-backdrop {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 80;
        }

        .main-content {
            flex: 1; margin-left: var(--sidebar-width); min-height: calc(100vh - var(--header-height));
            display: flex; flex-direction: column; width: calc(100% - var(--sidebar-width));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .main-content.expanded { margin-left: 0; width: 100%; }

        /* CONTAINER & CARDS */
        .container { max-width: 1180px; width: 100%; margin: 24px auto; padding: 0 20px; flex: 1; }
        .card {
            background-color: var(--bg-card); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px;
            border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .card h2 { color: #fff; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: var(--primary); }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: var(--text-muted); font-weight: 600; font-size: 0.85rem; }
        .form-control {
            width: 100%; padding: 11px 16px; background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border-color); border-radius: var(--radius-md); color: #fff; font-size: 0.92rem;
            transition: 0.2s ease;
        }
        .form-control:focus { border-color: var(--primary); outline: none; background: rgba(15, 23, 42, 0.95); box-shadow: 0 0 12px var(--primary-glow); }
        textarea.form-control { resize: vertical; min-height: 90px; }
        select.form-control { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%3c38bdf8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; padding-right: 40px; }

        /* BUTTONS */
        .btn {
            background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border: none;
            padding: 10px 20px; border-radius: 20px; font-weight: 600; font-size: 0.88rem; cursor: pointer;
            transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; justify-content: center;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); min-height: 42px;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4); }
        .btn-secondary { background: rgba(255, 255, 255, 0.08); color: var(--text-main); box-shadow: none; border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.15); }
        .btn-danger { background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .btn-danger:hover { box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4); }
        .btn-series { background: linear-gradient(135deg, #d97706, #b45309); box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }
        .btn-sm { padding: 6px 12px; font-size: 0.78rem; min-height: 32px; border-radius: 14px; }

        /* GRIDS & CARDS */
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .stat-card {
            background: var(--bg-card); padding: 22px; border-radius: var(--radius-lg);
            border: 1px solid var(--border-color); text-align: left; position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between; transition: 0.25s ease;
        }
        .stat-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 10px 25px rgba(0,0,0,0.5), 0 0 15px var(--primary-glow); }
        .stat-icon { position: absolute; right: 20px; top: 20px; font-size: 2.2rem; opacity: 0.2; color: var(--primary); }
        .stat-number { font-size: 2.4rem; font-weight: 800; color: #fff; margin: 10px 0; letter-spacing: -1px; }

        /* TABLE & RESPONSIVE WRAPPER */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: var(--radius-md); margin-top: 15px; border: 1px solid var(--border-color); }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        th { background-color: rgba(15, 23, 42, 0.85); color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: rgba(255, 255, 255, 0.02); }

        .poster-thumb { width: 48px; height: 68px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); display: block; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-movie { background: rgba(56, 189, 248, 0.15); color: var(--primary); border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-series { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

        .progress-container { width: 100%; background-color: rgba(15, 23, 42, 0.8); border-radius: 12px; margin-top: 15px; overflow: hidden; display: none; border: 1px solid var(--border-color); }
        .progress-bar { height: 26px; background: linear-gradient(90deg, #0284c7, #38bdf8); width: 0%; text-align: center; color: #0f172a; font-weight: 800; font-size: 0.82rem; line-height: 26px; transition: width 0.1s linear; }

        /* ALERTS */
        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* MOBILE RESPONSIVE MEDIA QUERIES */
        @media (max-width: 991px) {
            .sidebar { top: 0; z-index: 1000; transform: translateX(-260px); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-backdrop.show { display: block; }
            .main-content { margin-left: 0 !important; width: 100% !important; }
            .container { padding: 0 14px; margin: 16px auto; }
            .card { padding: 16px; margin-bottom: 16px; }
            .grid-3 { grid-template-columns: 1fr !important; }
            .btn { width: 100%; }
            .admin-profile-badge .profile-info { display: none; }
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['admin_logged_in'])): ?>
    <!-- Top Navigation Bar -->
    <nav class="top-navbar">
        <div class="top-nav-left">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Sidebar Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="index.php" class="brand">
                <?php if (!empty($adminSiteLogo)): ?>
                    <img src="../<?= htmlspecialchars($adminSiteLogo) ?>" alt="<?= htmlspecialchars($adminSiteTitle) ?>" style="max-height: 38px; object-fit: contain; border-radius: 6px;">
                <?php else: ?>
                    <div class="brand-logo"><i class="fa-solid fa-crown"></i></div>
                    <div class="brand-title"><?= htmlspecialchars($adminSiteTitle) ?></div>
                <?php endif; ?>
            </a>
        </div>

        <div class="top-nav-right">
            <div class="admin-profile-badge">
                <div class="profile-avatar"><i class="fa-solid fa-user-shield"></i></div>
                <div class="profile-info">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></span>
                    <span class="profile-role">Administrator</span>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="admin-layout">
<?php if (isset($_SESSION['admin_logged_in'])): ?>
    <!-- Left Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
            </a>
            <a href="movies.php" class="<?= basename($_SERVER['PHP_SELF']) == 'movies.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-film"></i> <span>Movies</span>
            </a>
            <a href="series.php" class="<?= basename($_SERVER['PHP_SELF']) == 'series.php' || basename($_SERVER['PHP_SELF']) == 'episodes.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-tv"></i> <span>Web Series</span>
            </a>
            <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> <span>Users Approval</span>
            </a>
            <?php
                $pendingSuggestionsCount = 0;
                if (isset($pdo)) {
                    try {
                        $pendingSuggestionsCount = (int)$pdo->query("SELECT COUNT(*) FROM user_suggestions WHERE status = 'pending'")->fetchColumn();
                    } catch (Exception $e) {}
                }
            ?>
            <a href="suggestions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'suggestions.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-lightbulb"></i> <span>User Suggestions</span>
                <?php if ($pendingSuggestionsCount > 0): ?>
                    <span class="badge" style="background: #f59e0b; color: #000; font-weight: 800; font-size: 0.72rem; margin-left: auto; border-radius: 10px; padding: 2px 7px;"><?= $pendingSuggestionsCount ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-sliders"></i> <span>Site Settings</span>
            </a>
            <a href="smtp_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'smtp_settings.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope-circle-check"></i> <span>SMTP Settings</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="../index.php" target="_blank">
                <i class="fa-solid fa-globe"></i> <span>View Site</span>
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth <= 991) {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContent) {
                    mainContent.classList.toggle('expanded');
                }
            }
        }
    </script>
<?php endif; ?>

<main class="main-content" id="mainContent" <?= !isset($_SESSION['admin_logged_in']) ? 'style="margin-left: 0; width: 100%;"' : '' ?>>
    <div class="container">
