<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$tag = strtolower(trim($_POST['tag'] ?? ''));

// Validate tag input
if (empty($tag)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid tag', 'debug' => ['received_tag' => $_POST['tag'] ?? 'not set']]);
    exit;
}

// Initialize disabled blacklist session if not set
if (!isset($_SESSION['disabled_blacklist_tags'])) {
    $_SESSION['disabled_blacklist_tags'] = [];
}

// Normalize all disabled tags to lowercase for comparison
$disabledTags = array_map('strtolower', array_map('trim', $_SESSION['disabled_blacklist_tags']));

// Check if the tag is actually disabled
if (!in_array($tag, $disabledTags)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Tag is not disabled',
        'debug' => [
            'tag_looking_for' => $tag,
            'disabled_tags_count' => count($_SESSION['disabled_blacklist_tags']),
            'disabled_tags_raw' => $_SESSION['disabled_blacklist_tags'],
            'disabled_tags_normalized' => $disabledTags
        ]
    ]);
    exit;
}

// Remove tag from disabled blacklist (case-insensitive removal)
$_SESSION['disabled_blacklist_tags'] = array_values(array_filter($_SESSION['disabled_blacklist_tags'], function($disabledTag) use ($tag) {
    return strtolower(trim($disabledTag)) !== $tag;
}));

echo json_encode(['status' => 'success', 'message' => 'Blacklist tag re-enabled']);
?>
