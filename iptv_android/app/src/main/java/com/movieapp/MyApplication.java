package com.movieapp;

import android.content.Context;
import android.content.SharedPreferences;
import androidx.appcompat.app.AppCompatDelegate;
import androidx.multidex.MultiDex;
import androidx.multidex.MultiDexApplication;
import com.movieapp.network.RetrofitClient;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;

public class MyApplication extends MultiDexApplication {
    public static final String CRASH_PREFS = "CrashPrefs";
    public static final String CRASH_LOG = "crash_log";

    static {
        // Critical for Vector Drawables on Android 4.4 KitKat (API 19)
        AppCompatDelegate.setCompatVectorFromResourcesEnabled(true);
    }

    @Override
    protected void attachBaseContext(Context base) {
        super.attachBaseContext(base);
        MultiDex.install(this);
    }

    @Override
    public void onCreate() {
        super.onCreate();
        
        try {
            java.security.Security.insertProviderAt(org.conscrypt.Conscrypt.newProvider(), 1);
        } catch (Throwable e) {
            e.printStackTrace();
        }

        // Configure global HttpsURLConnection for Glide and ExoPlayer on Android 4.4
        try {
            SSLContext sslContext = RetrofitClient.getSSLContext();
            if (sslContext != null) {
                HttpsURLConnection.setDefaultSSLSocketFactory(sslContext.getSocketFactory());
                HttpsURLConnection.setDefaultHostnameVerifier((hostname, session) -> true);
            }
        } catch (Throwable t) {
            t.printStackTrace();
        }
        
        final Thread.UncaughtExceptionHandler defaultHandler = Thread.getDefaultUncaughtExceptionHandler();
        Thread.setDefaultUncaughtExceptionHandler(new Thread.UncaughtExceptionHandler() {
            @Override
            public void uncaughtException(Thread thread, Throwable throwable) {
                try {
                    String stackTrace = android.util.Log.getStackTraceString(throwable);
                    SharedPreferences prefs = getSharedPreferences(CRASH_PREFS, Context.MODE_PRIVATE);
                    prefs.edit().putString(CRASH_LOG, stackTrace).commit();
                } catch (Exception ignored) {}
                
                if (defaultHandler != null) {
                    defaultHandler.uncaughtException(thread, throwable);
                }
            }
        });
    }
}
