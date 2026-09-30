<?php
// backend/admin/save_media.php

require_once __DIR__ . '/../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized admin session.']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add_movie') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $posterUrl = trim($_POST['poster_url'] ?? '');
        $streamUrl = trim($_POST['stream_url'] ?? '');
        $releaseYear = (int)($_POST['release_year'] ?? 2024);
        $rating = trim($_POST['rating'] ?? '8.0');

        if (empty($title) || empty($streamUrl)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Movie title and stream URL are required.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO media_items (type, title, description, poster_url, release_year, rating, stream_url) VALUES ('movie', :title, :desc, :poster, :year, :rating, :stream)");
        $stmt->execute([
            ':title' => $title,
            ':desc' => $description,
            ':poster' => $posterUrl,
            ':year' => $releaseYear,
            ':rating' => $rating,
            ':stream' => $streamUrl
        ]);

        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);

    } else if ($action === 'add_series') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $posterUrl = trim($_POST['poster_url'] ?? '');
        $releaseYear = (int)($_POST['release_year'] ?? 2024);
        $rating = trim($_POST['rating'] ?? '8.5');

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Series title is required.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO media_items (type, title, description, poster_url, release_year, rating) VALUES ('series', :title, :desc, :poster, :year, :rating)");
        $stmt->execute([
            ':title' => $title,
            ':desc' => $description,
            ':poster' => $posterUrl,
            ':year' => $releaseYear,
            ':rating' => $rating
        ]);

        $seriesId = $pdo->lastInsertId();

        // Automatically create "Season 1" for new series
        $seasonStmt = $pdo->prepare("INSERT INTO seasons (series_id, season_number, title) VALUES (:series_id, 1, 'Season 1')");
        $seasonStmt->execute([':series_id' => $seriesId]);

        echo json_encode(['status' => 'success', 'id' => $seriesId]);

    } else if ($action === 'add_season') {
        $seriesId = (int)($_POST['series_id'] ?? 0);
        $seasonNumber = (int)($_POST['season_number'] ?? 1);
        $title = trim($_POST['title'] ?? ('Season ' . $seasonNumber));

        $stmt = $pdo->prepare("INSERT INTO seasons (series_id, season_number, title) VALUES (:series_id, :num, :title)");
        $stmt->execute([
            ':series_id' => $seriesId,
            ':num' => $seasonNumber,
            ':title' => $title
        ]);

        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);

    } else if ($action === 'add_episode') {
        $seasonId = (int)($_POST['season_id'] ?? 0);
        $episodeNumber = (int)($_POST['episode_number'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $streamUrl = trim($_POST['stream_url'] ?? '');
        $duration = trim($_POST['duration'] ?? '45m');

        if ($seasonId <= 0 || empty($title) || empty($streamUrl)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Episode title and stream URL required.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, stream_url, duration) VALUES (:season_id, :ep_num, :title, :desc, :stream, :duration)");
        $stmt->execute([
            ':season_id' => $seasonId,
            ':ep_num' => $episodeNumber,
            ':title' => $title,
            ':desc' => $description,
            ':stream' => $streamUrl,
            ':duration' => $duration
        ]);

        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action parameter.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
