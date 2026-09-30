package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class Episode implements Serializable {
    @SerializedName("id")
    private int id;

    @SerializedName("season_id")
    private int seasonId;

    @SerializedName("episode_number")
    private int episodeNumber;

    @SerializedName("title")
    private String title;

    @SerializedName("description")
    private String description;

    @SerializedName("stream_url")
    private String streamUrl;

    @SerializedName("duration")
    private String duration;

    public int getId() { return id; }
    public int getSeasonId() { return seasonId; }
    public int getEpisodeNumber() { return episodeNumber; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getStreamUrl() { return streamUrl; }
    public String getDuration() { return duration; }
}
