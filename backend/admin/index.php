<?php
// backend/admin/index.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

// Fetch Counts
$movieCount = $pdo->query("SELECT COUNT(*) FROM media_items WHERE type = 'movie'")->fetchColumn();
$seriesCount = $pdo->query("SELECT COUNT(*) FROM media_items WHERE type = 'series'")->fetchColumn();
$episodeCount = $pdo->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
$pendingUsersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin_approved != 1")->fetchColumn();
?>

<!-- WELCOME BANNER -->
<div class="card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-color: rgba(56, 189, 248, 0.2); position: relative; overflow: hidden; margin-bottom: 24px;">
    <div style="position: absolute; right: -40px; top: -40px; width: 180px; height: 180px; background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%); pointer-events: none;"></div>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-gauge-high" style="color: var(--primary);"></i> Admin Dashboard Overview
    </h1>
    <p style="color: var(--text-muted); margin-top: 6px; font-size: 0.95rem;">
        Welcome back, <strong style="color: var(--primary);"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></strong>! Manage your media content, user approvals, and streaming library here.
    </p>
</div>

<!-- STATS CARDS GRID -->
<div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
        <i class="fa-solid fa-film stat-icon"></i>
        <div>
            <span style="color: var(--text-muted); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Movies</span>
            <div class="stat-number"><?= number_format($movieCount) ?></div>
        </div>
        <div style="margin-top: 14px;">
            <a href="movies.php" class="btn btn-sm" style="width: 100%;"><i class="fa-solid fa-list-check"></i> Manage Movies</a>
        </div>
    </div>

    <div class="stat-card">
        <i class="fa-solid fa-tv stat-icon" style="color: #fbbf24;"></i>
        <div>
            <span style="color: var(--text-muted); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Web Series</span>
            <div class="stat-number" style="color: #fbbf24;"><?= number_format($seriesCount) ?></div>
        </div>
        <div style="margin-top: 14px;">
            <a href="series.php" class="btn btn-series btn-sm" style="width: 100%;"><i class="fa-solid fa-layer-group"></i> Manage Series</a>
        </div>
    </div>

    <div class="stat-card">
        <i class="fa-solid fa-users stat-icon" style="color: #f87171;"></i>
        <div>
            <span style="color: var(--text-muted); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Pending Approval</span>
            <div class="stat-number" style="color: #f87171;"><?= number_format($pendingUsersCount) ?></div>
        </div>
        <div style="margin-top: 14px;">
            <a href="users.php" class="btn btn-sm" style="width: 100%; background: linear-gradient(135deg, #dc2626, #b91c1c);"><i class="fa-solid fa-user-check"></i> Review Approvals</a>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="card" style="margin-top: 24px;">
    <h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2>
    <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-top: 14px;">
        <a href="movies.php#add" class="btn"><i class="fa-solid fa-plus"></i> Add New Movie</a>
        <a href="series.php#add" class="btn btn-series"><i class="fa-solid fa-folder-plus"></i> Add New Web Series</a>
        <a href="../index.php" target="_blank" class="btn btn-secondary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Main Site</a>
    </div>
</div>

<!-- REAL-TIME LIVE STREAMING MONITOR -->
<div class="card" style="margin-top: 24px; border-color: rgba(34, 197, 94, 0.3);">
    <div class="card-header-flex">
        <h2><i class="fa-solid fa-tower-broadcast" style="color: #4ade80; animation: pulse 2s infinite;"></i> Real-Time Live Watching Monitor</h2>
        <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);" id="liveCountBadge">
            <i class="fa-solid fa-circle" style="font-size: 0.6rem; color: #4ade80;"></i> <span id="activeCount">0</span> Active Viewers
        </span>
    </div>

    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 16px;">
        Live tracking of users currently streaming movies & web series across Website and Android App.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Email / Contact</th>
                    <th>Currently Watching</th>
                    <th>Platform / Device</th>
                    <th>Live Status</th>
                </tr>
            </thead>
            <tbody id="activeStreamsBody">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 8px; display: block; color: var(--primary);"></i>
                        Loading live stream monitor...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    async function fetchLiveStreams() {
        try {
            const res = await fetch('../api/admin_live_streams.php');
            const data = await res.json();
            const tbody = document.getElementById('activeStreamsBody');
            const countEl = document.getElementById('activeCount');

            if (data.status === 'success') {
                countEl.innerText = data.count;

                if (data.count === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                <i class="fa-solid fa-film" style="font-size: 1.8rem; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                                No active users streaming right now.
                            </td>
                        </tr>
                    `;
                    return;
                }

                let html = '';
                data.data.forEach(s => {
                    html += `
                        <tr>
                            <td><strong>#${s.user_id}</strong></td>
                            <td><strong style="color: #fff;"><i class="fa-solid fa-circle-user" style="color: var(--primary); margin-right: 6px;"></i>${escapeHtml(s.user_name)}</strong></td>
                            <td><span style="color: var(--text-muted); font-size: 0.85rem;">${escapeHtml(s.user_email)}</span></td>
                            <td><strong style="color: #38bdf8;"><i class="fa-solid fa-play" style="font-size: 0.8rem; margin-right: 6px;"></i>${escapeHtml(s.media_title)}</strong></td>
                            <td><span class="badge" style="background: rgba(255,255,255,0.06); color: #fff; border: 1px solid var(--border-color);">${escapeHtml(s.device_type)}</span></td>
                            <td>
                                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">
                                    <i class="fa-solid fa-circle" style="font-size: 0.5rem; color: #4ade80;"></i> LIVE (${s.seconds_ago || 0}s ago)
                                </span>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                            <i class="fa-solid fa-film" style="font-size: 1.8rem; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            No active users streaming right now.
                        </td>
                    </tr>
                `;
            }
        } catch (e) {
            const tbody = document.getElementById('activeStreamsBody');
            if (tbody && tbody.innerHTML.includes('Loading')) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                            <i class="fa-solid fa-film" style="font-size: 1.8rem; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            No active users streaming right now.
                        </td>
                    </tr>
                `;
            }
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    fetchLiveStreams();
    setInterval(fetchLiveStreams, 3000);
</script>

</div>
</main>
</div>
</body>
</html>

