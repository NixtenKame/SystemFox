<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$tag = $_POST['tag'] ?? '';

if (!$tag) {
    echo json_encode(['success' => false, 'error' => 'No tag specified']);
    exit;
}

if (!isset($_SESSION['disabled_blacklist_tags'])) {
    $_SESSION['disabled_blacklist_tags'] = [];
}

if (in_array($tag, $_SESSION['disabled_blacklist_tags'])) {
    $_SESSION['disabled_blacklist_tags'] = array_diff($_SESSION['disabled_blacklist_tags'], [$tag]);
} else {
    $_SESSION['disabled_blacklist_tags'][] = $tag;
}

echo json_encode(['success' => true]);
?>
