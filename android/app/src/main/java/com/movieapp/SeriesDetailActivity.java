package com.movieapp;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.movieapp.adapter.EpisodeAdapter;
import com.movieapp.model.Episode;
import com.movieapp.model.MediaItem;
import com.movieapp.model.Season;
import com.movieapp.model.SeriesDetailResponse;
import com.movieapp.network.RetrofitClient;
import com.movieapp.util.DownloadReceiver;
import com.movieapp.util.DownloadTracker;
import java.io.File;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SeriesDetailActivity extends AppCompatActivity implements EpisodeAdapter.OnEpisodeClickListener {

    private ImageView imgSeriesPoster;
    private TextView tvSeriesTitle, tvSeriesMeta, tvSeriesDescription;
    private Spinner spinnerSeasons;
    private RecyclerView recyclerViewEpisodes;
    private ProgressBar progressBar;

    private final List<Season> seasonList = new ArrayList<>();
    private final List<Episode> episodeList = new ArrayList<>();
    private EpisodeAdapter episodeAdapter;
    private MediaItem seriesItem;

    private final BroadcastReceiver updateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if (episodeAdapter != null) {
                episodeAdapter.notifyDataSetChanged();
            }
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_series_detail);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) != android.content.pm.PackageManager.PERMISSION_GRANTED) {
                requestPermissions(new String[]{android.Manifest.permission.POST_NOTIFICATIONS}, 1010);
            }
        }

        imgSeriesPoster = findViewById(R.id.imgSeriesPoster);
        tvSeriesTitle = findViewById(R.id.tvSeriesTitle);
        tvSeriesMeta = findViewById(R.id.tvSeriesMeta);
        tvSeriesDescription = findViewById(R.id.tvSeriesDescription);
        spinnerSeasons = findViewById(R.id.spinnerSeasons);
        recyclerViewEpisodes = findViewById(R.id.recyclerViewEpisodes);
        progressBar = findViewById(R.id.progressBar);

        recyclerViewEpisodes.setLayoutManager(new LinearLayoutManager(this));
        episodeAdapter = new EpisodeAdapter(episodeList, this);
        recyclerViewEpisodes.setAdapter(episodeAdapter);

        seriesItem = (MediaItem) getIntent().getSerializableExtra("SERIES_ITEM");
        if (seriesItem != null) {
            setupHeader(seriesItem);
            loadSeriesDetails(seriesItem.getId());
        } else {
            finish();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (episodeAdapter != null) {
            episodeAdapter.notifyDataSetChanged();
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

    private void setupHeader(MediaItem item) {
        tvSeriesTitle.setText(item.getTitle());
        tvSeriesMeta.setText(item.getReleaseYear() + " | ⭐ " + item.getRating());
        tvSeriesDescription.setText(item.getDescription() != null ? item.getDescription() : "");

        episodeAdapter.setSeriesInfo(item.getTitle(), item.getPosterUrl());

        if (item.getPosterUrl() != null && !item.getPosterUrl().isEmpty()) {
            Glide.with(this).load(item.getPosterUrl()).into(imgSeriesPoster);
        }
    }

    private void loadSeriesDetails(int seriesId) {
        progressBar.setVisibility(View.VISIBLE);

        RetrofitClient.getApiService().getSeriesDetail(seriesId).enqueue(new Callback<SeriesDetailResponse>() {
            @Override
            public void onResponse(Call<SeriesDetailResponse> call, Response<SeriesDetailResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null && response.body().getData() != null) {
                    List<Season> seasons = response.body().getData().getSeasons();
                    if (seasons != null && !seasons.isEmpty()) {
                        seasonList.clear();
                        seasonList.addAll(seasons);
                        setupSeasonSpinner();
                    } else {
                        Toast.makeText(SeriesDetailActivity.this, "No seasons found for this web series", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Toast.makeText(SeriesDetailActivity.this, "Failed to load series details", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<SeriesDetailResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(SeriesDetailActivity.this, "Network Error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void setupSeasonSpinner() {
        ArrayAdapter<Season> adapter = new ArrayAdapter<>(this, R.layout.item_spinner_season, seasonList);
        adapter.setDropDownViewResource(R.layout.item_spinner_season_dropdown);
        spinnerSeasons.setAdapter(adapter);

        spinnerSeasons.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                Season selectedSeason = seasonList.get(position);
                episodeList.clear();
                if (selectedSeason.getEpisodes() != null) {
                    episodeList.addAll(selectedSeason.getEpisodes());
                }
                episodeAdapter.notifyDataSetChanged();
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    @Override
    public void onEpisodeClick(Episode episode) {
        String mediaKey = "ep_" + episode.getId();
        DownloadTracker tracker = DownloadTracker.getInstance(this);

        Intent intent = new Intent(this, PlayerActivity.class);
        String fullTitle = (seriesItem != null ? seriesItem.getTitle() + " - " : "")
                + "Ep " + episode.getEpisodeNumber() + ": " + episode.getTitle();

        if (tracker.isDownloaded(mediaKey)) {
            String localPath = tracker.getLocalFilePath(mediaKey);
            if (localPath != null && new File(localPath).exists()) {
                intent.putExtra("VIDEO_URL", localPath);
                intent.putExtra("IS_OFFLINE", true);
                Toast.makeText(this, "Playing from offline storage 💾", Toast.LENGTH_SHORT).show();
            } else {
                intent.putExtra("VIDEO_URL", episode.getStreamUrl());
            }
        } else {
            intent.putExtra("VIDEO_URL", episode.getStreamUrl());
        }

        intent.putExtra("VIDEO_TITLE", fullTitle);
        startActivity(intent);
    }
}
