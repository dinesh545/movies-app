<?php
// backend/logout.php - User Logout Script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_mobile']);
unset($_SESSION['user_session_token']);

session_destroy();
header('Location: login.php');
exit();
