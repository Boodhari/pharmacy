<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Set timeout duration in seconds (10 minutes)
$timeout_duration = 600; // 10 * 60

// Check if last activity is set and compare with current time
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Timeout exceeded
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
