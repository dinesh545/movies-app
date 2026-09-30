<?php
// backend/db.php - SQLite Database Connection & Auto-Initialization

$dbDir = __DIR__ . '/database';
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$dbPath = $dbDir . '/soni_cinemas.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize Database Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL DEFAULT 'movie', -- 'movie' or 'series'
            title TEXT NOT NULL,
            description TEXT,
            poster_url TEXT,
            release_year INTEGER,
            rating TEXT DEFAULT '8.0',
            stream_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS seasons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            series_id INTEGER NOT NULL,
            season_number INTEGER NOT NULL,
            title TEXT NOT NULL,
            FOREIGN KEY (series_id) REFERENCES media_items(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS episodes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            season_id INTEGER NOT NULL,
            episode_number INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            stream_url TEXT NOT NULL,
            duration TEXT DEFAULT '45m',
            FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS site_settings (
            key_name TEXT PRIMARY KEY,
            key_value TEXT
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            mobile TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            is_email_verified INTEGER DEFAULT 0,
            is_admin_approved INTEGER DEFAULT 0,
            email_verification_otp TEXT,
            session_token TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS user_password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            otp_code TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS active_streams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            media_title TEXT NOT NULL,
            device_type TEXT DEFAULT 'Web',
            last_ping DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS user_suggestions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT 0,
            user_name TEXT NOT NULL,
            user_email TEXT,
            user_mobile TEXT,
            category TEXT NOT NULL,
            suggestion_text TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Check & Add column is_admin_approved if missing for existing installations
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin_approved INTEGER DEFAULT 0");
    } catch (Exception $e) {
        // Column already exists, ignore
    }

    // Insert Default Admin if not exists (Username: admin, Password: admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', :hash)");
        $insertStmt->execute([':hash' => $defaultPasswordHash]);
    }

    // Insert Default Site Settings if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultSettings = [
            'site_title' => 'Soni Cinemas',
            'site_tagline' => 'Stream Movies & Web Series Online',
            'site_logo' => '',
            'site_favicon' => '',
            'footer_text' => '© 2026 Soni Cinemas. All Rights Reserved.',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => 'Soni Cinemas'
        ];
        $settStmt = $pdo->prepare("INSERT INTO site_settings (key_name, key_value) VALUES (:key, :val)");
        foreach ($defaultSettings as $k => $v) {
            $settStmt->execute([':key' => $k, ':val' => $v]);
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

/**
 * Helper function to retrieve a site setting by key
 */
function get_site_setting($pdo, $key, $default = '') {
    static $settingsCache = null;
    if ($settingsCache === null) {
        try {
            $stmt = $pdo->query("SELECT key_name, key_value FROM site_settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return isset($settingsCache[$key]) && $settingsCache[$key] !== '' ? $settingsCache[$key] : $default;
}

