<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: text/css; charset=UTF-8');

$md5 = $_GET['md5'] ?? '';

if (!$md5 || !preg_match('/^[a-f0-9]{32}$/', $md5)) {
    exit; // invalid md5
}

// Step 1: Find user_id by md5 in user_settings table
$stmt = $db->prepare("SELECT user_id FROM user_settings WHERE custom_css_md5 = ?");
$stmt->bind_param("s", $md5);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    exit; // no match
}

$row = $result->fetch_assoc();
$user_id = $row['user_id'];

// Step 2: Get custom_css from user_settings table
$stmt2 = $db->prepare("SELECT custom_css FROM user_settings WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2 && $cssRow = $result2->fetch_assoc()) {
    echo $cssRow['custom_css'];
}