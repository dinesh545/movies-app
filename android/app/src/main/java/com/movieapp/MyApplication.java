package com.movieapp;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import androidx.multidex.MultiDexApplication;

public class MyApplication extends MultiDexApplication {
    public static final String CRASH_PREFS = "CrashPrefs";
    public static final String CRASH_LOG = "crash_log";

    @Override
    public void onCreate() {
        super.onCreate();
        
        try {
            java.security.Security.insertProviderAt(org.conscrypt.Conscrypt.newProvider(), 1);
        } catch (Exception e) {
            e.printStackTrace();
        }
        
        final Thread.UncaughtExceptionHandler defaultHandler = Thread.getDefaultUncaughtExceptionHandler();
        Thread.setDefaultUncaughtExceptionHandler(new Thread.UncaughtExceptionHandler() {
            @Override
            public void uncaughtException(Thread thread, Throwable throwable) {
                String stackTrace = android.util.Log.getStackTraceString(throwable);
                SharedPreferences prefs = getSharedPreferences(CRASH_PREFS, Context.MODE_PRIVATE);
                prefs.edit().putString(CRASH_LOG, stackTrace).commit();
                
                if (defaultHandler != null) {
                    defaultHandler.uncaughtException(thread, throwable);
                }
            }
        });
    }
}
