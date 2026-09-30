package com.movieapp;

import android.content.Context;
import android.media.AudioManager;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.Settings;
import android.view.MotionEvent;
import android.view.View;
import android.view.WindowManager;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.media3.common.MediaItem;
import androidx.media3.common.PlaybackException;
import androidx.media3.common.Player;
import androidx.media3.datasource.DefaultHttpDataSource;
import android.content.Intent;
import androidx.media3.exoplayer.DefaultLoadControl;
import androidx.media3.exoplayer.DefaultRenderersFactory;
import androidx.media3.exoplayer.ExoPlayer;
import androidx.media3.exoplayer.LoadControl;
import androidx.media3.exoplayer.source.MediaSource;
import androidx.media3.exoplayer.source.ProgressiveMediaSource;
import androidx.media3.exoplayer.trackselection.DefaultTrackSelector;
import androidx.media3.ui.PlayerView;
import com.google.android.material.card.MaterialCardView;
import java.util.HashMap;
import java.util.Map;
import android.util.Base64;
import android.os.Build;
import android.view.Window;
import android.view.WindowInsets;
import android.view.WindowInsetsController;
import android.widget.ImageButton;
import androidx.appcompat.app.AlertDialog;
import androidx.media3.common.C;
import androidx.media3.common.Format;
import androidx.media3.common.TrackSelectionOverride;
import androidx.media3.common.Tracks;
import com.google.android.material.button.MaterialButton;
import java.util.ArrayList;
import java.util.List;

import android.content.SharedPreferences;
import android.view.GestureDetector;
import androidx.media3.common.PlaybackParameters;
import androidx.media3.ui.AspectRatioFrameLayout;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.SessionManager;
import okhttp3.FormBody;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class PlayerActivity extends AppCompatActivity {

    private PlayerView playerView;
    private ExoPlayer player;
    private MaterialCardView gestureCard;
    private TextView tvGestureText;
    private TextView tvMovieTitle;
    private ImageButton btnBack;
    private ImageButton btnResizeMode;
    private ImageButton btnSubtitle;
    private ImageButton btnAudioTrack;
    private ImageButton btnExternalPlayer;
    private ImageButton btnToggleOrientation;
    private View topBar;
    private String currentVideoUrl;

    private int currentResizeModeIndex = 0;
    private final int[] RESIZE_MODES = new int[]{
        AspectRatioFrameLayout.RESIZE_MODE_FIT,
        AspectRatioFrameLayout.RESIZE_MODE_ZOOM,
        AspectRatioFrameLayout.RESIZE_MODE_FILL
    };
    private final String[] RESIZE_MODE_NAMES = new String[]{
        "📺 Fit",
        "✂️ Zoom",
        "↔️ Fill"
    };
    private final String[] RESIZE_MODE_TOASTS = new String[]{
        "Aspect Ratio: Fit (Original Aspect Ratio)",
        "Aspect Ratio: Zoom / Crop (No Black Bezels)",
        "Aspect Ratio: Fill (Stretched Full Screen)"
    };

    private AudioManager audioManager;
    private int maxVolume;
    private int initialVolume;
    private float initialBrightness;

    private float startX = 0f;
    private float startY = 0f;
    private boolean isLeftSide = false;
    private boolean isGesturing = false;
    private boolean isLongPressSpeed = false;

    private SharedPreferences prefs;
    private String currentMediaKey;
    private boolean hasResumedPosition = false;

    private final Handler savePositionHandler = new Handler(Looper.getMainLooper());
    private final Runnable savePositionRunnable = new Runnable() {
        @Override
        public void run() {
            saveCurrentPosition();
            savePositionHandler.postDelayed(this, 3000);
        }
    };

    private final Handler hideHandler = new Handler(Looper.getMainLooper());
    private final Runnable hideRunnable = () -> {
        if (gestureCard != null) {
            gestureCard.setVisibility(View.GONE);
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_player);
        enableImmersiveFullScreen();

        playerView = findViewById(R.id.playerView);
        gestureCard = findViewById(R.id.gestureCard);
        tvGestureText = findViewById(R.id.tvGestureText);
        tvMovieTitle = findViewById(R.id.tvMovieTitle);
        btnBack = findViewById(R.id.btnBack);
        btnResizeMode = findViewById(R.id.btnResizeMode);
        btnSubtitle = findViewById(R.id.btnSubtitle);
        btnAudioTrack = findViewById(R.id.btnAudioTrack);
        btnExternalPlayer = findViewById(R.id.btnExternalPlayer);
        btnToggleOrientation = findViewById(R.id.btnToggleOrientation);
        topBar = findViewById(R.id.topBar);

        // Auto-hide Top Bar together with ExoPlayer controls
        playerView.setControllerVisibilityListener((PlayerView.ControllerVisibilityListener) visibility -> {
            if (topBar != null) {
                topBar.setVisibility(visibility);
            }
        });

        String title = getIntent().getStringExtra("TITLE");
        if (title == null || title.isEmpty()) {
            title = getIntent().getStringExtra("VIDEO_TITLE");
        }
        if (title != null && !title.isEmpty()) {
            tvMovieTitle.setText(title);
        }

        btnBack.setOnClickListener(v -> finish());
        btnResizeMode.setOnClickListener(v -> toggleResizeMode());
        btnSubtitle.setOnClickListener(v -> showSubtitleTrackDialog());
        btnAudioTrack.setOnClickListener(v -> showAudioTrackDialog());
        if (btnExternalPlayer != null) {
            btnExternalPlayer.setOnClickListener(v -> openInExternalPlayer(currentVideoUrl));
        }
        btnToggleOrientation.setOnClickListener(v -> toggleOrientation());

        audioManager = (AudioManager) getSystemService(Context.AUDIO_SERVICE);
        if (audioManager != null) {
            maxVolume = audioManager.getStreamMaxVolume(AudioManager.STREAM_MUSIC);
        }

        String videoUrl = getIntent().getStringExtra("VIDEO_URL");
        if (videoUrl == null || videoUrl.isEmpty()) {
            Toast.makeText(this, "Invalid Video Stream URL", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }
        currentVideoUrl = videoUrl;

        prefs = getSharedPreferences("SoniCinemasPlayback", Context.MODE_PRIVATE);
        String mediaId = (title != null && !title.isEmpty()) ? title : videoUrl;
        currentMediaKey = "pos_" + Math.abs(mediaId.hashCode());

        boolean isOffline = getIntent().getBooleanExtra("IS_OFFLINE", false) || videoUrl.startsWith("/") || videoUrl.startsWith("file://");
        initializePlayer(videoUrl, isOffline);
        setupGestureControls();
        if (!isOffline) {
            startAppLivePing(title != null ? title : "Android Movie Stream");
        } else {
            Toast.makeText(this, "Playing from offline storage 💾", Toast.LENGTH_SHORT).show();
        }
    }

    private Handler appPingHandler = new Handler(Looper.getMainLooper());
    private Runnable appPingRunnable;

    private void startAppLivePing(String title) {
        stopAppLivePing();
        com.movieapp.util.SessionManager sessionManager = new com.movieapp.util.SessionManager(this);
        int userId = sessionManager.getUserId();
        String token = sessionManager.getSessionToken() != null ? sessionManager.getSessionToken() : "";

        appPingRunnable = new Runnable() {
            @Override
            public void run() {
                new Thread(() -> {
                    try {
                        OkHttpClient client = RetrofitClient.getOkHttpClient();
                        RequestBody formBody = new FormBody.Builder()
                                .add("user_id", String.valueOf(userId))
                                .add("session_token", token)
                                .add("title", title)
                                .add("device", "Android App")
                                .add("action", "ping")
                                .build();
                        Request request = new Request.Builder()
                                .url(RetrofitClient.BASE_URL + "api/stream_ping.php")
                                .post(formBody)
                                .build();
                        client.newCall(request).execute();
                    } catch (Exception e) {}
                }).start();

                appPingHandler.postDelayed(this, 5000);
            }
        };
        appPingHandler.post(appPingRunnable);
    }

    private void stopAppLivePing() {
        if (appPingHandler != null && appPingRunnable != null) {
            appPingHandler.removeCallbacks(appPingRunnable);
        }
        com.movieapp.util.SessionManager sessionManager = new com.movieapp.util.SessionManager(this);
        int userId = sessionManager.getUserId();
        new Thread(() -> {
            try {
                OkHttpClient client = new OkHttpClient();
                RequestBody formBody = new FormBody.Builder()
                        .add("user_id", String.valueOf(userId))
                        .add("action", "stop")
                        .build();
                Request request = new Request.Builder()
                        .url(RetrofitClient.BASE_URL + "api/stream_ping.php")
                        .post(formBody)
                        .build();
                client.newCall(request).execute();
            } catch (Exception e) {}
        }).start();
    }

    private void enableImmersiveFullScreen() {
        Window window = getWindow();
        if (window == null) return;

        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);

        View decorView = window.getDecorView();
        if (decorView == null) return;

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            window.setDecorFitsSystemWindows(false);
            WindowInsetsController controller = decorView.getWindowInsetsController();
            if (controller != null) {
                controller.hide(WindowInsets.Type.statusBars() | WindowInsets.Type.navigationBars());
                controller.setSystemBarsBehavior(WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
            }
        } else {
            @SuppressWarnings("deprecation")
            int flags = View.SYSTEM_UI_FLAG_FULLSCREEN
                    | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                    | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                    | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                    | View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                    | View.SYSTEM_UI_FLAG_LAYOUT_STABLE;
            decorView.setSystemUiVisibility(flags);
        }
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) {
            enableImmersiveFullScreen();
        }
    }

    private void toggleOrientation() {
        int currentOrientation = getResources().getConfiguration().orientation;
        if (currentOrientation == android.content.res.Configuration.ORIENTATION_LANDSCAPE) {
            setRequestedOrientation(android.content.pm.ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);
            Toast.makeText(this, "Screen Orientation: Portrait 📱", Toast.LENGTH_SHORT).show();
        } else {
            setRequestedOrientation(android.content.pm.ActivityInfo.SCREEN_ORIENTATION_SENSOR_LANDSCAPE);
            Toast.makeText(this, "Screen Orientation: Landscape 🔄", Toast.LENGTH_SHORT).show();
        }
    }

    private void toggleResizeMode() {
        if (playerView == null) return;
        currentResizeModeIndex = (currentResizeModeIndex + 1) % RESIZE_MODES.length;
        playerView.setResizeMode(RESIZE_MODES[currentResizeModeIndex]);
        Toast.makeText(this, RESIZE_MODE_TOASTS[currentResizeModeIndex], Toast.LENGTH_SHORT).show();
    }

    private void showSubtitleTrackDialog() {
        if (player == null) return;

        Tracks currentTracks = player.getCurrentTracks();
        List<String> subTrackNames = new ArrayList<>();
        List<TrackSelectionOverride> subOverrides = new ArrayList<>();

        // Option 1: Turn off subtitles
        subTrackNames.add("🚫 Off (Disable Subtitles)");
        subOverrides.add(null);

        for (Tracks.Group group : currentTracks.getGroups()) {
            if (group.getType() == C.TRACK_TYPE_TEXT) {
                for (int i = 0; i < group.length; i++) {
                    Format format = group.getTrackFormat(i);
                    String lang = (format.language != null && !format.language.isEmpty()) ? format.language.toUpperCase() : "Subtitle " + subTrackNames.size();
                    String label = (format.label != null && !format.label.isEmpty()) ? format.label : lang;

                    subTrackNames.add(label);
                    TrackSelectionOverride override = new TrackSelectionOverride(group.getMediaTrackGroup(), i);
                    subOverrides.add(override);
                }
            }
        }

        if (subTrackNames.size() <= 1) {
            Toast.makeText(this, "No subtitle tracks found in video.", Toast.LENGTH_SHORT).show();
            return;
        }

        CharSequence[] items = subTrackNames.toArray(new CharSequence[0]);
        new AlertDialog.Builder(this)
                .setTitle("💬 Select Subtitle / CC")
                .setItems(items, (dialog, which) -> {
                    if (which == 0) {
                        // Disable subtitles
                        player.setTrackSelectionParameters(
                                player.getTrackSelectionParameters()
                                        .buildUpon()
                                        .setTrackTypeDisabled(C.TRACK_TYPE_TEXT, true)
                                        .clearOverridesOfType(C.TRACK_TYPE_TEXT)
                                        .build()
                        );
                        Toast.makeText(this, "Subtitles Disabled", Toast.LENGTH_SHORT).show();
                    } else {
                        TrackSelectionOverride selectedOverride = subOverrides.get(which);
                        player.setTrackSelectionParameters(
                                player.getTrackSelectionParameters()
                                        .buildUpon()
                                        .setTrackTypeDisabled(C.TRACK_TYPE_TEXT, false)
                                        .clearOverridesOfType(C.TRACK_TYPE_TEXT)
                                        .addOverride(selectedOverride)
                                        .build()
                        );
                        Toast.makeText(this, "Subtitles: " + subTrackNames.get(which), Toast.LENGTH_SHORT).show();
                    }
                })
                .setNegativeButton("Cancel", null)
                .show();
    }

    private void showAudioTrackDialog() {
        if (player == null) return;

        Tracks currentTracks = player.getCurrentTracks();
        List<String> audioTrackNames = new ArrayList<>();
        List<TrackSelectionOverride> trackOverrides = new ArrayList<>();

        for (Tracks.Group group : currentTracks.getGroups()) {
            if (group.getType() == C.TRACK_TYPE_AUDIO) {
                for (int i = 0; i < group.length; i++) {
                    Format format = group.getTrackFormat(i);
                    String lang = (format.language != null && !format.language.isEmpty()) ? format.language.toUpperCase() : "Audio Track " + (audioTrackNames.size() + 1);
                    String label = format.label != null && !format.label.isEmpty() ? format.label : lang;
                    boolean isSupported = group.isTrackSupported(i);
                    String trackName = label + (format.bitrate > 0 ? " (" + (format.bitrate / 1000) + " kbps)" : "") + (isSupported ? "" : " ⚠️ (Unsupported on TV)");

                    audioTrackNames.add(trackName);
                    TrackSelectionOverride override = new TrackSelectionOverride(group.getMediaTrackGroup(), i);
                    trackOverrides.add(override);
                }
            }
        }

        if (audioTrackNames.isEmpty()) {
            Toast.makeText(this, "No alternate audio tracks found in video.", Toast.LENGTH_SHORT).show();
            return;
        }

        CharSequence[] items = audioTrackNames.toArray(new CharSequence[0]);
        new AlertDialog.Builder(this)
                .setTitle("🎧 Select Audio Language / Track")
                .setItems(items, (dialog, which) -> {
                    TrackSelectionOverride selectedOverride = trackOverrides.get(which);
                    player.setTrackSelectionParameters(
                            player.getTrackSelectionParameters()
                                    .buildUpon()
                                    .clearOverridesOfType(C.TRACK_TYPE_AUDIO)
                                    .addOverride(selectedOverride)
                                    .build()
                    );
                    Toast.makeText(this, "Switched Audio to: " + audioTrackNames.get(which), Toast.LENGTH_SHORT).show();
                })
                .setNegativeButton("Cancel", null)
                .show();
    }

    public void openInExternalPlayer(String rawOrFinalUrl) {
        if (rawOrFinalUrl == null || rawOrFinalUrl.isEmpty()) return;
        try {
            String playUrl = rawOrFinalUrl;
            if (!playUrl.startsWith("file://") && !playUrl.startsWith("/")) {
                com.movieapp.util.SessionManager sessionManager = new com.movieapp.util.SessionManager(this);
                String token = sessionManager.getSessionToken();
                if (token != null && !token.isEmpty() && !playUrl.contains("session_token=")) {
                    playUrl += (playUrl.contains("?") ? "&" : "?") + "session_token=" + Uri.encode(token);
                }
                if (!playUrl.contains("raw=1")) {
                    playUrl += (playUrl.contains("?") ? "&" : "?") + "raw=1";
                }
            }
            Uri uri = playUrl.startsWith("file://") ? Uri.parse(playUrl) : (playUrl.startsWith("/") ? Uri.fromFile(new java.io.File(playUrl)) : Uri.parse(playUrl));
            Intent intent = new Intent(Intent.ACTION_VIEW);
            intent.setDataAndType(uri, "video/*");
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            startActivity(Intent.createChooser(intent, "Open with Video Player (e.g. VLC / MX Player)"));
        } catch (Exception e) {
            Toast.makeText(this, "No external video player found (please install VLC or MX Player).", Toast.LENGTH_LONG).show();
        }
    }

    private void initializePlayer(String videoUrl, boolean isOffline) {
        DefaultTrackSelector trackSelector = new DefaultTrackSelector(this);
        trackSelector.setParameters(
                trackSelector.buildUponParameters()
                        .setExceedRendererCapabilitiesIfNecessary(false)
                        .setExceedAudioConstraintsIfNecessary(false)
                        .build()
        );

        DefaultRenderersFactory renderersFactory = new DefaultRenderersFactory(this)
                .setEnableDecoderFallback(true)
                .setExtensionRendererMode(DefaultRenderersFactory.EXTENSION_RENDERER_MODE_ON);

        LoadControl loadControl = new DefaultLoadControl.Builder()
                .setBufferDurationsMs(
                        2500,   // Min buffer before stopping (2.5s)
                        50000,  // Max buffer (50s)
                        1000,   // Buffer to start playback (1s -> Smooth Instant Start!)
                        1500    // Buffer to resume playback (1.5s)
                ).build();

        MediaSource mediaSource;
        if (isOffline) {
            Uri localUri = videoUrl.startsWith("file://") ? Uri.parse(videoUrl) : Uri.fromFile(new java.io.File(videoUrl));
            androidx.media3.datasource.DefaultDataSource.Factory defaultDataSourceFactory =
                    new androidx.media3.datasource.DefaultDataSource.Factory(this);
            mediaSource = new ProgressiveMediaSource.Factory(defaultDataSourceFactory)
                    .createMediaSource(MediaItem.fromUri(localUri));
        } else {
            String finalUrl = videoUrl;
            com.movieapp.util.SessionManager sessionManager = new com.movieapp.util.SessionManager(this);
            String token = sessionManager.getSessionToken();
            if (token != null && !token.isEmpty()) {
                if (!finalUrl.contains("session_token=")) {
                    finalUrl += (finalUrl.contains("?") ? "&" : "?") + "session_token=" + Uri.encode(token);
                }
            }
            if (!finalUrl.contains("raw=1")) {
                finalUrl += (finalUrl.contains("?") ? "&" : "?") + "raw=1";
            }
            Map<String, String> defaultRequestHeaders = new HashMap<>();

            DefaultHttpDataSource.Factory dataSourceFactory = new DefaultHttpDataSource.Factory()
                    .setConnectTimeoutMs(15000)
                    .setReadTimeoutMs(15000)
                    .setDefaultRequestProperties(defaultRequestHeaders)
                    .setAllowCrossProtocolRedirects(true);

            mediaSource = new ProgressiveMediaSource.Factory(dataSourceFactory)
                    .createMediaSource(MediaItem.fromUri(Uri.parse(finalUrl)));
        }

        player = new ExoPlayer.Builder(this, renderersFactory)
                .setTrackSelector(trackSelector)
                .setLoadControl(loadControl)
                .build();

        playerView.setPlayer(player);
        player.setMediaSource(mediaSource);

        player.addListener(new Player.Listener() {
            @Override
            public void onPlayerError(PlaybackException error) {
                String errorMsg = error.getMessage() != null ? error.getMessage() : "";
                Throwable cause = error.getCause();
                String causeMsg = cause != null && cause.getMessage() != null ? cause.getMessage() : "";

                boolean isAudioCodecError = errorMsg.contains("MediaCodecAudioRenderer")
                        || errorMsg.contains("unsupported type")
                        || causeMsg.contains("MediaCodecAudioRenderer")
                        || causeMsg.contains("NO_UNSUPPORTED_TYPE")
                        || error.errorCode == PlaybackException.ERROR_CODE_DECODER_INIT_FAILED;

                if (isAudioCodecError) {
                    new AlertDialog.Builder(PlayerActivity.this)
                            .setTitle("Audio Codec Unsupported")
                            .setMessage("The audio format of this video (e.g. Dolby AC3/EAC3) is not supported by this Android 4.4 TV hardware.\n\nWould you like to open it in an external player (like VLC or MX Player) for audio, or play without audio?")
                            .setPositiveButton("Open in VLC / Player", (dialog, which) -> {
                                openInExternalPlayer(currentVideoUrl);
                            })
                            .setNeutralButton("Play Without Audio", (dialog, which) -> {
                                if (player != null) {
                                    player.setTrackSelectionParameters(
                                            player.getTrackSelectionParameters()
                                                    .buildUpon()
                                                    .setTrackTypeDisabled(C.TRACK_TYPE_AUDIO, true)
                                                    .build()
                                    );
                                    player.prepare();
                                    player.play();
                                }
                            })
                            .setNegativeButton("Cancel", (dialog, which) -> finish())
                            .show();
                } else {
                    Toast.makeText(PlayerActivity.this, "Playback Error: " + error.getMessage(), Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onPlaybackStateChanged(int playbackState) {
                if (playbackState == Player.STATE_READY) {
                    if (!hasResumedPosition && currentMediaKey != null && prefs != null) {
                        long savedPos = prefs.getLong(currentMediaKey, 0);
                        long duration = player.getDuration();
                        if (savedPos > 3000 && (duration <= 0 || savedPos < duration - 15000)) {
                            player.seekTo(savedPos);
                            long minutes = (savedPos / 1000) / 60;
                            long seconds = (savedPos / 1000) % 60;
                            String timeStr = String.format("%02d:%02d", minutes, seconds);
                            Toast.makeText(PlayerActivity.this, "▶ Resumed watching from " + timeStr, Toast.LENGTH_SHORT).show();
                        }
                        hasResumedPosition = true;
                    }
                    if (!player.isPlaying()) {
                        player.play();
                    }
                }
            }
        });

        player.prepare();
        player.setPlayWhenReady(true);
    }

    private void setupGestureControls() {
        GestureDetector gestureDetector = new GestureDetector(this, new GestureDetector.SimpleOnGestureListener() {
            @Override
            public boolean onDoubleTap(MotionEvent e) {
                if (player == null) return false;
                int screenWidth = getResources().getDisplayMetrics().widthPixels;
                hideHandler.removeCallbacks(hideRunnable);
                gestureCard.setVisibility(View.VISIBLE);

                if (e.getX() < (screenWidth / 2.0f)) {
                    long targetPos = Math.max(0, player.getCurrentPosition() - 10000);
                    player.seekTo(targetPos);
                    tvGestureText.setText("⏪ -10s");
                } else {
                    long targetPos = Math.min(player.getDuration(), player.getCurrentPosition() + 10000);
                    player.seekTo(targetPos);
                    tvGestureText.setText("⏩ +10s");
                }
                hideHandler.postDelayed(hideRunnable, 1000);
                return true;
            }

            @Override
            public void onLongPress(MotionEvent e) {
                if (player == null) return;
                isLongPressSpeed = true;
                player.setPlaybackParameters(new PlaybackParameters(1.5f));
                hideHandler.removeCallbacks(hideRunnable);
                gestureCard.setVisibility(View.VISIBLE);
                tvGestureText.setText("⚡ 1.5x Speed");
            }

            @Override
            public boolean onSingleTapConfirmed(MotionEvent e) {
                if (playerView != null) {
                    if (playerView.isControllerFullyVisible()) {
                        playerView.hideController();
                    } else {
                        playerView.showController();
                    }
                }
                return true;
            }
        });

        playerView.setOnTouchListener((v, event) -> {
            gestureDetector.onTouchEvent(event);

            int screenWidth = getResources().getDisplayMetrics().widthPixels;
            int screenHeight = getResources().getDisplayMetrics().heightPixels;

            switch (event.getAction()) {
                case MotionEvent.ACTION_DOWN:
                    startX = event.getX();
                    startY = event.getY();
                    isLeftSide = startX < (screenWidth / 2.0f);
                    isGesturing = false;

                    if (audioManager != null) {
                        initialVolume = audioManager.getStreamVolume(AudioManager.STREAM_MUSIC);
                    }

                    WindowManager.LayoutParams lp = getWindow().getAttributes();
                    if (lp.screenBrightness < 0) {
                        try {
                            int sysBright = Settings.System.getInt(getContentResolver(), Settings.System.SCREEN_BRIGHTNESS);
                            initialBrightness = sysBright / 255.0f;
                        } catch (Settings.SettingNotFoundException e) {
                            initialBrightness = 0.5f;
                        }
                    } else {
                        initialBrightness = lp.screenBrightness;
                    }
                    break;

                case MotionEvent.ACTION_MOVE:
                    float deltaY = startY - event.getY();
                    float deltaX = Math.abs(event.getX() - startX);

                    if (Math.abs(deltaY) > 30 && Math.abs(deltaY) > deltaX && !isLongPressSpeed) {
                        isGesturing = true;
                        float deltaPercent = deltaY / (screenHeight * 0.6f);

                        hideHandler.removeCallbacks(hideRunnable);
                        gestureCard.setVisibility(View.VISIBLE);

                        if (isLeftSide) {
                            int targetVolume = initialVolume + (int) (deltaPercent * maxVolume);
                            targetVolume = Math.max(0, Math.min(maxVolume, targetVolume));

                            if (audioManager != null) {
                                audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, targetVolume, 0);
                            }

                            int volPercent = (int) (((float) targetVolume / maxVolume) * 100);
                            tvGestureText.setText("🔊 Volume: " + volPercent + "%");
                        } else {
                            float targetBrightness = initialBrightness + deltaPercent;
                            targetBrightness = Math.max(0.01f, Math.min(1.00f, targetBrightness));

                            WindowManager.LayoutParams params = getWindow().getAttributes();
                            params.screenBrightness = targetBrightness;
                            getWindow().setAttributes(params);

                            int brightPercent = (int) (targetBrightness * 100);
                            tvGestureText.setText("☀️ Brightness: " + brightPercent + "%");
                        }
                    }
                    break;

                case MotionEvent.ACTION_UP:
                case MotionEvent.ACTION_CANCEL:
                    if (isLongPressSpeed) {
                        if (player != null) {
                            player.setPlaybackParameters(new PlaybackParameters(1.0f));
                        }
                        hideHandler.postDelayed(hideRunnable, 500);
                        isLongPressSpeed = false;
                    } else if (isGesturing) {
                        hideHandler.postDelayed(hideRunnable, 1000);
                    }
                    break;
            }
            return true;
        });
    }

    private void saveCurrentPosition() {
        if (player == null || currentMediaKey == null || prefs == null) return;
        long currentPos = player.getCurrentPosition();
        long duration = player.getDuration();

        if (duration > 0 && (duration - currentPos) < 15000) {
            // Video finished or within last 15 seconds -> clear saved position
            prefs.edit().remove(currentMediaKey).apply();
        } else if (currentPos > 3000) {
            prefs.edit().putLong(currentMediaKey, currentPos).apply();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        savePositionHandler.post(savePositionRunnable);
    }

    @Override
    protected void onPause() {
        super.onPause();
        saveCurrentPosition();
        savePositionHandler.removeCallbacks(savePositionRunnable);
    }

    @Override
    protected void onStop() {
        super.onStop();
        stopAppLivePing();
        saveCurrentPosition();
        savePositionHandler.removeCallbacks(savePositionRunnable);
        if (player != null) {
            player.release();
            player = null;
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        stopAppLivePing();
        saveCurrentPosition();
        savePositionHandler.removeCallbacks(savePositionRunnable);
        if (player != null) {
            player.release();
            player = null;
        }
    }

    private void showRemoteHud(String message) {
        if (gestureCard == null || tvGestureText == null) return;
        tvGestureText.setText(message);
        gestureCard.setVisibility(View.VISIBLE);
        hideHandler.removeCallbacks(hideRunnable);
        hideHandler.postDelayed(hideRunnable, 1200);
    }

    private void adjustVolumeBy(int step) {
        if (audioManager == null) return;
        int current = audioManager.getStreamVolume(AudioManager.STREAM_MUSIC);
        int target = Math.max(0, Math.min(maxVolume, current + step));
        audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, target, 0);
        int volPercent = (int) (((float) target / maxVolume) * 100);
        showRemoteHud("🔊 Volume: " + volPercent + "%");
    }

    private void togglePlayPause() {
        if (player == null) return;
        if (player.isPlaying()) {
            player.pause();
            showRemoteHud("⏸ Paused");
        } else {
            player.play();
            showRemoteHud("▶ Playing");
        }
    }

    private void seekRelative(long deltaMs, String label) {
        if (player == null) return;
        long duration = player.getDuration();
        long newPos = Math.max(0, duration > 0 ? Math.min(duration, player.getCurrentPosition() + deltaMs) : player.getCurrentPosition() + deltaMs);
        player.seekTo(newPos);
        showRemoteHud(label);
    }

    @Override
    public boolean onKeyDown(int keyCode, android.view.KeyEvent event) {
        View currentFocus = getCurrentFocus();
        boolean isTopBarFocused = (currentFocus != null && topBar != null && topBar.getVisibility() == View.VISIBLE && currentFocus != playerView);

        switch (keyCode) {
            case android.view.KeyEvent.KEYCODE_DPAD_CENTER:
            case android.view.KeyEvent.KEYCODE_ENTER:
            case android.view.KeyEvent.KEYCODE_NUMPAD_ENTER:
                if (isTopBarFocused) {
                    return super.onKeyDown(keyCode, event);
                }
                togglePlayPause();
                return true;

            case android.view.KeyEvent.KEYCODE_MEDIA_PLAY_PAUSE:
            case android.view.KeyEvent.KEYCODE_MEDIA_PLAY:
            case android.view.KeyEvent.KEYCODE_MEDIA_PAUSE:
                togglePlayPause();
                return true;

            case android.view.KeyEvent.KEYCODE_DPAD_LEFT:
            case android.view.KeyEvent.KEYCODE_MEDIA_REWIND:
                seekRelative(-10000, "⏪ -10s");
                return true;

            case android.view.KeyEvent.KEYCODE_DPAD_RIGHT:
            case android.view.KeyEvent.KEYCODE_MEDIA_FAST_FORWARD:
                seekRelative(10000, "⏩ +10s");
                return true;

            case android.view.KeyEvent.KEYCODE_CHANNEL_UP:
                seekRelative(60000, "⏩ +1 Min");
                return true;

            case android.view.KeyEvent.KEYCODE_CHANNEL_DOWN:
                seekRelative(-60000, "⏪ -1 Min");
                return true;

            case android.view.KeyEvent.KEYCODE_DPAD_UP:
                if (!isTopBarFocused && topBar != null) {
                    if (topBar.getVisibility() != View.VISIBLE) {
                        topBar.setVisibility(View.VISIBLE);
                        if (btnBack != null) btnBack.requestFocus();
                        return true;
                    }
                }
                adjustVolumeBy(1);
                return true;

            case android.view.KeyEvent.KEYCODE_DPAD_DOWN:
                if (isTopBarFocused && topBar != null) {
                    topBar.setVisibility(View.GONE);
                    if (playerView != null) playerView.requestFocus();
                    return true;
                }
                adjustVolumeBy(-1);
                return true;

            case android.view.KeyEvent.KEYCODE_VOLUME_UP:
                adjustVolumeBy(1);
                return true;

            case android.view.KeyEvent.KEYCODE_VOLUME_DOWN:
                adjustVolumeBy(-1);
                return true;

            case android.view.KeyEvent.KEYCODE_VOLUME_MUTE:
                if (audioManager != null) {
                    int current = audioManager.getStreamVolume(AudioManager.STREAM_MUSIC);
                    if (current > 0) {
                        initialVolume = current;
                        audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, 0, 0);
                        showRemoteHud("🔇 Muted");
                    } else {
                        audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, initialVolume > 0 ? initialVolume : maxVolume / 2, 0);
                        showRemoteHud("🔊 Unmuted");
                    }
                }
                return true;

            case android.view.KeyEvent.KEYCODE_MENU:
                if (topBar != null) {
                    if (topBar.getVisibility() == View.VISIBLE) {
                        topBar.setVisibility(View.GONE);
                    } else {
                        topBar.setVisibility(View.VISIBLE);
                        if (btnAudioTrack != null) btnAudioTrack.requestFocus();
                    }
                }
                return true;

            case android.view.KeyEvent.KEYCODE_MEDIA_STOP:
                finish();
                return true;

            case android.view.KeyEvent.KEYCODE_CAPTIONS:
                showSubtitleTrackDialog();
                return true;

            case android.view.KeyEvent.KEYCODE_MEDIA_AUDIO_TRACK:
                showAudioTrackDialog();
                return true;

            case android.view.KeyEvent.KEYCODE_BACK:
            case android.view.KeyEvent.KEYCODE_ESCAPE:
                if (topBar != null && topBar.getVisibility() == View.VISIBLE) {
                    topBar.setVisibility(View.GONE);
                    return true;
                }
                finish();
                return true;
        }
        return super.onKeyDown(keyCode, event);
    }
}
