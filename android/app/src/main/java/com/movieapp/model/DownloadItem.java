package com.movieapp.model;

import java.io.Serializable;

public class DownloadItem implements Serializable {
    public static final String STATUS_DOWNLOADING = "DOWNLOADING";
    public static final String STATUS_COMPLETED = "COMPLETED";
    public static final String STATUS_FAILED = "FAILED";

    private String mediaKey;
    private String title;
    private String posterUrl;
    private String streamUrl;
    private String localPath;
    private String mediaType; // "movie" or "series_episode"
    private String fileSize;
    private long downloadId;
    private String status;
    private long createdAt;

    public DownloadItem() {}

    public DownloadItem(String mediaKey, String title, String posterUrl, String streamUrl,
                        String localPath, String mediaType, long downloadId) {
        this.mediaKey = mediaKey;
        this.title = title;
        this.posterUrl = posterUrl;
        this.streamUrl = streamUrl;
        this.localPath = localPath;
        this.mediaType = mediaType;
        this.downloadId = downloadId;
        this.status = STATUS_DOWNLOADING;
        this.createdAt = System.currentTimeMillis();
    }

    public String getMediaKey() {
        return mediaKey;
    }

    public void setMediaKey(String mediaKey) {
        this.mediaKey = mediaKey;
    }

    public String getTitle() {
        return title;
    }

    public void setTitle(String title) {
        this.title = title;
    }

    public String getPosterUrl() {
        return posterUrl;
    }

    public void setPosterUrl(String posterUrl) {
        this.posterUrl = posterUrl;
    }

    public String getStreamUrl() {
        return streamUrl;
    }

    public void setStreamUrl(String streamUrl) {
        this.streamUrl = streamUrl;
    }

    public String getLocalPath() {
        return localPath;
    }

    public void setLocalPath(String localPath) {
        this.localPath = localPath;
    }

    public String getMediaType() {
        return mediaType;
    }

    public void setMediaType(String mediaType) {
        this.mediaType = mediaType;
    }

    public String getFileSize() {
        return fileSize;
    }

    public void setFileSize(String fileSize) {
        this.fileSize = fileSize;
    }

    public long getDownloadId() {
        return downloadId;
    }

    public void setDownloadId(long downloadId) {
        this.downloadId = downloadId;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public long getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(long createdAt) {
        this.createdAt = createdAt;
    }
}
