<?php
// backend/admin/suggestions.php - Admin User Suggestions & Requests Manager (Redesigned UI)
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

$msg = '';
$error = '';

// Handle Status Change & Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            if ($action === 'update_status') {
                $newStatus = trim($_POST['status'] ?? 'pending');
                $allowedStatuses = ['pending', 'in_progress', 'resolved'];
                if (in_array($newStatus, $allowedStatuses)) {
                    $stmt = $pdo->prepare("UPDATE user_suggestions SET status = :status WHERE id = :id");
                    $stmt->execute([':status' => $newStatus, ':id' => $id]);
                    $msg = "Suggestion #{$id} status updated to '" . ucfirst(str_replace('_', ' ', $newStatus)) . "'.";
                }
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM user_suggestions WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Suggestion #{$id} deleted successfully.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch Filter Parameters
$statusFilter = trim($_GET['status'] ?? 'all');
$categoryFilter = trim($_GET['category'] ?? 'all');

// Build SQL Query
$sql = "SELECT * FROM user_suggestions WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($categoryFilter !== 'all') {
    $sql .= " AND category = :cat";
    $params[':cat'] = $categoryFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for Stats
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM user_suggestions")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM user_suggestions WHERE status = 'pending'")->fetchColumn();
$inProgressCount = (int)$pdo->query("SELECT COUNT(*) FROM user_suggestions WHERE status = 'in_progress'")->fetchColumn();
$resolvedCount = (int)$pdo->query("SELECT COUNT(*) FROM user_suggestions WHERE status = 'resolved'")->fetchColumn();
?>

<style>
    .sug-header-title { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .sug-title-text h1 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
    .sug-title-text p { font-size: 0.88rem; color: var(--text-muted); }

    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .sug-stat-card { background: #131b2e; border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; transition: transform 0.2s ease, border-color 0.2s ease; }
    .sug-stat-card:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, 0.4); }
    .sug-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
    .sug-stat-num { font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1; }
    .sug-stat-label { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-top: 4px; }

    .filter-card { background: #131b2e; border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 14px; }
    .filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .filter-label { font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; min-width: 80px; }
    .pill-btn { padding: 7px 16px; border-radius: 20px; font-size: 0.83rem; font-weight: 700; text-decoration: none; color: #94a3b8; background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; }
    .pill-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .pill-btn.active { background: linear-gradient(135deg, #0284c7, #38bdf8); color: #0f172a; border-color: transparent; box-shadow: 0 0 15px rgba(56, 189, 248, 0.35); }

    .sug-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 992px) {
        .sug-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .sug-card { background: #131b2e; border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; gap: 16px; position: relative; transition: all 0.2s ease; }
    .sug-card:hover { border-color: rgba(56, 189, 248, 0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.4); }

    .sug-card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .sug-user-info { display: flex; align-items: center; gap: 12px; }
    .sug-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #1e293b, #334155); border: 1.5px solid rgba(56, 189, 248, 0.4); color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .sug-user-name { font-size: 0.95rem; font-weight: 700; color: #fff; line-height: 1.2; }
    .sug-user-contact { font-size: 0.78rem; color: var(--text-muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

    .sug-tag { padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.3px; }
    .tag-movie { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .tag-feature { background: rgba(129, 140, 248, 0.15); color: #818cf8; border: 1px solid rgba(129, 140, 248, 0.3); }
    .tag-bug { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .tag-general { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }

    .sug-content-box { background: rgba(9, 13, 22, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 14px; font-size: 0.92rem; color: #f1f5f9; line-height: 1.6; word-break: break-word; }

    .sug-card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding-top: 12px; border-top: 1px solid var(--border-color); }
    .sug-date { font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

    .sug-actions { display: flex; align-items: center; gap: 10px; }
    .status-select { padding: 6px 12px; border-radius: 8px; background: #090d16; color: #fff; border: 1px solid var(--border-color); font-size: 0.8rem; font-weight: 600; cursor: pointer; outline: none; transition: border-color 0.2s; }
    .status-select:focus { border-color: var(--primary); }

    .btn-del { padding: 6px 12px; border-radius: 8px; background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-del:hover { background: #ef4444; color: #fff; }
</style>

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

<!-- Title Header -->
<div class="sug-header-title">
    <div class="sug-title-text">
        <h1><i class="fa-solid fa-comments" style="color: var(--primary);"></i> User Suggestions & Movie Requests</h1>
        <p>Review movie requests, feature suggestions, and feedback submitted by app & web users.</p>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-row">
    <div class="sug-stat-card">
        <div class="sug-stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
            <i class="fa-solid fa-lightbulb"></i>
        </div>
        <div>
            <div class="sug-stat-num"><?= $totalCount ?></div>
            <div class="sug-stat-label">Total Suggestions</div>
        </div>
    </div>
    <div class="sug-stat-card">
        <div class="sug-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div>
            <div class="sug-stat-num"><?= $pendingCount ?></div>
            <div class="sug-stat-label">Pending Review</div>
        </div>
    </div>
    <div class="sug-stat-card">
        <div class="sug-stat-icon" style="background: rgba(129, 140, 248, 0.15); color: #818cf8;">
            <i class="fa-solid fa-spinner fa-spin"></i>
        </div>
        <div>
            <div class="sug-stat-num"><?= $inProgressCount ?></div>
            <div class="sug-stat-label">In Progress</div>
        </div>
    </div>
    <div class="sug-stat-card">
        <div class="sug-stat-icon" style="background: rgba(34, 197, 94, 0.15); color: #4ade80;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="sug-stat-num"><?= $resolvedCount ?></div>
            <div class="sug-stat-label">Resolved / Done</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-card">
    <div class="filter-group">
        <span class="filter-label">Status:</span>
        <a href="suggestions.php?status=all&category=<?= urlencode($categoryFilter) ?>" class="pill-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
        <a href="suggestions.php?status=pending&category=<?= urlencode($categoryFilter) ?>" class="pill-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>">⏳ Pending (<?= $pendingCount ?>)</a>
        <a href="suggestions.php?status=in_progress&category=<?= urlencode($categoryFilter) ?>" class="pill-btn <?= $statusFilter === 'in_progress' ? 'active' : '' ?>">🚀 In Progress (<?= $inProgressCount ?>)</a>
        <a href="suggestions.php?status=resolved&category=<?= urlencode($categoryFilter) ?>" class="pill-btn <?= $statusFilter === 'resolved' ? 'active' : '' ?>">✅ Resolved (<?= $resolvedCount ?>)</a>
    </div>
    <div class="filter-group">
        <span class="filter-label">Category:</span>
        <a href="suggestions.php?status=<?= urlencode($statusFilter) ?>&category=all" class="pill-btn <?= $categoryFilter === 'all' ? 'active' : '' ?>">All Categories</a>
        <a href="suggestions.php?status=<?= urlencode($statusFilter) ?>&category=Movie / Web Series Request" class="pill-btn <?= $categoryFilter === 'Movie / Web Series Request' ? 'active' : '' ?>">🎬 Movie / Series Requests</a>
        <a href="suggestions.php?status=<?= urlencode($statusFilter) ?>&category=App / Website Feature Improvement" class="pill-btn <?= $categoryFilter === 'App / Website Feature Improvement' ? 'active' : '' ?>">🚀 Feature Improvements</a>
        <a href="suggestions.php?status=<?= urlencode($statusFilter) ?>&category=Technical Bug / Video Issue" class="pill-btn <?= $categoryFilter === 'Technical Bug / Video Issue' ? 'active' : '' ?>">🐛 Bug Reports</a>
    </div>
</div>

<!-- Suggestions Grid -->
<?php if (empty($suggestions)): ?>
    <div style="background: #131b2e; border: 1px solid var(--border-color); border-radius: 16px; padding: 48px; text-align: center; color: var(--text-muted);">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.4;"></i>
        <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 4px;">No Suggestions Found</h3>
        <p style="font-size: 0.85rem;">No user suggestions or requests match your current status/category filter.</p>
    </div>
<?php else: ?>
    <div class="sug-grid">
        <?php foreach ($suggestions as $s): ?>
            <?php
                $tagClass = 'tag-general';
                $tagIcon = 'fa-lightbulb';
                if (strpos($s['category'], 'Movie') !== false) {
                    $tagClass = 'tag-movie';
                    $tagIcon = 'fa-film';
                } elseif (strpos($s['category'], 'Feature') !== false) {
                    $tagClass = 'tag-feature';
                    $tagIcon = 'fa-rocket';
                } elseif (strpos($s['category'], 'Bug') !== false) {
                    $tagClass = 'tag-bug';
                    $tagIcon = 'fa-bug';
                }
            ?>
            <div class="sug-card">
                <div>
                    <!-- Top User Info & Tag -->
                    <div class="sug-card-top" style="margin-bottom: 14px;">
                        <div class="sug-user-info">
                            <div class="sug-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <div class="sug-user-name"><?= htmlspecialchars($s['user_name']) ?></div>
                                <div class="sug-user-contact">
                                    <?php if ($s['user_email']): ?>
                                        <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($s['user_email']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($s['user_mobile']): ?>
                                        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($s['user_mobile']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span class="sug-tag <?= $tagClass ?>">
                            <i class="fa-solid <?= $tagIcon ?>"></i> <?= htmlspecialchars($s['category']) ?>
                        </span>
                    </div>

                    <!-- Message Body -->
                    <div class="sug-content-box">
                        <?= nl2br(htmlspecialchars($s['suggestion_text'])) ?>
                    </div>
                </div>

                <!-- Footer Date & Actions -->
                <div class="sug-card-bottom">
                    <div class="sug-date">
                        <i class="fa-solid fa-calendar-day"></i>
                        <?= date('d M Y, h:i A', strtotime($s['created_at'])) ?>
                    </div>

                    <div class="sug-actions">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="action" value="update_status">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="pending" <?= $s['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                <option value="in_progress" <?= $s['status'] === 'in_progress' ? 'selected' : '' ?>>🚀 In Progress</option>
                                <option value="resolved" <?= $s['status'] === 'resolved' ? 'selected' : '' ?>>✅ Resolved</option>
                            </select>
                        </form>

                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this suggestion?')">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-del" title="Delete Suggestion">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
</main>
</div>
</body>
</html>
