package com.movieapp.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class MovieResponse {
    @SerializedName("status")
    private String status;

    @SerializedName("count")
    private int count;

    @SerializedName("data")
    private List<Movie> data;

    public String getStatus() {
        return status;
    }

    public int getCount() {
        return count;
    }

    public List<Movie> getData() {
        return data;
    }
}
