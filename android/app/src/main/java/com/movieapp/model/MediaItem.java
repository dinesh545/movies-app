package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class MediaItem implements Serializable {
    @SerializedName("id")
    private int id;

    @SerializedName("type")
    private String type; // 'movie' or 'series'

    @SerializedName("title")
    private String title;

    @SerializedName("description")
    private String description;

    @SerializedName("poster_url")
    private String posterUrl;

    @SerializedName("release_year")
    private int releaseYear;

    @SerializedName("rating")
    private String rating;

    @SerializedName("stream_url")
    private String streamUrl;

    public int getId() { return id; }
    public String getType() { return type; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getPosterUrl() { return posterUrl; }
    public int getReleaseYear() { return releaseYear; }
    public String getRating() { return rating; }
    public String getStreamUrl() { return streamUrl; }
}
