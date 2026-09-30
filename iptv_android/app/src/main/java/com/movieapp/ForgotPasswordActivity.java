package com.movieapp;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.button.MaterialButton;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ForgotPasswordActivity extends AppCompatActivity {

    private EditText etEmail, etOtp, etNewPassword;
    private LinearLayout layoutResetFields;
    private ProgressBar progressBar;
    private MaterialButton btnSubmit;
    private boolean isOtpSent = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_forgot_password);

        etEmail = findViewById(R.id.etForgotEmail);
        etOtp = findViewById(R.id.etResetOtp);
        etNewPassword = findViewById(R.id.etNewPassword);
        layoutResetFields = findViewById(R.id.layoutResetFields);
        progressBar = findViewById(R.id.progressBarForgot);
        btnSubmit = findViewById(R.id.btnSubmitForgot);

        findViewById(R.id.tvBackToLogin).setOnClickListener(v -> finish());

        btnSubmit.setOnClickListener(v -> {
            if (!isOtpSent) {
                requestResetOtp();
            } else {
                submitNewPassword();
            }
        });
    }

    private void requestResetOtp() {
        String email = etEmail.getText().toString().trim();
        if (TextUtils.isEmpty(email)) {
            Toast.makeText(this, "Please enter your registered email address.", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnSubmit.setEnabled(false);

        ApiService apiService = RetrofitClient.getApiService();
        apiService.forgotPassword(email).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse auth = response.body();
                    if ("success".equalsIgnoreCase(auth.getStatus())) {
                        isOtpSent = true;
                        layoutResetFields.setVisibility(View.VISIBLE);
                        btnSubmit.setText("Reset Password & Login");
                        Toast.makeText(ForgotPasswordActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                    } else {
                        Toast.makeText(ForgotPasswordActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    Toast.makeText(ForgotPasswordActivity.this, "Request failed. Please try again.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);
                Toast.makeText(ForgotPasswordActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void submitNewPassword() {
        String email = etEmail.getText().toString().trim();
        String otp = etOtp.getText().toString().trim();
        String newPassword = etNewPassword.getText().toString().trim();

        if (TextUtils.isEmpty(otp) || otp.length() != 6 || TextUtils.isEmpty(newPassword) || newPassword.length() < 6) {
            Toast.makeText(this, "Please enter the 6-digit OTP code and a new password (min 6 chars).", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnSubmit.setEnabled(false);

        ApiService apiService = RetrofitClient.getApiService();
        apiService.resetPassword(email, otp, newPassword).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse auth = response.body();
                    if ("success".equalsIgnoreCase(auth.getStatus())) {
                        Toast.makeText(ForgotPasswordActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                        Intent intent = new Intent(ForgotPasswordActivity.this, LoginActivity.class);
                        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                        startActivity(intent);
                        finish();
                    } else {
                        Toast.makeText(ForgotPasswordActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    Toast.makeText(ForgotPasswordActivity.this, "Password reset failed.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);
                Toast.makeText(ForgotPasswordActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}
