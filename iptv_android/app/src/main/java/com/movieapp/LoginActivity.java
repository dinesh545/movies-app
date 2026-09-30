package com.movieapp;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.button.MaterialButton;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.SessionManager;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {

    private EditText etLoginId, etPassword;
    private ProgressBar progressBar;
    private MaterialButton btnLogin;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        sessionManager = new SessionManager(this);

        etLoginId = findViewById(R.id.etLoginId);
        etPassword = findViewById(R.id.etPassword);
        progressBar = findViewById(R.id.progressBarLogin);
        btnLogin = findViewById(R.id.btnLogin);

        findViewById(R.id.tvForgotPassword).setOnClickListener(v -> {
            startActivity(new Intent(LoginActivity.this, ForgotPasswordActivity.class));
        });

        findViewById(R.id.tvRegisterLink).setOnClickListener(v -> {
            startActivity(new Intent(LoginActivity.this, RegisterActivity.class));
        });

        btnLogin.setOnClickListener(v -> attemptLogin());

        // Display intent message if redirected (e.g., Session Expired)
        String notice = getIntent().getStringExtra("notice");
        if (notice != null && !notice.isEmpty()) {
            Toast.makeText(this, notice, Toast.LENGTH_LONG).show();
        }
    }

    private void attemptLogin() {
        String loginId = etLoginId.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        if (TextUtils.isEmpty(loginId) || TextUtils.isEmpty(password)) {
            Toast.makeText(this, "Please enter both Email/Mobile and Password.", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnLogin.setEnabled(false);

        ApiService apiService = RetrofitClient.getApiService();
        apiService.loginUser(loginId, password).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnLogin.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse auth = response.body();
                    if ("success".equalsIgnoreCase(auth.getStatus())) {
                        sessionManager.saveSession(
                                auth.getUserId(),
                                "",
                                auth.getEmail(),
                                "",
                                auth.getSessionToken()
                        );
                        Toast.makeText(LoginActivity.this, "Login Successful!", Toast.LENGTH_SHORT).show();
                        Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                        startActivity(intent);
                        finish();
                    } else if ("email_not_verified".equalsIgnoreCase(auth.getStatus())) {
                        Toast.makeText(LoginActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                        Intent intent = new Intent(LoginActivity.this, VerifyOtpActivity.class);
                        intent.putExtra("email", auth.getEmail() != null ? auth.getEmail() : loginId);
                        startActivity(intent);
                    } else if ("pending_approval".equalsIgnoreCase(auth.getStatus())) {
                        Toast.makeText(LoginActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                    } else {
                        Toast.makeText(LoginActivity.this, auth.getMessage() != null ? auth.getMessage() : "Login failed.", Toast.LENGTH_LONG).show();
                    }
                } else {
                    Toast.makeText(LoginActivity.this, "Server error. Please try again.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnLogin.setEnabled(true);
                Toast.makeText(LoginActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}
