# Proguard rules for IPTV KitKat
-keep class org.conscrypt.** { *; }
-keep class androidx.media3.** { *; }
-keep class com.soni.iptv.model.** { *; }
-dontwarn org.conscrypt.**
-dontwarn okhttp3.**
