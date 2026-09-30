package com.movieapp.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.google.android.material.button.MaterialButton;
import com.movieapp.R;
import com.movieapp.model.MediaItem;
import java.util.List;

public class MediaAdapter extends RecyclerView.Adapter<MediaAdapter.MediaViewHolder> {

    private final List<MediaItem> mediaList;
    private final OnMediaClickListener listener;

    public interface OnMediaClickListener {
        void onMediaClick(MediaItem item);
    }

    public MediaAdapter(List<MediaItem> mediaList, OnMediaClickListener listener) {
        this.mediaList = mediaList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public MediaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_movie, parent, false);
        return new MediaViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MediaViewHolder holder, int position) {
        MediaItem item = mediaList.get(position);
        android.content.Context ctx = holder.itemView.getContext();
        com.movieapp.util.DownloadTracker tracker = com.movieapp.util.DownloadTracker.getInstance(ctx);

        holder.tvTitle.setText(item.getTitle());
        holder.tvMeta.setText(item.getReleaseYear() + " | ⭐ " + item.getRating());
        holder.tvDescription.setText(item.getDescription() != null ? item.getDescription() : "");

        final String mediaKey = "movie_" + item.getId();
        boolean isSeries = "series".equalsIgnoreCase(item.getType());

        if (isSeries) {
            holder.btnAction.setText("EPISODES");
            holder.btnMovieDownload.setVisibility(View.GONE);
        } else {
            holder.btnMovieDownload.setVisibility(View.VISIBLE);
            boolean isDownloaded = tracker.isDownloaded(mediaKey);
            boolean isDownloading = tracker.isDownloading(ctx, mediaKey);

            if (isDownloaded) {
                holder.btnAction.setText("PLAY 💾");
                holder.btnMovieDownload.setImageResource(R.drawable.ic_delete);
                holder.btnMovieDownload.setColorFilter(0xFFEF4444);
                holder.btnMovieDownload.setOnClickListener(v -> {
                    new androidx.appcompat.app.AlertDialog.Builder(ctx)
                            .setTitle("Delete Movie")
                            .setMessage("Delete \"" + item.getTitle() + "\" from offline storage?")
                            .setPositiveButton("Delete", (dialog, which) -> {
                                tracker.deleteDownload(ctx, mediaKey);
                                android.widget.Toast.makeText(ctx, "Deleted from offline storage", android.widget.Toast.LENGTH_SHORT).show();
                                notifyItemChanged(holder.getAdapterPosition());
                            })
                            .setNegativeButton("Cancel", null)
                            .show();
                });
            } else if (isDownloading) {
                holder.btnAction.setText("PLAY");
                holder.btnMovieDownload.setImageResource(R.drawable.ic_download);
                holder.btnMovieDownload.setColorFilter(0xFF94A3B8);
                holder.btnMovieDownload.setOnClickListener(v -> {
                    android.widget.Toast.makeText(ctx, "Download is in progress...", android.widget.Toast.LENGTH_SHORT).show();
                });
            } else {
                holder.btnAction.setText("PLAY");
                holder.btnMovieDownload.setImageResource(R.drawable.ic_download);
                holder.btnMovieDownload.setColorFilter(0xFF38BDF8);
                holder.btnMovieDownload.setOnClickListener(v -> {
                    long id = tracker.startDownload(ctx, mediaKey, item.getTitle(), item.getPosterUrl(), item.getStreamUrl(), "movie");
                    if (id != -1) {
                        android.widget.Toast.makeText(ctx, "Starting download: " + item.getTitle() + " ⬇️", android.widget.Toast.LENGTH_SHORT).show();
                        notifyItemChanged(holder.getAdapterPosition());
                    } else {
                        android.widget.Toast.makeText(ctx, "Download failed to start.", android.widget.Toast.LENGTH_SHORT).show();
                    }
                });
            }
        }

        if (item.getPosterUrl() != null && !item.getPosterUrl().isEmpty()) {
            Glide.with(holder.itemView.getContext())
                    .load(item.getPosterUrl())
                    .placeholder(android.R.drawable.ic_menu_gallery)
                    .into(holder.imgPoster);
        } else {
            holder.imgPoster.setImageResource(android.R.drawable.ic_menu_gallery);
        }

        holder.btnAction.setOnClickListener(v -> listener.onMediaClick(item));
        holder.itemView.setOnClickListener(v -> listener.onMediaClick(item));

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
        return mediaList.size();
    }

    static class MediaViewHolder extends RecyclerView.ViewHolder {
        ImageView imgPoster;
        TextView tvTitle, tvMeta, tvDescription;
        MaterialButton btnAction;
        android.widget.ImageButton btnMovieDownload;

        public MediaViewHolder(@NonNull View itemView) {
            super(itemView);
            imgPoster = itemView.findViewById(R.id.imgPoster);
            tvTitle = itemView.findViewById(R.id.tvTitle);
            tvMeta = itemView.findViewById(R.id.tvMeta);
            tvDescription = itemView.findViewById(R.id.tvDescription);
            btnAction = itemView.findViewById(R.id.btnAction);
            btnMovieDownload = itemView.findViewById(R.id.btnMovieDownload);
        }
    }
}
