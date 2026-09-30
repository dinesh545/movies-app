<?php
// backend/admin/series.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

// Handle Delete Series
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM media_items WHERE id = :id AND type = 'series'");
    $stmt->execute([':id' => $deleteId]);
    header('Location: series.php?msg=Web+Series+deleted+successfully');
    exit();
}

// Fetch Series List with season/episode counts
$stmt = $pdo->query("
    SELECT m.*, 
        (SELECT COUNT(*) FROM seasons WHERE series_id = m.id) as season_count,
        (SELECT COUNT(*) FROM episodes e JOIN seasons s ON e.season_id = s.id WHERE s.series_id = m.id) as episode_count
    FROM media_items m 
    WHERE m.type = 'series' 
    ORDER BY m.id DESC
");
$allSeries = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($_GET['msg']) ?></div>
    </div>
<?php endif; ?>

<div class="card" id="add">
    <h2><i class="fa-solid fa-tv" style="color: #fbbf24;"></i> Add New Web Series</h2>
    <form id="seriesForm" style="margin-top: 16px;">
        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="form-group" style="grid-column: span 2;">
                <label><i class="fa-solid fa-heading" style="color: var(--primary);"></i> Series Title *</label>
                <input type="text" id="title" class="form-control" placeholder="e.g. Mirzapur, Stranger Things" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i> Release Year</label>
                <input type="number" id="release_year" class="form-control" value="2024">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-star" style="color: #fbbf24;"></i> Rating</label>
                <input type="text" id="rating" class="form-control" value="8.8">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-align-left" style="color: var(--primary);"></i> Series Description</label>
            <textarea id="description" class="form-control" placeholder="Short description of the web series..."></textarea>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-image" style="color: var(--primary);"></i> Series Cover Poster Image (JPG / PNG / WEBP)</label>
            <input type="file" id="posterFile" class="form-control" accept="image/*" required>
        </div>

        <div id="status-msg" style="margin-top: 10px; font-weight: 700; font-size: 0.9rem;"></div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-series" id="submitBtn"><i class="fa-solid fa-folder-plus"></i> Create Web Series</button>
        </div>
    </form>
</div>

<!-- Web Series Table -->
<div class="card">
    <div class="card-header-flex">
        <h2><i class="fa-solid fa-tv" style="color: #fbbf24;"></i> All Web Series (<?= count($allSeries) ?>)</h2>
        <a href="#add" class="btn btn-series btn-sm"><i class="fa-solid fa-plus"></i> Add Series</a>
    </div>

    <?php if (empty($allSeries)): ?>
        <p style="color: var(--text-muted);">No web series created yet. Fill the form above to add a series!</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Series Title</th>
                        <th>Year</th>
                        <th>Seasons</th>
                        <th>Episodes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allSeries as $series): ?>
                        <tr>
                            <td>
                                <?php if (!empty($series['poster_url'])): ?>
                                    <img src="<?= htmlspecialchars($series['poster_url']) ?>" class="poster-thumb" alt="Poster">
                                <?php else: ?>
                                    <div style="width:48px; height:68px; background:rgba(255,255,255,0.05); border-radius:8px; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-tv" style="color:#fbbf24;"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#fff; font-size:0.95rem;"><?= htmlspecialchars($series['title']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars(substr($series['description'] ?? '', 0, 65)) ?><?= strlen($series['description'] ?? '') > 65 ? '...' : '' ?></span>
                            </td>
                            <td><span class="badge badge-movie"><?= htmlspecialchars($series['release_year'] ?? '2024') ?></span></td>
                            <td><span class="badge badge-series"><i class="fa-solid fa-layer-group"></i> <?= $series['season_count'] ?> Seasons</span></td>
                            <td><span class="badge badge-movie"><i class="fa-solid fa-clapperboard"></i> <?= $series['episode_count'] ?> Episodes</span></td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="episodes.php?series_id=<?= $series['id'] ?>" class="btn btn-sm"><i class="fa-solid fa-sliders"></i> Episodes</a>
                                    <a href="series.php?action=delete&id=<?= $series['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this web series and all its episodes?')"><i class="fa-solid fa-trash-can"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('seriesForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const title = document.getElementById('title').value;
        const releaseYear = document.getElementById('release_year').value;
        const rating = document.getElementById('rating').value;
        const description = document.getElementById('description').value;
        const posterFile = document.getElementById('posterFile').files[0];

        const submitBtn = document.getElementById('submitBtn');
        const statusMsg = document.getElementById('status-msg');

        if (!posterFile) {
            alert('Please select a cover poster image!');
            return;
        }

        submitBtn.disabled = true;
        statusMsg.style.color = '#38bdf8';
        statusMsg.innerText = '⏳ Uploading Cover Poster Image to Nextcloud...';

        try {
            // Step 1: Upload Poster Image
            const posterFormData = new FormData();
            posterFormData.append('poster', posterFile);

            const posterRes = await fetch('../api/upload_poster.php', { method: 'POST', body: posterFormData });
            const posterData = await posterRes.json();

            if (!posterRes.ok || posterData.status !== 'success') {
                throw new Error(posterData.message || 'Failed to upload poster image.');
            }

            // Step 2: Save Series to SQLite DB
            statusMsg.innerText = '⏳ Saving Web Series to Database...';
            const dbFormData = new FormData();
            dbFormData.append('action', 'add_series');
            dbFormData.append('title', title);
            dbFormData.append('release_year', releaseYear);
            dbFormData.append('rating', rating);
            dbFormData.append('description', description);
            dbFormData.append('poster_url', posterData.poster_url);

            const saveRes = await fetch('save_media.php', { method: 'POST', body: dbFormData });
            const saveData = await saveRes.json();

            if (saveData.status === 'success') {
                statusMsg.style.color = '#22c55e';
                statusMsg.innerText = '✅ Web Series created successfully!';
                setTimeout(() => { window.location.href = 'episodes.php?series_id=' + saveData.id; }, 1200);
            } else {
                throw new Error(saveData.message || 'Failed to save series.');
            }

        } catch (err) {
            submitBtn.disabled = false;
            statusMsg.style.color = '#ef4444';
            statusMsg.innerText = '❌ Error: ' + err.message;
        }
    });
</script>

</div>
</main>
</div>
</body>
</html>
