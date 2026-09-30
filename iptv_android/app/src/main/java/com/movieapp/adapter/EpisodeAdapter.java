package com.movieapp.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.recyclerview.widget.RecyclerView;
import com.google.android.material.button.MaterialButton;
import com.movieapp.R;
import com.movieapp.model.Episode;
import com.movieapp.util.DownloadTracker;
import java.util.List;

public class EpisodeAdapter extends RecyclerView.Adapter<EpisodeAdapter.EpisodeViewHolder> {

    private final List<Episode> episodeList;
    private final OnEpisodeClickListener listener;
    private String seriesTitle = "Web Series";
    private String seriesPosterUrl = "";

    public interface OnEpisodeClickListener {
        void onEpisodeClick(Episode episode);
    }

    public EpisodeAdapter(List<Episode> episodeList, OnEpisodeClickListener listener) {
        this.episodeList = episodeList;
        this.listener = listener;
    }

    public void setSeriesInfo(String seriesTitle, String seriesPosterUrl) {
        this.seriesTitle = seriesTitle;
        this.seriesPosterUrl = seriesPosterUrl;
    }

    @NonNull
    @Override
    public EpisodeViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_episode, parent, false);
        return new EpisodeViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull EpisodeViewHolder holder, int position) {
        Episode episode = episodeList.get(position);
        Context ctx = holder.itemView.getContext();
        DownloadTracker tracker = DownloadTracker.getInstance(ctx);

        final String mediaKey = "ep_" + episode.getId();
        final String fullTitle = (seriesTitle != null && !seriesTitle.isEmpty() ? seriesTitle + " - " : "")
                + "Ep " + episode.getEpisodeNumber() + ": " + episode.getTitle();

        holder.tvEpTitle.setText("Ep " + episode.getEpisodeNumber() + ": " + episode.getTitle());
        holder.tvEpMeta.setText("Duration: " + (episode.getDuration() != null ? episode.getDuration() : "45m"));

        boolean isDownloaded = tracker.isDownloaded(mediaKey);
        boolean isDownloading = tracker.isDownloading(ctx, mediaKey);

        if (isDownloaded) {
            holder.btnPlayEp.setText("PLAY 💾");
            holder.btnEpDownload.setImageResource(R.drawable.ic_delete);
            holder.btnEpDownload.setColorFilter(0xFFEF4444);
            holder.btnEpDownload.setOnClickListener(v -> {
                new AlertDialog.Builder(ctx)
                        .setTitle("Delete Episode")
                        .setMessage("Delete \"" + fullTitle + "\" from offline storage?")
                        .setPositiveButton("Delete", (dialog, which) -> {
                            tracker.deleteDownload(ctx, mediaKey);
                            Toast.makeText(ctx, "Deleted from offline storage", Toast.LENGTH_SHORT).show();
                            notifyItemChanged(holder.getAdapterPosition());
                        })
                        .setNegativeButton("Cancel", null)
                        .show();
            });
        } else if (isDownloading) {
            holder.btnPlayEp.setText("PLAY");
            holder.btnEpDownload.setImageResource(R.drawable.ic_download);
            holder.btnEpDownload.setColorFilter(0xFF94A3B8);
            holder.btnEpDownload.setOnClickListener(v -> {
                Toast.makeText(ctx, "Download is in progress...", Toast.LENGTH_SHORT).show();
            });
        } else {
            holder.btnPlayEp.setText("PLAY");
            holder.btnEpDownload.setImageResource(R.drawable.ic_download);
            holder.btnEpDownload.setColorFilter(0xFF38BDF8);
            holder.btnEpDownload.setOnClickListener(v -> {
                long id = tracker.startDownload(ctx, mediaKey, fullTitle, seriesPosterUrl, episode.getStreamUrl(), "series_episode");
                if (id != -1) {
                    Toast.makeText(ctx, "Downloading " + fullTitle + " ⬇️", Toast.LENGTH_SHORT).show();
                    notifyItemChanged(holder.getAdapterPosition());
                } else {
                    Toast.makeText(ctx, "Download failed to start.", Toast.LENGTH_SHORT).show();
                }
            });
        }

        holder.btnPlayEp.setOnClickListener(v -> listener.onEpisodeClick(episode));
        holder.itemView.setOnClickListener(v -> listener.onEpisodeClick(episode));

        holder.itemView.setOnFocusChangeListener((v, hasFocus) -> {
            float density = v.getResources().getDisplayMetrics().density;
            if (hasFocus) {
                v.animate().scaleX(1.03f).scaleY(1.03f).setDuration(120).start();
                if (v instanceof com.google.android.material.card.MaterialCardView) {
                    com.google.android.material.card.MaterialCardView card = (com.google.android.material.card.MaterialCardView) v;
                    card.setStrokeColor(android.content.res.ColorStateList.valueOf(0xFFF59E0B));
                    card.setStrokeWidth((int) (3 * density));
                    card.setCardElevation(12 * density);
                }
            } else {
                v.animate().scaleX(1.0f).scaleY(1.0f).setDuration(120).start();
                if (v instanceof com.google.android.material.card.MaterialCardView) {
                    com.google.android.material.card.MaterialCardView card = (com.google.android.material.card.MaterialCardView) v;
                    card.setStrokeColor(android.content.res.ColorStateList.valueOf(0xFF334155));
                    card.setStrokeWidth((int) (1 * density));
                    card.setCardElevation(4 * density);
                }
            }
        });
    }

    @Override
    public int getItemCount() {
        return episodeList.size();
    }

    static class EpisodeViewHolder extends RecyclerView.ViewHolder {
        TextView tvEpTitle, tvEpMeta;
        MaterialButton btnPlayEp;
        ImageButton btnEpDownload;

        public EpisodeViewHolder(@NonNull View itemView) {
            super(itemView);
            tvEpTitle = itemView.findViewById(R.id.tvEpTitle);
            tvEpMeta = itemView.findViewById(R.id.tvEpMeta);
            btnPlayEp = itemView.findViewById(R.id.btnPlayEp);
            btnEpDownload = itemView.findViewById(R.id.btnEpDownload);
        }
    }
}
