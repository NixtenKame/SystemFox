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
    echo json_encode(['status' => 'error', 'message' => 'Invalid tag']);
    exit;
}

// Initialize disabled blacklist session if not set
if (!isset($_SESSION['disabled_blacklist_tags'])) {
    $_SESSION['disabled_blacklist_tags'] = [];
}

// Check if the tag is blacklisted in the database (case-insensitive)
$query = "SELECT id FROM user_blacklist WHERE user_id = ? AND LOWER(TRIM(tag)) = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("is", $user_id, $tag);
$stmt->execute();
$result = $stmt->get_result();
$isBlacklisted = $result->num_rows > 0;
$stmt->close();

// If the tag is blacklisted, allow temporary disable without removing from DB
if ($isBlacklisted) {
    // Normalize all disabled tags to lowercase for comparison
    $disabledTags = array_map('strtolower', array_map('trim', $_SESSION['disabled_blacklist_tags']));
    
    if (in_array($tag, $disabledTags)) {
        // Re-enable the tag (remove from disabled list - case-insensitive removal)
        $_SESSION['disabled_blacklist_tags'] = array_values(array_filter($_SESSION['disabled_blacklist_tags'], function($disabledTag) use ($tag) {
            return strtolower(trim($disabledTag)) !== $tag;
        }));
        echo json_encode(['status' => 'success', 'action' => 'enabled', 'message' => 'Tag re-enabled']);
    } else {
        // Temporarily disable the tag
        $_SESSION['disabled_blacklist_tags'][] = $tag;
        echo json_encode(['status' => 'success', 'action' => 'disabled', 'message' => 'Tag temporarily disabled']);
    }
} else {
    // If not blacklisted, add to database (normalized to lowercase)
    $insertQuery = "INSERT INTO user_blacklist (user_id, tag) VALUES (?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bind_param("is", $user_id, $tag);
    $insertStmt->execute();
    $insertStmt->close();

    echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Tag added to blacklist']);
}

$db->close();
?>