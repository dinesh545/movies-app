<?php
// backend/api/submit_suggestion.php - Handle User Suggestion & Movie Request Submissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$category = trim($_POST['category'] ?? ($_GET['category'] ?? ''));
$suggestionText = trim($_POST['suggestion_text'] ?? ($_GET['suggestion_text'] ?? ''));
$name = trim($_POST['name'] ?? ($_GET['name'] ?? ''));
$email = trim($_POST['email'] ?? ($_GET['email'] ?? ''));
$mobile = trim($_POST['mobile'] ?? ($_GET['mobile'] ?? ''));
$userId = (int)($_POST['user_id'] ?? ($_SESSION['user_id'] ?? 0));
$sessionToken = trim($_POST['session_token'] ?? ($_SESSION['user_session_token'] ?? ''));

// Validate User from Session or Session Token
if ($userId <= 0 && !empty($sessionToken)) {
    try {
        $uStmt = $pdo->prepare("SELECT id, name, email, mobile FROM users WHERE session_token = :st");
        $uStmt->execute([':st' => $sessionToken]);
        $user = $uStmt->fetch();
        if ($user) {
            $userId = (int)$user['id'];
            if (empty($name)) $name = $user['name'];
            if (empty($email)) $email = $user['email'];
            if (empty($mobile)) $mobile = $user['mobile'];
        }
    } catch (Exception $e) {}
}

if ($userId > 0 && (empty($name) || empty($email))) {
    try {
        $uStmt = $pdo->prepare("SELECT name, email, mobile FROM users WHERE id = :uid");
        $uStmt->execute([':uid' => $userId]);
        $user = $uStmt->fetch();
        if ($user) {
            if (empty($name)) $name = $user['name'];
            if (empty($email)) $email = $user['email'];
            if (empty($mobile)) $mobile = $user['mobile'];
        }
    } catch (Exception $e) {}
}

if (empty($name)) {
    $name = 'Anonymous User';
}

if (empty($category)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a suggestion category.']);
    exit();
}

if (empty($suggestionText)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your suggestion or movie request message.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO user_suggestions (user_id, user_name, user_email, user_mobile, category, suggestion_text, status)
        VALUES (:uid, :name, :email, :mobile, :cat, :text, 'pending')
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':name' => $name,
        ':email' => $email,
        ':mobile' => $mobile,
        ':cat' => $category,
        ':text' => $suggestionText
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you! Your suggestion/request has been submitted successfully.'
    ]);
} catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit: ' . $e->getMessage()]);
}
