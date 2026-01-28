<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: text/css; charset=UTF-8');

// Extract user ID from URL path: /user/{id}/profile_style.css
preg_match('/\/user\/(\d+)\/profile_style\.css/', $_SERVER['REQUEST_URI'], $matches);
$userId = $matches[1] ?? '';

if (!$userId || !is_numeric($userId)) {
    exit; // invalid user
}

// Get custom_css from user table
$stmt = $db->prepare("SELECT custom_profile_css FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    exit; // no match
}

$row = $result->fetch_assoc();
echo $row['custom_profile_css'];