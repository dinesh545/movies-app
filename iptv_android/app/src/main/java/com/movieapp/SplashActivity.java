package com.movieapp;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.view.Window;
import android.view.WindowInsets;
import android.view.WindowInsetsController;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.card.MaterialCardView;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.SessionManager;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SplashActivity extends AppCompatActivity {

    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        enableImmersiveFullScreen();
        setContentView(R.layout.activity_splash);

        sessionManager = new SessionManager(this);

        MaterialCardView logoCard = findViewById(R.id.logoCard);
        if (logoCard != null) {
            logoCard.setAlpha(0f);
            logoCard.setScaleX(0.7f);
            logoCard.setScaleY(0.7f);
            logoCard.animate()
                    .alpha(1f)
                    .scaleX(1f)
                    .scaleY(1f)
                    .setDuration(1200)
                    .start();
        }

        SharedPreferences prefs = getSharedPreferences(MyApplication.CRASH_PREFS, android.content.Context.MODE_PRIVATE);
        String crashLog = prefs.getString(MyApplication.CRASH_LOG, null);
        if (crashLog != null) {
            prefs.edit().remove(MyApplication.CRASH_LOG).apply();
            new androidx.appcompat.app.AlertDialog.Builder(this)
                .setTitle("App Crashed Last Time")
                .setMessage(crashLog)
                .setPositiveButton("OK", (dialog, which) -> {
                    new Handler(Looper.getMainLooper()).postDelayed(this::checkAuthAndNavigate, 500);
                })
                .setCancelable(false)
                .show();
        } else {
            new Handler(Looper.getMainLooper()).postDelayed(this::checkAuthAndNavigate, 2000);
        }
    }

    private void checkAuthAndNavigate() {
        if (!sessionManager.isLoggedIn()) {
            navigateToLogin(null);
            return;
        }

        String token = sessionManager.getSessionToken();
        ApiService apiService = RetrofitClient.getApiService();
        apiService.checkSession(token).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse auth = response.body();
                    if ("success".equalsIgnoreCase(auth.getStatus()) || "valid".equalsIgnoreCase(auth.getStatus())) {
                        navigateToMain();
                    } else if ("session_expired".equalsIgnoreCase(auth.getStatus())) {
                        sessionManager.logout();
                        navigateToLogin("Logged out because your account was logged in on another device!");
                    } else {
                        navigateToLogin(auth.getMessage());
                    }
                } else {
                    navigateToMain(); // Fallback if server unreachable temporarily
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                navigateToMain(); // Offline / Fallback
            }
        });
    }

    private void navigateToLogin(String notice) {
        Intent intent = new Intent(SplashActivity.this, LoginActivity.class);
        if (notice != null && !notice.isEmpty()) {
            intent.putExtra("notice", notice);
        }
        startActivity(intent);
        finish();
    }

    private void navigateToMain() {
        Intent intent = new Intent(SplashActivity.this, MainActivity.class);
        startActivity(intent);
        finish();
    }

    private void enableImmersiveFullScreen() {
        Window window = getWindow();
        if (window == null) return;

        View decorView = window.getDecorView();
        if (decorView == null) return;

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            window.setDecorFitsSystemWindows(false);
            WindowInsetsController controller = decorView.getWindowInsetsController();
            if (controller != null) {
                controller.hide(WindowInsets.Type.statusBars() | WindowInsets.Type.navigationBars());
                controller.setSystemBarsBehavior(WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
            }
        } else {
            @SuppressWarnings("deprecation")
            int flags = View.SYSTEM_UI_FLAG_FULLSCREEN
                    | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                    | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                    | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                    | View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                    | View.SYSTEM_UI_FLAG_LAYOUT_STABLE;
            decorView.setSystemUiVisibility(flags);
        }
    }
}
