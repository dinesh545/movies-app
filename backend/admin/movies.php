<?php
// backend/admin/movies.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

$msg = '';
$error = '';

// Handle Delete Movie
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM media_items WHERE id = :id AND type = 'movie'");
    $stmt->execute([':id' => $deleteId]);
    header('Location: movies.php?msg=Movie+deleted+successfully');
    exit();
}

// Fetch Movies List
$stmt = $pdo->query("SELECT * FROM media_items WHERE type = 'movie' ORDER BY id DESC");
$movies = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($_GET['msg']) ?></div>
    </div>
<?php endif; ?>

<div class="card" id="add">
    <h2><i class="fa-solid fa-film"></i> Add New Movie</h2>
    <form id="movieForm" style="margin-top: 16px;">
        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="form-group" style="grid-column: span 2;">
                <label><i class="fa-solid fa-heading" style="color: var(--primary);"></i> Movie Title *</label>
                <input type="text" id="title" class="form-control" placeholder="e.g. Jawan, Stree 2" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i> Release Year</label>
                <input type="number" id="release_year" class="form-control" value="2024">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-star" style="color: #fbbf24;"></i> IMDb Rating</label>
                <input type="text" id="rating" class="form-control" value="8.5">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-align-left" style="color: var(--primary);"></i> Storyline / Description</label>
            <textarea id="description" class="form-control" placeholder="Short storyline or description..."></textarea>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-image" style="color: var(--primary);"></i> Cover Poster Image (JPG / PNG / WEBP)</label>
            <input type="file" id="posterFile" class="form-control" accept="image/*" required>
        </div>

        <!-- Video Source Option -->
        <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px;">
            <label style="color: var(--primary); font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-video"></i> Select Movie Video Source:
            </label>
            
            <div style="display: flex; gap: 20px; margin-bottom: 14px; flex-wrap: wrap;">
                <label style="cursor: pointer; color: #fff; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                    <input type="radio" name="video_source" value="upload" checked onclick="toggleVideoSource('upload')"> 📤 Upload Video File from PC
                </label>
                <label style="cursor: pointer; color: var(--primary); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                    <input type="radio" name="video_source" value="nextcloud" onclick="toggleVideoSource('nextcloud')"> ☁️ Pick File in Nextcloud Storage
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
                    <button type="button" class="btn btn-secondary" onclick="loadNextcloudFiles()"><i class="fa-solid fa-rotate"></i> Refresh</button>
                </div>
                <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 6px; display: block;">
                    💡 Upload movies using WinSCP / Web UI directly to <code>Movies/</code> folder for max speed, then select here.
                </span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar" id="progressBar">0%</div>
        </div>

        <div id="status-msg" style="margin-top: 10px; font-weight: 700; font-size: 0.9rem;"></div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" id="submitBtn"><i class="fa-solid fa-cloud-arrow-up"></i> Save Movie</button>
        </div>
    </form>
</div>

<!-- Movies Table -->
<div class="card">
    <div class="card-header-flex">
        <h2><i class="fa-solid fa-film"></i> All Movies (<?= count($movies) ?>)</h2>
        <a href="#add" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Add Movie</a>
    </div>

    <?php if (empty($movies)): ?>
        <p style="color: var(--text-muted);">No movies added yet. Use the form above to upload your first movie!</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Title & Storyline</th>
                        <th>Year</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td>
                                <?php if (!empty($movie['poster_url'])): ?>
                                    <img src="<?= htmlspecialchars($movie['poster_url']) ?>" class="poster-thumb" alt="Poster">
                                <?php else: ?>
                                    <div style="width:48px; height:68px; background:rgba(255,255,255,0.05); border-radius:8px; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-film"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#fff; font-size:0.95rem;"><?= htmlspecialchars($movie['title']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars(substr($movie['description'] ?? '', 0, 65)) ?><?= strlen($movie['description'] ?? '') > 65 ? '...' : '' ?></span>
                            </td>
                            <td><span class="badge badge-movie"><?= htmlspecialchars($movie['release_year'] ?? '2024') ?></span></td>
                            <td><span style="color:#fbbf24; font-weight:700;"><i class="fa-solid fa-star"></i> <?= htmlspecialchars($movie['rating'] ?? '8.5') ?></span></td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="<?= htmlspecialchars($movie['stream_url']) ?>" target="_blank" class="btn btn-sm"><i class="fa-solid fa-play"></i> Stream</a>
                                    <a href="movies.php?action=delete&id=<?= $movie['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this movie permanently?')"><i class="fa-solid fa-trash-can"></i> Delete</a>
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

    document.getElementById('movieForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const title = document.getElementById('title').value;
        const releaseYear = document.getElementById('release_year').value;
        const rating = document.getElementById('rating').value;
        const description = document.getElementById('description').value;
        const posterFile = document.getElementById('posterFile').files[0];

        const submitBtn = document.getElementById('submitBtn');
        const statusMsg = document.getElementById('status-msg');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');

        if (!posterFile) {
            alert('Please select a cover poster image!');
            return;
        }

        submitBtn.disabled = true;
        statusMsg.style.color = '#38bdf8';
        
        try {
            // STEP 1: Upload Poster Image
            statusMsg.innerText = '⏳ Step 1: Uploading Cover Poster Image to Nextcloud...';
            const posterFormData = new FormData();
            posterFormData.append('poster', posterFile);

            const posterRes = await fetch('../api/upload_poster.php', { method: 'POST', body: posterFormData });
            const posterData = await posterRes.json();

            if (!posterRes.ok || posterData.status !== 'success') {
                throw new Error(posterData.message || 'Failed to upload poster image.');
            }

            const posterUrl = posterData.poster_url;
            let streamUrl = '';

            // STEP 2: Get Stream URL (Either from Nextcloud Picker or Chunked Upload)
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
                    throw new Error('Please select a video file to upload!');
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
                            statusMsg.innerText = `⏳ Finalizing: Merging movie file on Nextcloud Cloud Storage... (${speedMBps} MB/s)`;
                        } else {
                            statusMsg.innerText = `⏳ Uploading Chunk ${chunkIndex + 1} of ${totalChunks} (${speedMBps} MB/s)...`;
                        }
                    });

                    if (chunkData.status !== 'chunk_received' && chunkData.status !== 'success') {
                        throw new Error(chunkData.message || `Video Chunk ${chunkIndex + 1} failed.`);
                    }

                    bytesUploadedBeforeChunk += chunkBlob.size;

                    if (chunkData.status === 'success') {
                        progressBar.style.width = '100%';
                        progressBar.innerText = '100%';
                        streamUrl = chunkData.stream_url;
                    }
                }
            }

            // STEP 3: Save Movie to SQLite Database
            statusMsg.innerText = '⏳ Saving movie details to Database...';
            const dbFormData = new FormData();
            dbFormData.append('action', 'add_movie');
            dbFormData.append('title', title);
            dbFormData.append('release_year', releaseYear);
            dbFormData.append('rating', rating);
            dbFormData.append('description', description);
            dbFormData.append('poster_url', posterUrl);
            dbFormData.append('stream_url', streamUrl);

            const saveRes = await fetch('save_media.php', { method: 'POST', body: dbFormData });
            const saveData = await saveRes.json();

            if (saveData.status === 'success') {
                statusMsg.style.color = '#22c55e';
                statusMsg.innerText = '✅ Movie saved successfully!';
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                throw new Error(saveData.message || 'Failed to save movie in database.');
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
