package com.movieapp.network;

import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.UnknownHostException;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.TimeUnit;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;
import okhttp3.Dns;
import okhttp3.OkHttpClient;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class RetrofitClient {
    public static final String BASE_URL = "https://movies.mybhiwani.in/";
    private static Retrofit retrofit = null;
    private static OkHttpClient sharedOkHttpClient = null;

    public static X509TrustManager getTrustManager() {
        return new X509TrustManager() {
            @Override
            public void checkClientTrusted(X509Certificate[] chain, String authType) throws CertificateException {
            }

            @Override
            public void checkServerTrusted(X509Certificate[] chain, String authType) throws CertificateException {
                // Trust server certificate on older Android 4.4 KitKat devices
                // which lack modern CA root anchors (e.g. ISRG Root X1, Let's Encrypt)
            }

            @Override
            public X509Certificate[] getAcceptedIssuers() {
                return new X509Certificate[0];
            }
        };
    }

    public static SSLContext getSSLContext() {
        X509TrustManager tm = getTrustManager();
        SSLContext sslContext;
        try {
            sslContext = SSLContext.getInstance("TLS", "Conscrypt");
        } catch (Exception e) {
            try {
                sslContext = SSLContext.getInstance("TLS");
            } catch (Exception ex) {
                return null;
            }
        }
        try {
            sslContext.init(null, new TrustManager[]{tm}, new java.security.SecureRandom());
            return sslContext;
        } catch (Exception e) {
            return null;
        }
    }

    private static void configureSslAndDns(OkHttpClient.Builder clientBuilder) {
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

        // Use custom TLS 1.2/1.3 SocketFactory + TrustManager for KitKat compatibility
        try {
            X509TrustManager tm = getTrustManager();
            SSLContext sslContext = getSSLContext();
            if (sslContext != null) {
                clientBuilder.sslSocketFactory(sslContext.getSocketFactory(), tm);
                clientBuilder.hostnameVerifier((hostname, session) -> true);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public static synchronized ApiService getApiService() {
        if (retrofit == null) {
            OkHttpClient.Builder clientBuilder = new OkHttpClient.Builder();
            clientBuilder.connectTimeout(20, TimeUnit.SECONDS);
            clientBuilder.readTimeout(20, TimeUnit.SECONDS);
            configureSslAndDns(clientBuilder);

            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL)
                    .client(clientBuilder.build())
                    .addConverterFactory(GsonConverterFactory.create())
                    .build();
        }
        return retrofit.create(ApiService.class);
    }

    public static synchronized OkHttpClient getOkHttpClient() {
        if (sharedOkHttpClient == null) {
            OkHttpClient.Builder clientBuilder = new OkHttpClient.Builder();
            clientBuilder.connectTimeout(15, TimeUnit.SECONDS);
            clientBuilder.readTimeout(15, TimeUnit.SECONDS);
            configureSslAndDns(clientBuilder);
            sharedOkHttpClient = clientBuilder.build();
        }
        return sharedOkHttpClient;
    }

    public static OkHttpClient getDownloadOkHttpClient() {
        OkHttpClient.Builder clientBuilder = new OkHttpClient.Builder();
        clientBuilder.connectTimeout(30, TimeUnit.SECONDS);
        clientBuilder.readTimeout(0, TimeUnit.SECONDS); // 0 = no timeout for large video downloads
        configureSslAndDns(clientBuilder);
        return clientBuilder.build();
    }
}
