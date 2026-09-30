package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class SeriesDetailResponse {
    @SerializedName("status")
    private String status;

    @SerializedName("data")
    private SeriesDetailData data;

    public String getStatus() { return status; }
    public SeriesDetailData getData() { return data; }

    public static class SeriesDetailData extends MediaItem {
        @SerializedName("seasons")
        private List<Season> seasons;

        public List<Season> getSeasons() { return seasons; }
    }
}
