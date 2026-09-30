package com.movieapp.model;

import com.google.gson.annotations.SerializedName;

public class AuthResponse {
    @SerializedName("status")
    private String status;

    @SerializedName("message")
    private String message;

    @SerializedName("session_token")
    private String sessionToken;

    @SerializedName("email")
    private String email;

    @SerializedName("user_id")
    private int userId;

    public String getStatus() { return status; }
    public String getMessage() { return message; }
    public String getSessionToken() { return sessionToken; }
    public String getEmail() { return email; }
    public int getUserId() { return userId; }
}
