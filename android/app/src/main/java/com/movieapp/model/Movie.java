package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class Movie implements Serializable {
    @SerializedName("name")
    private String name;

    @SerializedName("path")
    private String path;

    @SerializedName("size")
    private long size;

    @SerializedName("formatted_size")
    private String formattedSize;

    @SerializedName("last_modified")
    private String lastModified;

    @SerializedName("stream_url")
    private String streamUrl;

    public String getName() {
        return name;
    }

    public String getPath() {
        return path;
    }

    public long getSize() {
        return size;
    }

    public String getFormattedSize() {
        return formattedSize;
    }

    public String getLastModified() {
        return lastModified;
    }

    public String getStreamUrl() {
        return streamUrl;
    }
}
