<?php
// backend/admin/episodes.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

$seriesId = isset($_GET['series_id']) ? (int)$_GET['series_id'] : 0;

if ($seriesId <= 0) {
    header('Location: series.php');
    exit();
}

// Fetch Series
$stmt = $pdo->prepare("SELECT * FROM media_items WHERE id = :id AND type = 'series'");
$stmt->execute([':id' => $seriesId]);
$series = $stmt->fetch();

if (!$series) {
    header('Location: series.php');
    exit();
}

// Handle Delete Season or Episode
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete_season' && isset($_GET['season_id'])) {
        $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = :id AND series_id = :series_id");
        $stmt->execute([':id' => (int)$_GET['season_id'], ':series_id' => $seriesId]);
        header("Location: episodes.php?series_id={$seriesId}&msg=Season+deleted+successfully");
        exit();
    }
    if ($_GET['action'] === 'delete_episode' && isset($_GET['ep_id'])) {
        $stmt = $pdo->prepare("DELETE FROM episodes WHERE id = :id");
        $stmt->execute([':id' => (int)$_GET['ep_id']]);
        header("Location: episodes.php?series_id={$seriesId}&msg=Episode+deleted+successfully");
        exit();
    }
}

// Fetch Seasons for this Series
$stmt = $pdo->prepare("SELECT * FROM seasons WHERE series_id = :series_id ORDER BY season_number ASC");
$stmt->execute([':series_id' => $seriesId]);
$seasons = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= htmlspecialchars($_GET['msg']) ?></span>
    </div>
<?php endif; ?>

<!-- Subheader navigation -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="series.php" style="color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Web Series List
        </a>
        <h1 style="margin: 0; font-size: 1.6rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-tv" style="color: var(--primary);"></i>
            <span>Manage: <?= htmlspecialchars($series['title']) ?></span>
        </h1>
    </div>
    <button onclick="document.getElementById('addSeasonModal').style.display='block'" class="btn" style="background: linear-gradient(135deg, #d97706, #b45309);">
        <i class="fa-solid fa-plus"></i> Add New Season
    </button>
</div>

<!-- Add Season Inline Form / Modal -->
<div id="addSeasonModal" class="card" style="display: none; border-color: rgba(245, 158, 11, 0.4); margin-bottom: 24px;">
    <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 16px; color: #f59e0b; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-folder-plus"></i> Add New Season to <?= htmlspecialchars($series['title']) ?>
    </h2>
    <form id="seasonForm">
        <input type="hidden" id="season_series_id" value="<?= $seriesId ?>">
        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="form-group">
                <label>Season Number</label>
                <input type="number" id="season_number" class="form-control" value="<?= count($seasons) + 1 ?>" required>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Season Title</label>
                <input type="text" id="season_title" class="form-control" value="Season <?= count($seasons) + 1 ?>" required>
            </div>
        </div>
        <div style="text-align: right; margin-top: 16px; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('addSeasonModal').style.display='none'">Cancel</button>
            <button type="submit" class="btn" style="background: linear-gradient(135deg, #d97706, #b45309);">Save Season</button>
        </div>
    </form>
</div>

<!-- Add Episode Form -->
<div class="card" style="margin-bottom: 24px;">
    <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-plus" style="color: var(--primary);"></i> Add Episode to a Season
    </h2>

    <?php if (empty($seasons)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>Please create at least one Season above before adding episodes!</span>
        </div>
    <?php else: ?>
        <form id="episodeForm">
            <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div class="form-group">
                    <label>Select Season *</label>
                    <select id="season_id" class="form-control" required>
                        <?php foreach ($seasons as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Episode Number *</label>
                    <input type="number" id="episode_number" class="form-control" value="1" required>
                </div>

                <div class="form-group">
                    <label>Episode Title *</label>
                    <input type="text" id="episode_title" class="form-control" placeholder="e.g. Episode 1: The Beginning" required>
                </div>
            </div>

            <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Episode Description</label>
                    <input type="text" id="episode_description" class="form-control" placeholder="Brief episode description...">
                </div>

                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" id="duration" class="form-control" value="45m">
                </div>
            </div>

            <!-- Video Source Selector -->
            <div style="background: rgba(15, 23, 42, 0.6); padding: 18px; border-radius: 12px; border: 1px solid var(--card-border); margin-bottom: 16px;">
                <label style="color: var(--primary); font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clapperboard"></i> Select Episode Video Source:
                </label>
                
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 14px;">
                    <label style="cursor: pointer; color: var(--text); display: flex; align-items: center; gap: 6px; font-weight: 500;">
                        <input type="radio" name="video_source" value="upload" checked onclick="toggleVideoSource('upload')">
                        <i class="fa-solid fa-cloud-arrow-up" style="color: #60a5fa;"></i> Upload Video from PC
                    </label>
                    <label style="cursor: pointer; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="video_source" value="nextcloud" onclick="toggleVideoSource('nextcloud')">
                        <i class="fa-solid fa-cloud" style="color: var(--primary);"></i> Pick File Already in Nextcloud (WinSCP / Direct)
                    </label>
                </div>

                <!-- Option A: File Upload -->
                <div id="uploadSourceDiv" class="form-group" style="margin-bottom: 0;">
                    <input type="file" id="videoFile" class="form-control" accept="video/*">
                </div>

                <!-- Option B: Nextcloud File Picker Dropdown -->
                <div id="nextcloudSourceDiv" class="form-group" style="display: none; margin-bottom: 0;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <select id="ncFileSelect" class="form-control" style="flex: 1; min-width: 200px;">
                            <option value="">-- Loading Nextcloud files... --</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadNextcloudFiles()" style="white-space: nowrap;">
                            <i class="fa-solid fa-rotate"></i> Refresh
                        </button>
                    </div>
                    <span style="font-size: 0.825rem; color: var(--text-muted); margin-top: 8px; display: block;">
                        <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> Upload episode videos via WinSCP directly to <code>Movies/</code> folder, then pick it here!
                    </span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar" id="progressBar">0%</div>
            </div>

            <div id="status-msg" style="margin-top: 10px; font-weight: 600; font-size: 0.9rem;"></div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fa-solid fa-paper-plane"></i> Save Episode
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Seasons & Episodes List -->
<?php foreach ($seasons as $season): ?>
    <?php
    $epStmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = :season_id ORDER BY episode_number ASC");
    $epStmt->execute([':season_id' => $season['id']]);
    $episodes = $epStmt->fetchAll();
    ?>
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <h2 style="font-size: 1.2rem; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-folder-open" style="color: #f59e0b;"></i>
                <span><?= htmlspecialchars($season['title']) ?></span>
                <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fef3c7; margin-left: 6px;">
                    <?= count($episodes) ?> Episodes
                </span>
            </h2>
            <a href="episodes.php?series_id=<?= $seriesId ?>&action=delete_season&season_id=<?= $season['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this season and all its episodes?')">
                <i class="fa-solid fa-trash"></i> Delete Season
            </a>
        </div>

        <?php if (empty($episodes)): ?>
            <p style="color: var(--text-muted); margin-top: 10px; font-size: 0.9rem;">No episodes added to <?= htmlspecialchars($season['title']) ?> yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ep #</th>
                            <th>Episode Title</th>
                            <th style="width: 110px;">Duration</th>
                            <th style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($episodes as $ep): ?>
                            <tr>
                                <td><strong style="color: var(--primary);">Ep <?= $ep['episode_number'] ?></strong></td>
                                <td>
                                    <strong style="color: var(--text);"><?= htmlspecialchars($ep['title']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($ep['description'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem;">
                                        <i class="fa-solid fa-clock" style="color: var(--text-muted);"></i> <?= htmlspecialchars($ep['duration']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= htmlspecialchars($ep['stream_url']) ?>" target="_blank" class="btn btn-sm" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; text-decoration: none;">
                                            <i class="fa-solid fa-play"></i> Stream
                                        </a>
                                        <a href="episodes.php?series_id=<?= $seriesId ?>&action=delete_episode&ep_id=<?= $ep['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this episode?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<script>
    let currentVideoSource = 'upload';

    function toggleVideoSource(source) {
        currentVideoSource = source;
        if (source === 'upload') {
            document.getElementById('uploadSourceDiv').style.display = 'block';
            document.getElementById('nextcloudSourceDiv').style.display = 'none';
        } else {
            document.getElementById('uploadSourceDiv').style.display = 'none';
            document.getElementById('nextcloudSourceDiv').style.display = 'block';
            loadNextcloudFiles();
        }
    }

    async function loadNextcloudFiles() {
        const selectEl = document.getElementById('ncFileSelect');
        selectEl.innerHTML = '<option value="">-- Loading files from Nextcloud... --</option>';

        try {
            const res = await fetch('../api/list_nextcloud_files.php');
            const data = await res.json();

            if (data.status === 'success' && data.data.length > 0) {
                selectEl.innerHTML = data.data.map(f => `
                    <option value="${f.stream_url}">${f.name} (${f.formatted_size})</option>
                `).join('');
            } else {
                selectEl.innerHTML = '<option value="">No video files found in Nextcloud Movies/ folder</option>';
            }
        } catch (err) {
            selectEl.innerHTML = '<option value="">Failed to connect to Nextcloud</option>';
        }
    }

    // Handle Season Add
    document.getElementById('seasonForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const seriesId = document.getElementById('season_series_id').value;
        const seasonNumber = document.getElementById('season_number').value;
        const seasonTitle = document.getElementById('season_title').value;

        const formData = new FormData();
        formData.append('action', 'add_season');
        formData.append('series_id', seriesId);
        formData.append('season_number', seasonNumber);
        formData.append('title', seasonTitle);

        const res = await fetch('save_media.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message || 'Error creating season');
        }
    });

    async function uploadChunkWithProgress(url, formData, onProgress, maxRetries = 5) {
        let attempt = 0;
        while (attempt < maxRetries) {
            try {
                return await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', url, true);
                    xhr.timeout = 60000; // 60 sec timeout per chunk

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            onProgress(e.loaded, e.total);
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                resolve(data);
                            } catch (err) {
                                reject(new Error('Invalid server JSON response'));
                            }
                        } else {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                reject(new Error(data.message || `HTTP ${xhr.status} Error`));
                            } catch (err) {
                                reject(new Error(`Server returned HTTP ${xhr.status}`));
                            }
                        }
                    };

                    xhr.onerror = () => reject(new Error('Network connection error during chunk upload.'));
                    xhr.ontimeout = () => reject(new Error('Upload chunk timed out.'));

                    xhr.send(formData);
                });
            } catch (err) {
                attempt++;
                if (attempt >= maxRetries) {
                    throw err;
                }
                console.warn(`Chunk upload retry attempt ${attempt}/${maxRetries}...`, err);
                await new Promise(r => setTimeout(r, 2000));
            }
        }
    }

    // Handle Episode Add
    document.getElementById('episodeForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const seasonId = document.getElementById('season_id').value;
        const episodeNumber = document.getElementById('episode_number').value;
        const title = document.getElementById('episode_title').value;
        const description = document.getElementById('episode_description').value;
        const duration = document.getElementById('duration').value;

        const submitBtn = document.getElementById('submitBtn');
        const statusMsg = document.getElementById('status-msg');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');

        submitBtn.disabled = true;
        statusMsg.style.color = '#38bdf8';

        try {
            let streamUrl = '';

            if (currentVideoSource === 'nextcloud') {
                const selectEl = document.getElementById('ncFileSelect');
                streamUrl = selectEl.value;
                if (!streamUrl) {
                    throw new Error('Please select a video file from Nextcloud dropdown list!');
                }
                statusMsg.innerText = '⚡ Nextcloud file linked instantly!';
            } else {
                const videoFile = document.getElementById('videoFile').files[0];
                if (!videoFile) {
                    throw new Error('Please select an episode video file!');
                }

                progressContainer.style.display = 'block';
                const CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB chunks for fast, smooth progress
                const totalChunks = Math.ceil(videoFile.size / CHUNK_SIZE);
                const fileId = Date.now() + '_' + Math.random().toString(36).substring(2, 8);
                const startTime = Date.now();
                let bytesUploadedBeforeChunk = 0;

                for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                    const start = chunkIndex * CHUNK_SIZE;
                    const end = Math.min(videoFile.size, start + CHUNK_SIZE);
                    const chunkBlob = videoFile.slice(start, end);

                    const chunkFormData = new FormData();
                    chunkFormData.append('file', chunkBlob, videoFile.name);
                    chunkFormData.append('chunk_index', chunkIndex);
                    chunkFormData.append('total_chunks', totalChunks);
                    chunkFormData.append('file_name', videoFile.name);
                    chunkFormData.append('file_id', fileId);

                    const chunkData = await uploadChunkWithProgress('../api/upload_chunk.php', chunkFormData, (chunkLoaded, chunkTotal) => {
                        const totalUploadedNow = bytesUploadedBeforeChunk + chunkLoaded;
                        const percent = Math.min(99, Math.round((totalUploadedNow / videoFile.size) * 100));
                        const elapsedSec = (Date.now() - startTime) / 1000;
                        const speedMBps = elapsedSec > 0 ? ((totalUploadedNow / (1024 * 1024)) / elapsedSec).toFixed(2) : '0.00';

                        progressBar.style.width = percent + '%';
                        progressBar.innerText = `${percent}% (${speedMBps} MB/s)`;

                        if (chunkIndex === totalChunks - 1 && percent >= 95) {
                            statusMsg.innerText = `⏳ Finalizing: Merging episode file on Nextcloud Cloud Storage... (${speedMBps} MB/s)`;
                        } else {
                            statusMsg.innerText = `⏳ Uploading Episode Chunk ${chunkIndex + 1} of ${totalChunks} (${speedMBps} MB/s)...`;
                        }
                    });

                    if (chunkData.status !== 'chunk_received' && chunkData.status !== 'success') {
                        throw new Error(chunkData.message || `Episode Chunk ${chunkIndex + 1} failed.`);
                    }

                    bytesUploadedBeforeChunk += chunkBlob.size;

                    if (chunkData.status === 'success') {
                        progressBar.style.width = '100%';
                        progressBar.innerText = '100%';
                        streamUrl = chunkData.stream_url;
                    }
                }
            }

            // STEP 2: Save Episode to SQLite Database
            statusMsg.innerText = '⏳ Saving episode details to Database...';
            const dbFormData = new FormData();
            dbFormData.append('action', 'add_episode');
            dbFormData.append('season_id', seasonId);
            dbFormData.append('episode_number', episodeNumber);
            dbFormData.append('title', title);
            dbFormData.append('description', description);
            dbFormData.append('duration', duration);
            dbFormData.append('stream_url', streamUrl);

            const saveRes = await fetch('save_media.php', { method: 'POST', body: dbFormData });
            const saveData = await saveRes.json();

            if (saveData.status === 'success') {
                statusMsg.style.color = '#22c55e';
                statusMsg.innerText = '✅ Episode saved successfully!';
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                throw new Error(saveData.message || 'Failed to save episode in database.');
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

