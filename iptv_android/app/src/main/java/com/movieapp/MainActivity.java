package com.movieapp;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Build;
import android.os.Bundle;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.google.android.material.button.MaterialButtonToggleGroup;
import com.movieapp.adapter.MediaAdapter;
import com.movieapp.model.MediaItem;
import com.movieapp.model.MediaResponse;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.DownloadReceiver;
import com.movieapp.util.DownloadTracker;
import com.movieapp.util.SessionManager;
import java.io.File;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MainActivity extends AppCompatActivity implements MediaAdapter.OnMediaClickListener {

    private static final int TAB_ALL = 0;
    private static final int TAB_MOVIES = 1;
    private static final int TAB_SERIES = 2;

    private RecyclerView recyclerView;
    private ProgressBar progressBar;
    private TextView emptyView;
    private MaterialButtonToggleGroup toggleGroup;
    private SessionManager sessionManager;

    private MediaAdapter adapter;
    private final List<MediaItem> allMediaList = new ArrayList<>();
    private final List<MediaItem> displayedList = new ArrayList<>();
    private int currentTab = TAB_ALL;

    private final BroadcastReceiver updateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if (adapter != null) {
                adapter.notifyDataSetChanged();
            }
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        sessionManager = new SessionManager(this);

        // Request runtime notification permission on Android 13+ (API 33+)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) != android.content.pm.PackageManager.PERMISSION_GRANTED) {
                requestPermissions(new String[]{android.Manifest.permission.POST_NOTIFICATIONS}, 1010);
            }
        }

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        recyclerView = findViewById(R.id.recyclerViewMedia);
        progressBar = findViewById(R.id.progressBar);
        emptyView = findViewById(R.id.emptyView);
        toggleGroup = findViewById(R.id.toggleGroup);

        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new MediaAdapter(displayedList, this);
        recyclerView.setAdapter(adapter);

        toggleGroup.check(R.id.btnTabAll);
        toggleGroup.addOnButtonCheckedListener((group, checkedId, isChecked) -> {
            if (isChecked) {
                if (checkedId == R.id.btnTabAll) {
                    currentTab = TAB_ALL;
                } else if (checkedId == R.id.btnTabMovies) {
                    currentTab = TAB_MOVIES;
                } else if (checkedId == R.id.btnTabSeries) {
                    currentTab = TAB_SERIES;
                }
                applyTabFilter();
            }
        });

        loadContent();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (adapter != null) {
            adapter.notifyDataSetChanged();
        }
        IntentFilter filter = new IntentFilter(DownloadReceiver.ACTION_DOWNLOAD_UPDATED);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(updateReceiver, filter, Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(updateReceiver, filter);
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        try {
            unregisterReceiver(updateReceiver);
        } catch (Exception ignored) {}
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        menu.add(0, 102, 0, "📥 Downloads")
                .setShowAsAction(MenuItem.SHOW_AS_ACTION_ALWAYS);
        menu.add(0, 100, 1, "💡 Request")
                .setShowAsAction(MenuItem.SHOW_AS_ACTION_NEVER);
        menu.add(0, 101, 2, "Logout")
                .setShowAsAction(MenuItem.SHOW_AS_ACTION_NEVER);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == 102) {
            Intent intent = new Intent(this, DownloadsActivity.class);
            startActivity(intent);
            return true;
        } else if (item.getItemId() == 100) {
            Intent intent = new Intent(this, SuggestionActivity.class);
            startActivity(intent);
            return true;
        } else if (item.getItemId() == 101) {
            sessionManager.logout();
            Toast.makeText(this, "Logged out successfully", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(this, LoginActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
            startActivity(intent);
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    private void loadContent() {
        progressBar.setVisibility(View.VISIBLE);
        emptyView.setVisibility(View.GONE);

        RetrofitClient.getApiService().getMovies().enqueue(new Callback<MediaResponse>() {
            @Override
            public void onResponse(Call<MediaResponse> call, Response<MediaResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null && response.body().getData() != null) {
                    allMediaList.clear();
                    allMediaList.addAll(response.body().getData());
                    applyTabFilter();
                } else {
                    emptyView.setText("Failed to load content.");
                    emptyView.setVisibility(View.VISIBLE);
                }
            }

            @Override
            public void onFailure(Call<MediaResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                emptyView.setText("Network Error: " + t.getMessage());
                emptyView.setVisibility(View.VISIBLE);
            }
        });
    }

    private void applyTabFilter() {
        displayedList.clear();

        for (MediaItem item : allMediaList) {
            boolean isSeries = "series".equalsIgnoreCase(item.getType());
            if (currentTab == TAB_ALL) {
                displayedList.add(item);
            } else if (currentTab == TAB_MOVIES) {
                if (!isSeries) {
                    displayedList.add(item);
                }
            } else if (currentTab == TAB_SERIES) {
                if (isSeries) {
                    displayedList.add(item);
                }
            }
        }

        adapter.notifyDataSetChanged();

        if (displayedList.isEmpty()) {
            if (currentTab == TAB_MOVIES) {
                emptyView.setText("No movies added yet.");
            } else if (currentTab == TAB_SERIES) {
                emptyView.setText("No web series added yet.");
            } else {
                emptyView.setText("No content found.");
            }
            emptyView.setVisibility(View.VISIBLE);
        } else {
            emptyView.setVisibility(View.GONE);
        }
    }

    @Override
    public void onMediaClick(MediaItem item) {
        if ("series".equalsIgnoreCase(item.getType())) {
            Intent intent = new Intent(this, SeriesDetailActivity.class);
            intent.putExtra("SERIES_ITEM", item);
            startActivity(intent);
        } else {
            String mediaKey = "movie_" + item.getId();
            DownloadTracker tracker = DownloadTracker.getInstance(this);
            Intent intent = new Intent(this, PlayerActivity.class);

            if (tracker.isDownloaded(mediaKey)) {
                String localPath = tracker.getLocalFilePath(mediaKey);
                if (localPath != null && new File(localPath).exists()) {
                    intent.putExtra("VIDEO_URL", localPath);
                    intent.putExtra("IS_OFFLINE", true);
                    Toast.makeText(this, "Playing from offline storage 💾", Toast.LENGTH_SHORT).show();
                } else {
                    intent.putExtra("VIDEO_URL", item.getStreamUrl());
                }
            } else {
                intent.putExtra("VIDEO_URL", item.getStreamUrl());
            }

            intent.putExtra("VIDEO_TITLE", item.getTitle());
            startActivity(intent);
        }
    }

    @Override
    public boolean onKeyDown(int keyCode, android.view.KeyEvent event) {
        switch (keyCode) {
            case android.view.KeyEvent.KEYCODE_PROG_RED:
                if (toggleGroup != null) toggleGroup.check(R.id.btnTabAll);
                return true;
            case android.view.KeyEvent.KEYCODE_PROG_GREEN:
                if (toggleGroup != null) toggleGroup.check(R.id.btnTabMovies);
                return true;
            case android.view.KeyEvent.KEYCODE_PROG_YELLOW:
                if (toggleGroup != null) toggleGroup.check(R.id.btnTabSeries);
                return true;
            case android.view.KeyEvent.KEYCODE_PROG_BLUE:
                startActivity(new Intent(this, DownloadsActivity.class));
                return true;
            case android.view.KeyEvent.KEYCODE_MENU:
                openOptionsMenu();
                return true;
            case android.view.KeyEvent.KEYCODE_BACK:
            case android.view.KeyEvent.KEYCODE_ESCAPE:
                new androidx.appcompat.app.AlertDialog.Builder(this)
                        .setTitle("Exit Soni Cinemas")
                        .setMessage("Are you sure you want to exit?")
                        .setPositiveButton("Exit", (dialog, which) -> finish())
                        .setNegativeButton("Cancel", null)
                        .show();
                return true;
        }
        return super.onKeyDown(keyCode, event);
    }
}
