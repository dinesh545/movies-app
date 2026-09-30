package com.movieapp.util;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;

public class DownloadReceiver extends BroadcastReceiver {
    public static final String ACTION_DOWNLOAD_UPDATED = "com.movieapp.DOWNLOAD_UPDATED";

    @Override
    public void onReceive(Context context, Intent intent) {
        // Broadcasts are dispatched locally to refresh active screens
    }
}
