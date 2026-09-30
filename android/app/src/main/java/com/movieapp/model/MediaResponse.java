package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class MediaResponse {
    @SerializedName("status")
    private String status;

    @SerializedName("count")
    private int count;

    @SerializedName("data")
    private List<MediaItem> data;

    public String getStatus() { return status; }
    public int getCount() { return count; }
    public List<MediaItem> getData() { return data; }
}
