package com.movieapp;

import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.movieapp.model.AuthResponse;
import com.movieapp.network.ApiService;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.SessionManager;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SuggestionActivity extends AppCompatActivity {

    private Spinner spinnerCategory;
    private TextInputEditText etName, etContact, etSuggestionText;
    private MaterialButton btnSubmit;
    private ProgressBar progressBar;
    private ImageButton btnBack;
    private SessionManager sessionManager;

    private static final String[] CATEGORIES = new String[]{
        "🎬 Movie / Web Series Request",
        "🚀 App / Website Feature Improvement",
        "🐛 Technical Bug / Video Issue",
        "💬 General Feedback"
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_suggestion);

        sessionManager = new SessionManager(this);

        btnBack = findViewById(R.id.btnBack);
        spinnerCategory = findViewById(R.id.spinnerCategory);
        etName = findViewById(R.id.etName);
        etContact = findViewById(R.id.etContact);
        etSuggestionText = findViewById(R.id.etSuggestionText);
        btnSubmit = findViewById(R.id.btnSubmit);
        progressBar = findViewById(R.id.progressBar);

        btnBack.setOnClickListener(v -> finish());

        ArrayAdapter<String> adapter = new ArrayAdapter<>(
            this,
            android.R.layout.simple_spinner_item,
            CATEGORIES
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerCategory.setAdapter(adapter);

        if (sessionManager.isLoggedIn()) {
            etName.setText(sessionManager.getUserName());
            etContact.setText(sessionManager.getUserEmail());
        }

        btnSubmit.setOnClickListener(v -> submitSuggestion());
    }

    private void submitSuggestion() {
        String category = spinnerCategory.getSelectedItem() != null ? spinnerCategory.getSelectedItem().toString() : CATEGORIES[0];
        String name = etName.getText() != null ? etName.getText().toString().trim() : "";
        String contact = etContact.getText() != null ? etContact.getText().toString().trim() : "";
        String text = etSuggestionText.getText() != null ? etSuggestionText.getText().toString().trim() : "";

        if (TextUtils.isEmpty(text)) {
            Toast.makeText(this, "Please enter your suggestion or movie request.", Toast.LENGTH_SHORT).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        btnSubmit.setEnabled(false);

        String token = sessionManager.getSessionToken() != null ? sessionManager.getSessionToken() : "";

        ApiService api = RetrofitClient.getApiService();
        api.submitSuggestion(category, text, name, contact, token).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse res = response.body();
                    if ("success".equalsIgnoreCase(res.getStatus())) {
                        Toast.makeText(SuggestionActivity.this, res.getMessage(), Toast.LENGTH_LONG).show();
                        finish();
                    } else {
                        Toast.makeText(SuggestionActivity.this, res.getMessage() != null ? res.getMessage() : "Failed to submit request.", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Toast.makeText(SuggestionActivity.this, "Submission successful!", Toast.LENGTH_SHORT).show();
                    finish();
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnSubmit.setEnabled(true);
                Toast.makeText(SuggestionActivity.this, "Network error. Please try again.", Toast.LENGTH_SHORT).show();
            }
        });
    }
}
