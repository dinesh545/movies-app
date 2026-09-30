package com.movieapp.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.google.android.material.button.MaterialButton;
import com.movieapp.R;
import com.movieapp.model.DownloadItem;
import java.util.List;

public class DownloadsAdapter extends RecyclerView.Adapter<DownloadsAdapter.DownloadViewHolder> {

    public interface OnDownloadActionListener {
        void onPlay(DownloadItem item);
        void onDelete(DownloadItem item);
    }

    private final List<DownloadItem> downloadList;
    private final OnDownloadActionListener listener;

    public DownloadsAdapter(List<DownloadItem> downloadList, OnDownloadActionListener listener) {
        this.downloadList = downloadList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public DownloadViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_download, parent, false);
        return new DownloadViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull DownloadViewHolder holder, int position) {
        DownloadItem item = downloadList.get(position);
        holder.tvTitle.setText(item.getTitle());

        String sizeText = item.getFileSize() != null && !item.getFileSize().isEmpty() ? "💾 " + item.getFileSize() : "💾 Local File";
        holder.tvSize.setText(sizeText);

        if (DownloadItem.STATUS_COMPLETED.equals(item.getStatus())) {
            holder.tvStatus.setText("Offline Ready");
            holder.tvStatus.setTextColor(0xFF4ADE80);
            holder.btnPlay.setEnabled(true);
            holder.btnPlay.setText("PLAY");
        } else if (DownloadItem.STATUS_DOWNLOADING.equals(item.getStatus())) {
            holder.tvStatus.setText("Downloading...");
            holder.tvStatus.setTextColor(0xFF38BDF8);
            holder.btnPlay.setEnabled(false);
            holder.btnPlay.setText("WAIT");
        } else {
            holder.tvStatus.setText("Download Failed");
            holder.tvStatus.setTextColor(0xFFEF4444);
            holder.btnPlay.setEnabled(false);
            holder.btnPlay.setText("ERROR");
        }

        if (item.getPosterUrl() != null && !item.getPosterUrl().isEmpty()) {
            Glide.with(holder.itemView.getContext())
                    .load(item.getPosterUrl())
                    .placeholder(android.R.drawable.ic_menu_gallery)
                    .into(holder.imgPoster);
        } else {
            holder.imgPoster.setImageResource(android.R.drawable.ic_menu_gallery);
        }

        holder.btnPlay.setOnClickListener(v -> listener.onPlay(item));
        holder.btnDelete.setOnClickListener(v -> listener.onDelete(item));
    }

    @Override
    public int getItemCount() {
        return downloadList.size();
    }

    static class DownloadViewHolder extends RecyclerView.ViewHolder {
        ImageView imgPoster;
        TextView tvTitle, tvSize, tvStatus;
        MaterialButton btnPlay;
        ImageButton btnDelete;

        public DownloadViewHolder(@NonNull View itemView) {
            super(itemView);
            imgPoster = itemView.findViewById(R.id.imgDownloadPoster);
            tvTitle = itemView.findViewById(R.id.tvDownloadTitle);
            tvSize = itemView.findViewById(R.id.tvDownloadSize);
            tvStatus = itemView.findViewById(R.id.tvDownloadStatus);
            btnPlay = itemView.findViewById(R.id.btnPlayOffline);
            btnDelete = itemView.findViewById(R.id.btnDeleteOffline);
        }
    }
}
