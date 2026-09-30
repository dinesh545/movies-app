package com.movieapp;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.button.MaterialButton;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class VerifyOtpActivity extends AppCompatActivity {

    private EditText etEmail, etOtpCode;
    private ProgressBar progressBar;
    private MaterialButton btnVerifyOtp, btnResendOtp;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_verify_otp);

        etEmail = findViewById(R.id.etVerifyEmail);
        etOtpCode = findViewById(R.id.etOtpCode);
        progressBar = findViewById(R.id.progressBarOtp);
        btnVerifyOtp = findViewById(R.id.btnVerifyOtp);
        btnResendOtp = findViewById(R.id.btnResendOtp);

        String emailParam = getIntent().getStringExtra("email");
        if (emailParam != null) {
            etEmail.setText(emailParam);
        }

        btnVerifyOtp.setOnClickListener(v -> attemptVerifyOtp());
        btnResendOtp.setOnClickListener(v -> attemptResendOtp());
    }

    private void attemptVerifyOtp() {
        String email = etEmail.getText().toString().trim();
        String otp = etOtpCode.getText().toString().trim();

        if (TextUtils.isEmpty(email) || TextUtils.isEmpty(otp) || otp.length() != 6) {
            Toast.makeText(this, "Please enter your email and full 6-digit OTP code.", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnVerifyOtp.setEnabled(false);

        ApiService apiService = RetrofitClient.getApiService();
        apiService.verifyEmail(email, otp).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnVerifyOtp.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse auth = response.body();
                    if ("success".equalsIgnoreCase(auth.getStatus())) {
                        Toast.makeText(VerifyOtpActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                        Intent intent = new Intent(VerifyOtpActivity.this, LoginActivity.class);
                        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                        startActivity(intent);
                        finish();
                    } else {
                        Toast.makeText(VerifyOtpActivity.this, auth.getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    Toast.makeText(VerifyOtpActivity.this, "Verification failed. Please try again.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnVerifyOtp.setEnabled(true);
                Toast.makeText(VerifyOtpActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void attemptResendOtp() {
        String email = etEmail.getText().toString().trim();
        if (TextUtils.isEmpty(email)) {
            Toast.makeText(this, "Please enter your registered email address first.", Toast.LENGTH_SHORT).show();
            return;
        }

        btnResendOtp.setEnabled(false);

        ApiService apiService = RetrofitClient.getApiService();
        apiService.resendOtp(email).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                btnResendOtp.setEnabled(true);
                if (response.isSuccessful() && response.body() != null) {
                    Toast.makeText(VerifyOtpActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(VerifyOtpActivity.this, "Failed to resend OTP.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                btnResendOtp.setEnabled(true);
                Toast.makeText(VerifyOtpActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}
