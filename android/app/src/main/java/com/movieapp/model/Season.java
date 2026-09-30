package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;
import java.util.List;

public class Season implements Serializable {
    @SerializedName("id")
    private int id;

    @SerializedName("series_id")
    private int seriesId;

    @SerializedName("season_number")
    private int seasonNumber;

    @SerializedName("title")
    private String title;

    @SerializedName("episodes")
    private List<Episode> episodes;

    public int getId() { return id; }
    public int getSeriesId() { return seriesId; }
    public int getSeasonNumber() { return seasonNumber; }
    public String getTitle() { return title; }
    public List<Episode> getEpisodes() { return episodes; }

    @Override
    public String toString() {
        return title; // Displayed in Season Dropdown Spinner
    }
}
