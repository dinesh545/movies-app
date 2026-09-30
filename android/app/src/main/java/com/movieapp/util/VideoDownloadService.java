package com.movieapp.util;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.pm.ServiceInfo;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Build;
import android.os.IBinder;
import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;
import com.movieapp.PlayerActivity;
import com.movieapp.R;
import com.movieapp.network.RetrofitClient;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.Call;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import okhttp3.ResponseBody;

public class VideoDownloadService extends Service {

    public static final String ACTION_START_DOWNLOAD = "com.movieapp.action.START_DOWNLOAD";
    public static final String ACTION_CANCEL_DOWNLOAD = "com.movieapp.action.CANCEL_DOWNLOAD";

    public static final String EXTRA_MEDIA_KEY = "media_key";
    public static final String EXTRA_TITLE = "title";
    public static final String EXTRA_POSTER_URL = "poster_url";
    public static final String EXTRA_STREAM_URL = "stream_url";
    public static final String EXTRA_TARGET_PATH = "target_path";
    public static final String EXTRA_MEDIA_TYPE = "media_type";

    private static final String CHANNEL_ID = "soni_cinemas_downloads_v2";
    private static final String CHANNEL_NAME = "Video Downloads";
    private static final int FOREGROUND_SERVICE_ID = 2026;

    private NotificationManager notificationManager;
    private final ExecutorService executorService = Executors.newFixedThreadPool(2);
    private final Map<String, Call> activeCalls = new ConcurrentHashMap<>();
    private final Map<String, Boolean> cancelledFlags = new ConcurrentHashMap<>();
    private final Map<String, Integer> activeNotifIds = new ConcurrentHashMap<>();

    public static void startDownload(Context context, String mediaKey, String title,
                                    String posterUrl, String streamUrl, String targetPath, String mediaType) {
        Intent intent = new Intent(context, VideoDownloadService.class);
        intent.setAction(ACTION_START_DOWNLOAD);
        intent.putExtra(EXTRA_MEDIA_KEY, mediaKey);
        intent.putExtra(EXTRA_TITLE, title);
        intent.putExtra(EXTRA_POSTER_URL, posterUrl);
        intent.putExtra(EXTRA_STREAM_URL, streamUrl);
        intent.putExtra(EXTRA_TARGET_PATH, targetPath);
        intent.putExtra(EXTRA_MEDIA_TYPE, mediaType);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            context.startForegroundService(intent);
        } else {
            context.startService(intent);
        }
    }

    public static void cancelDownload(Context context, String mediaKey) {
        Intent intent = new Intent(context, VideoDownloadService.class);
        intent.setAction(ACTION_CANCEL_DOWNLOAD);
        intent.putExtra(EXTRA_MEDIA_KEY, mediaKey);
        context.startService(intent);
    }

    @Override
    public void onCreate() {
        super.onCreate();
        notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
        createNotificationChannel();
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                    CHANNEL_ID,
                    CHANNEL_NAME,
                    NotificationManager.IMPORTANCE_DEFAULT
            );
            channel.setDescription("Progress and completion notifications for offline video downloads");
            channel.enableVibration(false);
            channel.setShowBadge(true);
            if (notificationManager != null) {
                notificationManager.createNotificationChannel(channel);
            }
        }
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        createNotificationChannel();

        // Immediately start in foreground with a valid notification
        NotificationCompat.Builder initialBuilder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.stat_sys_download)
                .setContentTitle("Soni Cinemas")
                .setContentText("Downloading media for offline viewing...")
                .setPriority(NotificationCompat.PRIORITY_DEFAULT)
                .setOngoing(true);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.UPSIDE_DOWN_CAKE) {
            startForeground(FOREGROUND_SERVICE_ID, initialBuilder.build(), ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC);
        } else {
            startForeground(FOREGROUND_SERVICE_ID, initialBuilder.build());
        }

        if (intent != null) {
            String action = intent.getAction();
            String mediaKey = intent.getStringExtra(EXTRA_MEDIA_KEY);

            if (ACTION_START_DOWNLOAD.equals(action) && mediaKey != null) {
                String title = intent.getStringExtra(EXTRA_TITLE);
                String posterUrl = intent.getStringExtra(EXTRA_POSTER_URL);
                String streamUrl = intent.getStringExtra(EXTRA_STREAM_URL);
                String targetPath = intent.getStringExtra(EXTRA_TARGET_PATH);
                String mediaType = intent.getStringExtra(EXTRA_MEDIA_TYPE);

                startDownloadTask(mediaKey, title, posterUrl, streamUrl, targetPath, mediaType);
            } else if (ACTION_CANCEL_DOWNLOAD.equals(action) && mediaKey != null) {
                cancelDownloadTask(mediaKey);
            }
        }
        return START_NOT_STICKY;
    }

    private void startDownloadTask(String mediaKey, String title, String posterUrl,
                                   String streamUrl, String targetPath, String mediaType) {
        cancelledFlags.put(mediaKey, false);
        int notifId = getNotificationId(mediaKey);
        activeNotifIds.put(mediaKey, notifId);

        NotificationCompat.Builder initialBuilder = buildProgressNotification(
                notifId, mediaKey, title, 0, 0, 0
        );
        notificationManager.notify(notifId, initialBuilder.build());

        executorService.submit(() -> performDownload(mediaKey, title, posterUrl, streamUrl, targetPath, mediaType, notifId));
    }

    private void performDownload(String mediaKey, String title, String posterUrl,
                                String streamUrl, String targetPath, String mediaType, int notifId) {
        File finalFile = new File(targetPath);
        File tempFile = new File(targetPath + ".downloading");

        OkHttpClient client = RetrofitClient.getDownloadOkHttpClient();

        Request request = new Request.Builder()
                .url(streamUrl)
                .addHeader("User-Agent", "SoniCinemasAndroidApp")
                .build();

        Call call = client.newCall(request);
        activeCalls.put(mediaKey, call);

        long lastUpdateTime = 0;

        try {
            Response response = call.execute();
            if (!response.isSuccessful() || response.body() == null) {
                throw new Exception("HTTP error " + response.code() + ": " + response.message());
            }

            ResponseBody body = response.body();
            long contentLength = body.contentLength();

            if (tempFile.exists()) {
                tempFile.delete();
            }

            try (InputStream in = body.byteStream();
                 FileOutputStream out = new FileOutputStream(tempFile)) {

                byte[] buffer = new byte[16 * 1024]; // 16KB buffer
                long totalBytesRead = 0;
                int read;

                while ((read = in.read(buffer)) != -1) {
                    if (Boolean.TRUE.equals(cancelledFlags.get(mediaKey))) {
                        throw new InterruptedException("Cancelled by user");
                    }

                    out.write(buffer, 0, read);
                    totalBytesRead += read;

                    long now = System.currentTimeMillis();
                    if (now - lastUpdateTime > 600) { // update every 600ms
                        lastUpdateTime = now;
                        int percent = (contentLength > 0) ? (int) ((totalBytesRead * 100) / contentLength) : 0;
                        NotificationCompat.Builder notif = buildProgressNotification(
                                notifId, mediaKey, title, percent, totalBytesRead, contentLength
                        );
                        notificationManager.notify(notifId, notif.build());
                    }
                }
                out.flush();
            }

            // Success! Rename temp file to final destination
            if (finalFile.exists()) {
                finalFile.delete();
            }
            if (!tempFile.renameTo(finalFile)) {
                tempFile.renameTo(finalFile);
            }

            // Update DownloadTracker
            DownloadTracker tracker = DownloadTracker.getInstance(this);
            tracker.markCompleted(mediaKey, finalFile.getAbsolutePath(), finalFile.length());

            // Broadcast UI refresh
            sendDownloadBroadcast();

            // Post Download Completed Notification
            postCompleteNotification(notifId, title, finalFile.getAbsolutePath());

        } catch (InterruptedException e) {
            if (tempFile.exists()) tempFile.delete();
            notificationManager.cancel(notifId);
            DownloadTracker.getInstance(this).deleteDownload(this, mediaKey);
            sendDownloadBroadcast();
        } catch (Exception e) {
            if (Boolean.TRUE.equals(cancelledFlags.get(mediaKey))) {
                if (tempFile.exists()) tempFile.delete();
                notificationManager.cancel(notifId);
            } else {
                if (tempFile.exists()) tempFile.delete();
                postFailedNotification(notifId, title, e.getMessage());
                DownloadTracker.getInstance(this).markFailed(mediaKey);
                sendDownloadBroadcast();
            }
        } finally {
            activeCalls.remove(mediaKey);
            cancelledFlags.remove(mediaKey);
            activeNotifIds.remove(mediaKey);

            if (activeCalls.isEmpty()) {
                stopForeground(false);
                stopSelf();
            }
        }
    }

    private void cancelDownloadTask(String mediaKey) {
        cancelledFlags.put(mediaKey, true);
        Call call = activeCalls.get(mediaKey);
        if (call != null) {
            call.cancel();
        }
        int notifId = getNotificationId(mediaKey);
        notificationManager.cancel(notifId);
        DownloadTracker.getInstance(this).deleteDownload(this, mediaKey);
        sendDownloadBroadcast();
    }

    private NotificationCompat.Builder buildProgressNotification(int notifId, String mediaKey,
                                                                 String title, int percent,
                                                                 long bytesRead, long totalBytes) {
        Intent cancelIntent = new Intent(this, VideoDownloadService.class);
        cancelIntent.setAction(ACTION_CANCEL_DOWNLOAD);
        cancelIntent.putExtra(EXTRA_MEDIA_KEY, mediaKey);

        int flags = Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                ? (PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE)
                : PendingIntent.FLAG_UPDATE_CURRENT;

        PendingIntent cancelPendingIntent = PendingIntent.getService(
                this, notifId, cancelIntent, flags
        );

        String progressInfo;
        if (totalBytes > 0) {
            progressInfo = percent + "% • " + DownloadTracker.formatFileSize(bytesRead) + " / " + DownloadTracker.formatFileSize(totalBytes);
        } else if (bytesRead > 0) {
            progressInfo = DownloadTracker.formatFileSize(bytesRead) + " downloaded";
        } else {
            progressInfo = "Starting download...";
        }

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.stat_sys_download)
                .setContentTitle(title != null ? title : "Downloading Video")
                .setContentText(progressInfo)
                .setSubText("Soni Cinemas")
                .setProgress(100, percent, totalBytes <= 0)
                .setOngoing(true)
                .setOnlyAlertOnce(true)
                .setPriority(NotificationCompat.PRIORITY_DEFAULT)
                .addAction(android.R.drawable.ic_menu_close_clear_cancel, "Cancel", cancelPendingIntent);

        try {
            builder.setLargeIcon(BitmapFactory.decodeResource(getResources(), R.mipmap.ic_launcher));
        } catch (Exception ignored) {}

        return builder;
    }

    private void postCompleteNotification(int notifId, String title, String localPath) {
        Intent playIntent = new Intent(this, PlayerActivity.class);
        playIntent.putExtra("VIDEO_URL", localPath);
        playIntent.putExtra("VIDEO_TITLE", title);
        playIntent.putExtra("IS_OFFLINE", true);

        int flags = Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                ? (PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE)
                : PendingIntent.FLAG_UPDATE_CURRENT;

        PendingIntent playPendingIntent = PendingIntent.getActivity(
                this, (int) System.currentTimeMillis(), playIntent, flags
        );

        NotificationCompat.Builder doneBuilder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.stat_sys_download_done)
                .setContentTitle("Download Complete 🎉")
                .setContentText((title != null ? title : "Video") + " • Tap to play offline")
                .setSubText("Soni Cinemas")
                .setContentIntent(playPendingIntent)
                .setAutoCancel(true)
                .setOngoing(false)
                .setPriority(NotificationCompat.PRIORITY_HIGH);

        try {
            doneBuilder.setLargeIcon(BitmapFactory.decodeResource(getResources(), R.mipmap.ic_launcher));
        } catch (Exception ignored) {}

        notificationManager.notify(notifId, doneBuilder.build());
    }

    private void postFailedNotification(int notifId, String title, String reason) {
        NotificationCompat.Builder failBuilder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.stat_sys_download_done)
                .setContentTitle("Download Failed")
                .setContentText((title != null ? title : "Video") + (reason != null ? ": " + reason : ""))
                .setSubText("Soni Cinemas")
                .setAutoCancel(true)
                .setOngoing(false)
                .setPriority(NotificationCompat.PRIORITY_DEFAULT);

        notificationManager.notify(notifId, failBuilder.build());
    }

    private void sendDownloadBroadcast() {
        Intent updateIntent = new Intent(DownloadReceiver.ACTION_DOWNLOAD_UPDATED);
        updateIntent.setPackage(getPackageName());
        sendBroadcast(updateIntent);
    }

    private int getNotificationId(String mediaKey) {
        return Math.abs(mediaKey.hashCode());
    }

    @Nullable
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
