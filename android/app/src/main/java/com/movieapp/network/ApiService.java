package com.movieapp.network;

import com.movieapp.model.AuthResponse;
import com.movieapp.model.MediaResponse;
import com.movieapp.model.SeriesDetailResponse;
import retrofit2.Call;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.Query;

public interface ApiService {
    @GET("api/movies.php")
    Call<MediaResponse> getMovies();

    @GET("api/series.php")
    Call<MediaResponse> getSeriesList();

    @GET("api/series.php")
    Call<SeriesDetailResponse> getSeriesDetail(@Query("id") int seriesId);

    // USER AUTH ENDPOINTS
    @FormUrlEncoded
    @POST("api/user_login.php")
    Call<AuthResponse> loginUser(
        @Field("login_id") String loginId,
        @Field("password") String password
    );

    @FormUrlEncoded
    @POST("api/user_register.php")
    Call<AuthResponse> registerUser(
        @Field("name") String name,
        @Field("mobile") String mobile,
        @Field("email") String email,
        @Field("password") String password
    );

    @FormUrlEncoded
    @POST("api/user_verify_email.php")
    Call<AuthResponse> verifyEmail(
        @Field("email") String email,
        @Field("otp") String otp
    );

    @FormUrlEncoded
    @POST("api/user_resend_otp.php")
    Call<AuthResponse> resendOtp(
        @Field("email") String email
    );

    @FormUrlEncoded
    @POST("api/user_forgot_password.php")
    Call<AuthResponse> forgotPassword(
        @Field("email") String email
    );

    @FormUrlEncoded
    @POST("api/user_reset_password.php")
    Call<AuthResponse> resetPassword(
        @Field("email") String email,
        @Field("otp") String otp,
        @Field("new_password") String newPassword
    );

    @GET("api/user_session_check.php")
    Call<AuthResponse> checkSession(
        @Query("session_token") String sessionToken
    );

    @FormUrlEncoded
    @POST("api/submit_suggestion.php")
    Call<AuthResponse> submitSuggestion(
        @Field("category") String category,
        @Field("suggestion_text") String suggestionText,
        @Field("name") String name,
        @Field("email") String email,
        @Field("session_token") String sessionToken
    );
}
