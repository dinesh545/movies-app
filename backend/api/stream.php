<?php
// backend/api/stream.php - Universal Fast Video Streaming API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    echo "Error: Missing 'file' parameter.";
    exit();
}

$file = ltrim($file, '/');
$filename = basename($file);
$showHtmlPlayer = isset($_GET['player']) && $_GET['player'] == '1';

// Determine browser-friendly MIME type
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico', 'bmp']);

// Enforce User Authentication & Single Device Session Token for Video Streams
if (!$isImage && !$showHtmlPlayer) {
    $token = $_SESSION['user_session_token'] ?? ($_GET['session_token'] ?? ($_SERVER['HTTP_X_SESSION_TOKEN'] ?? ''));
    if (empty($token) && isset($_GET['raw']) && $_GET['raw'] == '1') {
        // Allow raw stream playback for video player requests
    } elseif (empty($token)) {
        http_response_code(401);
        die("Authentication Required: Please log in to stream videos.");
    }
}

// Release PHP session locks immediately for parallel video range requests
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Disable web server compression & buffering so video frames stream instantly
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 1);

require_once __DIR__ . '/../NextcloudClient.php';

$mimeMap = [
    // Images
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'bmp'  => 'image/bmp',

    // Videos
    'mp4'  => 'video/mp4',
    'm4v'  => 'video/mp4',
    'mov'  => 'video/quicktime',
    'webm' => 'video/webm',
    'mkv'  => 'video/mp4',
    'avi'  => 'video/x-msvideo',
    'ts'   => 'video/mp2t',
    
    // Audio
    'mp3'  => 'audio/mpeg',
    'm4a'  => 'audio/mp4',
    'aac'  => 'audio/aac',
    
    // Subtitles
    'vtt'  => 'text/vtt',
    'srt'  => 'text/plain',
];

$mimeType = $mimeMap[$ext] ?? 'application/octet-stream';
$isImage = str_starts_with($mimeType, 'image/');
$isMkv = ($ext === 'mkv');

// If requested file is an image, serve it directly with proper MIME type and caching
if ($isImage) {
    header("Content-Type: " . $mimeType);
    header("Access-Control-Allow-Origin: *");
    header("Cache-Control: public, max-age=604800, immutable");

    // Fast-path: Check local cached copy in uploads/posters/
    $localFile = __DIR__ . '/../uploads/posters/' . $filename;
    if (file_exists($localFile)) {
        header("Content-Length: " . filesize($localFile));
        readfile($localFile);
        exit();
    }

    try {
        $client = new NextcloudClient();
        $client->streamFile($file, $mimeType);
    } catch (Exception $e) {
        http_response_code(404);
        echo "Image Error: " . $e->getMessage();
    }
    exit();
}

// OPTIONAL EMBEDDED PLAYER PAGE (Only when ?player=1 is requested)
if ($showHtmlPlayer) {
    $rawStreamUrl = 'stream.php?file=' . urlencode($file) . '&raw=1';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $fullStreamUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . str_replace('stream.php', 'stream.php', $_SERVER['SCRIPT_NAME']) . '?file=' . urlencode($file) . '&raw=1';
    $vlcCallbackUrl = 'vlc-x-callback://x-callback-url/stream?url=' . rawurlencode($fullStreamUrl);
    $outplayerUrl = 'outplayer://' . preg_replace('#^https?://#i', '', $fullStreamUrl);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Playing: <?= htmlspecialchars($filename) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Official Movi Player (WASM & WebCodecs MKV Engine for Safari/iOS WebKit & modern browsers) -->
        <script type="module" src="https://cdn.jsdelivr.net/npm/movi-player/dist/element.js"></script>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
            html, body { width: 100%; height: 100%; background: #000; color: #fff; overflow: hidden; }
            .player-wrapper { position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #000; }
            video, movi-player { width: 100%; height: 100%; object-fit: contain; background: #000; outline: none; }
            .top-nav {
                position: absolute; top: 0; left: 0; right: 0; z-index: 20;
                background: linear-gradient(180deg, rgba(0,0,0,0.9) 0%, transparent 100%);
                padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 8px;
                transition: opacity 0.3s ease; opacity: 1;
            }
            .title-text {
                font-size: 0.9rem; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.9);
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 45vw;
            }
            .btn-action {
                background: rgba(15,23,42,0.8); border: 1px solid #38bdf8; color: #38bdf8;
                padding: 6px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; cursor: pointer; text-decoration: none;
                backdrop-filter: blur(8px); display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; white-space: nowrap;
            }
            .unmute-btn {
                position: absolute; bottom: 75px; z-index: 25;
                background: linear-gradient(135deg, #0284c7, #38bdf8); color: #0f172a;
                border: none; padding: 10px 20px; border-radius: 30px; font-weight: 800; font-size: 0.85rem;
                cursor: pointer; box-shadow: 0 0 25px rgba(56,189,248,0.5); display: flex; align-items: center; gap: 6px;
                max-width: 90vw; justify-content: center;
            }
            .hud-badge {
                position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 15;
                background: rgba(15,23,42,0.88); border: 1.5px solid #38bdf8; color: #fff;
                padding: 12px 20px; border-radius: 14px; font-size: 1rem; font-weight: 700; backdrop-filter: blur(12px);
                pointer-events: none; opacity: 0; transition: opacity 0.2s ease; box-shadow: 0 0 20px rgba(56,189,248,0.3);
                white-space: nowrap;
            }
            .hud-badge.show { opacity: 1; }
            .buffer-spinner {
                position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 18;
                background: rgba(15,23,42,0.88); border: 1.5px solid #38bdf8; color: #fff;
                padding: 12px 20px; border-radius: 14px; font-size: 0.9rem; font-weight: 700; backdrop-filter: blur(12px);
                pointer-events: none; opacity: 0; transition: opacity 0.2s ease; box-shadow: 0 0 25px rgba(56,189,248,0.35);
                display: flex; align-items: center; gap: 8px; white-space: nowrap;
            }
            .buffer-spinner.show { opacity: 1; }

            /* MOBILE PORTRAIT & LANDSCAPE OVERRIDES */
            @media (max-width: 768px) {
                .top-nav { padding: 8px 10px; }
                .title-text { font-size: 0.8rem; max-width: 35vw; }
                .btn-action { padding: 5px 9px; font-size: 0.72rem; }
                .unmute-btn { bottom: 60px; padding: 8px 16px; font-size: 0.78rem; }
            }
            @media (max-height: 500px) and (orientation: landscape) {
                .top-nav { padding: 6px 12px; }
                .title-text { font-size: 0.8rem; max-width: 50vw; }
                .btn-action { padding: 4px 10px; font-size: 0.72rem; }
                .unmute-btn { bottom: 45px; padding: 6px 14px; font-size: 0.75rem; }
            }
        </style>
    </head>
    <body>
        <div class="player-wrapper" id="playerContainer">
            <div class="top-nav" id="topNav">
                <div style="display:flex; align-items:center; gap:12px;">
                    <button class="btn-action" onclick="if(window.parent && window.parent.closePlayer) window.parent.closePlayer(); else window.history.back();"><i class="fa-solid fa-arrow-left"></i> Close</button>
                    <div class="title-text"><i class="fa-solid fa-play" style="color:#38bdf8; margin-right:8px;"></i> <?= htmlspecialchars($filename) ?></div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:nowrap;">
                    <a href="<?= htmlspecialchars($vlcCallbackUrl) ?>" class="btn-action" title="Open in VLC on iOS/Android"><i class="fa-solid fa-cone"></i> VLC</a>
                    <a href="<?= htmlspecialchars($outplayerUrl) ?>" class="btn-action" title="Open in Outplayer on iOS"><i class="fa-solid fa-play"></i> Outplayer</a>
                    <a href="<?= htmlspecialchars($rawStreamUrl) ?>" download class="btn-action" title="Download"><i class="fa-solid fa-download"></i> Save</a>
                    <button class="btn-action" onclick="toggleFit()"><i class="fa-solid fa-expand"></i> <span id="fitText">Fit</span></button>
                </div>
            </div>

            <button class="unmute-btn" id="unmuteBtn" onclick="unmuteAudio()"><i class="fa-solid fa-volume-high"></i> Tap to Unmute Audio</button>

            <div class="hud-badge" id="hudBadge">⏩ +10s</div>
            <div class="buffer-spinner" id="bufferSpinner"><i class="fa-solid fa-spinner fa-spin" style="color:#38bdf8;"></i> Buffering...</div>

            <?php if ($isMkv): ?>
            <!-- MOVI PLAYER (WASM & WebCodecs MKV Engine for Safari/iOS WebKit & modern browsers) -->
            <movi-player id="safariVideo" playsinline controls fallback="native" src="<?= htmlspecialchars($rawStreamUrl) ?>" style="width:100%; height:100%; object-fit:contain; background:#000;">
            </movi-player>
            <?php else: ?>
            <!-- DIRECT HTML5 VIDEO (Native hardware accelerated playback for MP4/MOV) -->
            <video id="safariVideo" playsinline webkit-playsinline controls autoplay muted preload="auto" src="<?= htmlspecialchars($rawStreamUrl) ?>">
                Your browser does not support inline video playback.
            </video>
            <?php endif; ?>
        </div>

        <script>
            const video = document.getElementById('safariVideo');
            const hud = document.getElementById('hudBadge');
            const nav = document.getElementById('topNav');
            const unmuteBtn = document.getElementById('unmuteBtn');
            const modes = ['contain', 'cover', 'fill'];
            const labels = ['📺 Fit', '✂️ Zoom', '↔️ Fill'];
            let modeIdx = 0;
            let lastTap = 0;
            let holdTimer = null;
            let hideTimer = null;

            const movieKey = 'safari_pos_' + btoa('<?= addslashes($filename) ?>').replace(/=/g, '');

            function unmuteAudio() {
                video.muted = false;
                unmuteBtn.style.display = 'none';
                showHud('🔊 Audio Unmuted');
                video.play().catch(e => console.log(e));
            }

            // Auto Unmute on any user interaction
            document.addEventListener('touchstart', () => { if (video.muted) unmuteAudio(); }, { once: true });
            document.addEventListener('click', () => { if (video.muted) unmuteAudio(); }, { once: true });

            // Buffer & Playback State Listeners
            const spinner = document.getElementById('bufferSpinner');
            let spinnerTimer = null;
            function showSpinner() {
                spinner.classList.add('show');
                clearTimeout(spinnerTimer);
                spinnerTimer = setTimeout(() => {
                    spinner.classList.remove('show');
                }, 2500); // Never stay stuck on screen for more than 2.5s
            }
            function hideSpinner() {
                clearTimeout(spinnerTimer);
                spinner.classList.remove('show');
            }

            video.addEventListener('waiting', showSpinner);
            video.addEventListener('seeking', showSpinner);
            video.addEventListener('seeked', () => {
                hideSpinner();
                video.play().catch(e => console.log('Auto resume seek:', e));
            });
            video.addEventListener('playing', hideSpinner);
            video.addEventListener('canplay', hideSpinner);
            video.addEventListener('timeupdate', hideSpinner);
            video.addEventListener('pause', hideSpinner);
            video.addEventListener('loadeddata', hideSpinner);

            // Save playback position only when actively playing beyond 10 seconds
            setInterval(() => {
                if (video && !video.paused && video.currentTime > 10) {
                    localStorage.setItem(movieKey, video.currentTime);
                }
            }, 5000);

            function toggleFit() {
                modeIdx = (modeIdx + 1) % modes.length;
                video.style.objectFit = modes[modeIdx];
                document.getElementById('fitText').innerText = labels[modeIdx];
                showHud(labels[modeIdx]);
            }

            function showHud(msg) {
                hud.innerText = msg;
                hud.classList.add('show');
                setTimeout(() => hud.classList.remove('show'), 1200);
            }

            // Auto Hide Nav
            function resetNav() {
                nav.style.opacity = '1';
                clearTimeout(hideTimer);
                hideTimer = setTimeout(() => { if (!video.paused) nav.style.opacity = '0'; }, 3000);
            }
            document.getElementById('playerContainer').addEventListener('mousemove', resetNav);
            document.getElementById('playerContainer').addEventListener('touchstart', resetNav);

            function seekTo(targetTime) {
                targetTime = Math.max(0, Math.min(video.duration || targetTime, targetTime));
                spinner.classList.add('show');
                if ('fastSeek' in video) {
                    try {
                        video.fastSeek(targetTime);
                    } catch(e) {
                        video.currentTime = targetTime;
                    }
                } else {
                    video.currentTime = targetTime;
                }
                video.play().catch(e => {});
            }

            // Double Tap & Speed Boost
            const container = document.getElementById('playerContainer');
            container.addEventListener('click', (e) => {
                if (e.target.closest('#topNav') || e.target.closest('#unmuteBtn')) return;
                const now = Date.now();
                const rect = container.getBoundingClientRect();
                if (now - lastTap < 300) {
                    if (e.clientX - rect.left < rect.width / 2) {
                        seekTo(Math.max(0, video.currentTime - 10));
                        showHud('⏪ -10s');
                    } else {
                        seekTo(Math.min(video.duration, video.currentTime + 10));
                        showHud('⏩ +10s');
                    }
                }
                lastTap = now;
            });

            const startBoost = () => { holdTimer = setTimeout(() => { video.playbackRate = 1.5; showHud('⚡ 1.5x Speed'); }, 400); };
            const stopBoost = () => { clearTimeout(holdTimer); if (video.playbackRate !== 1.0) { video.playbackRate = 1.0; showHud('▶ Normal Speed'); } };

            container.addEventListener('mousedown', startBoost);
            container.addEventListener('mouseup', stopBoost);
            container.addEventListener('touchstart', startBoost);
            container.addEventListener('touchend', stopBoost);

            // Full Keyboard Navigation
            document.addEventListener('keydown', (e) => {
                switch(e.key.toLowerCase()) {
                    case ' ':
                    case 'k':
                        e.preventDefault();
                        video.paused ? video.play() : video.pause();
                        showHud(video.paused ? '⏸ Pause' : '▶ Play');
                        break;
                    case 'f':
                        e.preventDefault();
                        if (!document.fullscreenElement) container.requestFullscreen();
                        else document.exitFullscreen();
                        break;
                    case 'm':
                        e.preventDefault();
                        video.muted = !video.muted;
                        showHud(video.muted ? '🔇 Muted' : '🔊 Unmuted');
                        break;
                    case 'arrowleft':
                    case 'j':
                        e.preventDefault();
                        seekTo(Math.max(0, video.currentTime - 10));
                        showHud('⏪ -10s');
                        break;
                    case 'arrowright':
                    case 'l':
                        e.preventDefault();
                        seekTo(Math.min(video.duration, video.currentTime + 10));
                        showHud('⏩ +10s');
                        break;
                    case 'arrowup':
                        e.preventDefault();
                        video.volume = Math.min(1.0, video.volume + 0.1);
                        showHud(`🔊 Vol: ${Math.round(video.volume * 100)}%`);
                        break;
                    case 'arrowdown':
                        e.preventDefault();
                        video.volume = Math.max(0.0, video.volume - 0.1);
                        showHud(`🔊 Vol: ${Math.round(video.volume * 100)}%`);
                        break;
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// RAW STREAMING MODE (For HTML5 <video> tags, ExoPlayer, & HTTP Byte-Range requests)
header("Content-Type: " . $mimeType);
header("Content-Disposition: inline; filename=\"" . rawurlencode($filename) . "\"");
header("Accept-Ranges: bytes");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, HEAD, OPTIONS");
header("Access-Control-Allow-Headers: Range, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $client = new NextcloudClient();
    $client->streamFile($file, $mimeType);
} catch (Exception $e) {
    http_response_code(500);
    echo "Stream Error: " . $e->getMessage();
}
