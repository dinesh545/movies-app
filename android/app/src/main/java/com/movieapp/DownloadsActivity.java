package com.movieapp;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.movieapp.adapter.DownloadsAdapter;
import com.movieapp.model.DownloadItem;
import com.movieapp.util.DownloadReceiver;
import com.movieapp.util.DownloadTracker;
import java.io.File;
import java.util.ArrayList;
import java.util.List;

public class DownloadsActivity extends AppCompatActivity implements DownloadsAdapter.OnDownloadActionListener {

    private RecyclerView recyclerView;
    private View emptyView;
    private TextView tvStorageInfo;
    private DownloadsAdapter adapter;
    private final List<DownloadItem> downloadList = new ArrayList<>();

    private final BroadcastReceiver updateReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            loadDownloads();
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_downloads);

        Toolbar toolbar = findViewById(R.id.toolbarDownloads);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        recyclerView = findViewById(R.id.recyclerViewDownloads);
        emptyView = findViewById(R.id.emptyDownloadsView);
        tvStorageInfo = findViewById(R.id.tvStorageInfo);

        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new DownloadsAdapter(downloadList, this);
        recyclerView.setAdapter(adapter);

        loadDownloads();
    }

    @Override
    protected void onResume() {
        super.onResume();
        loadDownloads();
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

    private void loadDownloads() {
        DownloadTracker tracker = DownloadTracker.getInstance(this);
        List<DownloadItem> all = tracker.getAllDownloads();

        downloadList.clear();
        long totalBytes = 0;
        int completedCount = 0;

        for (DownloadItem item : all) {
            downloadList.add(item);
            if (item.getLocalPath() != null) {
                File f = new File(item.getLocalPath());
                if (f.exists()) {
                    totalBytes += f.length();
                    if (DownloadItem.STATUS_COMPLETED.equals(item.getStatus())) {
                        completedCount++;
                    }
                }
            }
        }

        adapter.notifyDataSetChanged();

        if (downloadList.isEmpty()) {
            emptyView.setVisibility(View.VISIBLE);
            recyclerView.setVisibility(View.GONE);
            tvStorageInfo.setText("Downloaded Videos • 0 items");
        } else {
            emptyView.setVisibility(View.GONE);
            recyclerView.setVisibility(View.VISIBLE);
            tvStorageInfo.setText("Downloaded Videos • " + completedCount + " ready (" + DownloadTracker.formatFileSize(totalBytes) + ")");
        }
    }

    @Override
    public void onPlay(DownloadItem item) {
        String localPath = item.getLocalPath();
        if (localPath == null || !new File(localPath).exists()) {
            Toast.makeText(this, "Downloaded file not found on device.", Toast.LENGTH_SHORT).show();
            return;
        }

        Intent intent = new Intent(this, PlayerActivity.class);
        intent.putExtra("VIDEO_URL", localPath);
        intent.putExtra("VIDEO_TITLE", item.getTitle());
        intent.putExtra("IS_OFFLINE", true);
        startActivity(intent);
    }

    @Override
    public void onDelete(DownloadItem item) {
        new AlertDialog.Builder(this)
                .setTitle("Delete Video")
                .setMessage("Delete \"" + item.getTitle() + "\" from offline storage?")
                .setPositiveButton("Delete", (dialog, which) -> {
                    DownloadTracker.getInstance(this).deleteDownload(this, item.getMediaKey());
                    Toast.makeText(this, "Deleted from offline downloads", Toast.LENGTH_SHORT).show();
                    loadDownloads();
                })
                .setNegativeButton("Cancel", null)
                .show();
    }
}
