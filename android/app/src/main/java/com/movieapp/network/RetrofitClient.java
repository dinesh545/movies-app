package com.movieapp.network;

import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.UnknownHostException;
import java.util.ArrayList;
import java.util.List;
import okhttp3.Dns;
import okhttp3.OkHttpClient;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;
import java.security.Security;
import javax.net.ssl.SSLContext;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;
import org.conscrypt.Conscrypt;

public class RetrofitClient {
    public static final String BASE_URL = "https://movies.mybhiwani.in/";
    private static Retrofit retrofit = null;

    public static ApiService getApiService() {
        if (retrofit == null) {
            OkHttpClient.Builder clientBuilder = new OkHttpClient.Builder();
            
            // Prefer IPv4
            clientBuilder.dns(new Dns() {
                @Override
                public List<InetAddress> lookup(String hostname) throws UnknownHostException {
                    List<InetAddress> addresses = Dns.SYSTEM.lookup(hostname);
                    List<InetAddress> ipv4 = new ArrayList<>();
                    for (InetAddress address : addresses) {
                        if (address instanceof Inet4Address) {
                            ipv4.add(address);
                        }
                    }
                    if (!ipv4.isEmpty()) {
                        return ipv4;
                    }
                    return addresses;
                }
            });

            // Use Conscrypt for TLS
            try {
                X509TrustManager tm = Conscrypt.getDefaultX509TrustManager();
                SSLContext sslContext = SSLContext.getInstance("TLS", "Conscrypt");
                sslContext.init(null, new TrustManager[]{tm}, null);
                clientBuilder.sslSocketFactory(sslContext.getSocketFactory(), tm);
            } catch (Exception e) {
                e.printStackTrace();
            }

            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL)
                    .client(clientBuilder.build())
                    .addConverterFactory(GsonConverterFactory.create())
                    .build();
        }
        return retrofit.create(ApiService.class);
    }

    public static OkHttpClient getDownloadOkHttpClient() {
        OkHttpClient.Builder clientBuilder = new OkHttpClient.Builder();
        clientBuilder.connectTimeout(30, java.util.concurrent.TimeUnit.SECONDS);
        clientBuilder.readTimeout(0, java.util.concurrent.TimeUnit.SECONDS);

        // Prefer IPv4
        clientBuilder.dns(new Dns() {
            @Override
            public List<InetAddress> lookup(String hostname) throws UnknownHostException {
                List<InetAddress> addresses = Dns.SYSTEM.lookup(hostname);
                List<InetAddress> ipv4 = new ArrayList<>();
                for (InetAddress address : addresses) {
                    if (address instanceof Inet4Address) {
                        ipv4.add(address);
                    }
                }
                if (!ipv4.isEmpty()) {
                    return ipv4;
                }
                return addresses;
            }
        });

        // Use Conscrypt for modern TLS
        try {
            X509TrustManager tm = Conscrypt.getDefaultX509TrustManager();
            SSLContext sslContext = SSLContext.getInstance("TLS", "Conscrypt");
            sslContext.init(null, new TrustManager[]{tm}, null);
            clientBuilder.sslSocketFactory(sslContext.getSocketFactory(), tm);
        } catch (Exception e) {
            e.printStackTrace();
        }

        return clientBuilder.build();
    }
}
