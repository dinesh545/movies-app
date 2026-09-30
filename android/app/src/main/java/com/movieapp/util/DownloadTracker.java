package com.movieapp.util;

import android.content.Context;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Environment;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.movieapp.model.DownloadItem;
import java.io.File;
import java.io.IOException;
import java.lang.reflect.Type;
import java.text.DecimalFormat;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class DownloadTracker {

    private static final String PREF_NAME = "MovieAppDownloads";
    private static final String KEY_DOWNLOADS = "downloads_map";
    private static DownloadTracker instance;

    private final SharedPreferences prefs;
    private final Gson gson;
    private final Map<String, DownloadItem> downloadsMap;

    private DownloadTracker(Context context) {
        this.prefs = context.getApplicationContext().getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        this.gson = new Gson();
        this.downloadsMap = loadDownloadsMap();
    }

    public static synchronized DownloadTracker getInstance(Context context) {
        if (instance == null) {
            instance = new DownloadTracker(context);
        }
        return instance;
    }

    private Map<String, DownloadItem> loadDownloadsMap() {
        String json = prefs.getString(KEY_DOWNLOADS, null);
        if (json == null || json.isEmpty()) {
            return new HashMap<>();
        }
        Type type = new TypeToken<Map<String, DownloadItem>>() {}.getType();
        try {
            Map<String, DownloadItem> map = gson.fromJson(json, type);
            return map != null ? map : new HashMap<>();
        } catch (Exception e) {
            return new HashMap<>();
        }
    }

    private synchronized void saveDownloadsMap() {
        prefs.edit().putString(KEY_DOWNLOADS, gson.toJson(downloadsMap)).apply();
    }

    public synchronized long startDownload(Context context, String mediaKey, String title,
                                          String posterUrl, String streamUrl, String mediaType) {
        if (streamUrl == null || streamUrl.isEmpty()) {
            return -1;
        }

        File moviesDir = context.getExternalFilesDir(Environment.DIRECTORY_MOVIES);
        if (moviesDir != null && !moviesDir.exists()) {
            moviesDir.mkdirs();
        }

        // Create .nomedia file in the directory so gallery doesn't show video files
        if (moviesDir != null) {
            File noMedia = new File(moviesDir, ".nomedia");
            if (!noMedia.exists()) {
                try {
                    noMedia.createNewFile();
                } catch (IOException ignored) {}
            }
        }

        // Generate safe unique filename
        String ext = ".mp4";
        if (streamUrl.toLowerCase().contains(".mkv") || (title != null && title.toLowerCase().contains(".mkv"))) {
            ext = ".mkv";
        }
        String safeName = mediaKey.replaceAll("[^a-zA-Z0-9_-]", "_") + ext;
        File targetFile = new File(moviesDir, safeName);

        // Append session token and raw=1 if needed
        String finalUrl = streamUrl;
        SessionManager sessionManager = new SessionManager(context);
        String token = sessionManager.getSessionToken();
        if (token != null && !token.isEmpty()) {
            if (!finalUrl.contains("session_token=")) {
                finalUrl += (finalUrl.contains("?") ? "&" : "?") + "session_token=" + Uri.encode(token);
            }
        }
        if (!finalUrl.contains("raw=1")) {
            finalUrl += (finalUrl.contains("?") ? "&" : "?") + "raw=1";
        }

        long downloadId = System.currentTimeMillis();
        DownloadItem item = new DownloadItem(mediaKey, title, posterUrl, finalUrl,
                targetFile.getAbsolutePath(), mediaType, downloadId);
        item.setStatus(DownloadItem.STATUS_DOWNLOADING);
        downloadsMap.put(mediaKey, item);
        saveDownloadsMap();

        // Start Foreground Download Service with custom rich notification
        VideoDownloadService.startDownload(context, mediaKey, title, posterUrl, finalUrl,
                targetFile.getAbsolutePath(), mediaType);

        return downloadId;
    }

    public synchronized boolean isDownloaded(String mediaKey) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item == null) return false;
        if (DownloadItem.STATUS_COMPLETED.equals(item.getStatus())) {
            if (item.getLocalPath() != null) {
                File file = new File(item.getLocalPath());
                return file.exists() && file.length() > 0;
            }
        }
        return false;
    }

    public synchronized boolean isDownloading(Context context, String mediaKey) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item == null) return false;
        return DownloadItem.STATUS_DOWNLOADING.equals(item.getStatus());
    }

    public synchronized void deleteDownload(Context context, String mediaKey) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item != null) {
            VideoDownloadService.cancelDownload(context, mediaKey);

            if (item.getLocalPath() != null) {
                try {
                    File file = new File(item.getLocalPath());
                    if (file.exists()) file.delete();
                    File tempFile = new File(item.getLocalPath() + ".downloading");
                    if (tempFile.exists()) tempFile.delete();
                } catch (Exception ignored) {}
            }
            downloadsMap.remove(mediaKey);
            saveDownloadsMap();
        }
    }

    public synchronized void markCompleted(String mediaKey, String localPath, long bytes) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item != null) {
            item.setStatus(DownloadItem.STATUS_COMPLETED);
            item.setLocalPath(localPath);
            item.setFileSize(formatFileSize(bytes));
            saveDownloadsMap();
        }
    }

    public synchronized void markFailed(String mediaKey) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item != null) {
            item.setStatus(DownloadItem.STATUS_FAILED);
            saveDownloadsMap();
        }
    }

    public synchronized String getLocalFilePath(String mediaKey) {
        DownloadItem item = downloadsMap.get(mediaKey);
        if (item != null && item.getLocalPath() != null) {
            File f = new File(item.getLocalPath());
            if (f.exists() && f.length() > 0) {
                return item.getLocalPath();
            }
        }
        return null;
    }

    public synchronized List<DownloadItem> getAllDownloads() {
        List<DownloadItem> list = new ArrayList<>(downloadsMap.values());
        Collections.sort(list, (o1, o2) -> Long.compare(o2.getCreatedAt(), o1.getCreatedAt()));
        return list;
    }

    public synchronized DownloadItem getDownloadItem(String mediaKey) {
        return downloadsMap.get(mediaKey);
    }

    public static String formatFileSize(long bytes) {
        if (bytes <= 0) return "0 B";
        final String[] units = new String[]{"B", "KB", "MB", "GB", "TB"};
        int digitGroups = (int) (Math.log10(bytes) / Math.log10(1024));
        return new DecimalFormat("#,##0.#").format(bytes / Math.pow(1024, digitGroups)) + " " + units[digitGroups];
    }
}
