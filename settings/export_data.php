<?php
// Start session & config
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

// Prevent caching
header_remove();
if (ob_get_length()) ob_end_clean();
ob_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("You must be logged in to export your data.");
}

$userId = (int)$_SESSION['user_id'];

// Helper: safe DB fetch
function fetchData($db, $query, $types, ...$params) {
    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("DB Prepare Error: " . $db->error);
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Fetch user data
$userDataArr = fetchData($db, "
    SELECT id, username, email, profile_picture, bio, created_at, last_active,
           level, birthdate, user_type, parental_consent,
           user_role, last_username_change, last_activity, online_status,
           custom_status, email_visibility, base_upload_limit,
           last_logged_in_at, status, suspension_end,
           status_reason, status_reason_date
    FROM users WHERE id = ?",
    "i", $userId
);

if (!$userDataArr) {
    http_response_code(404);
    exit("User data not found.");
}

$userData = $userDataArr[0];

// Profile picture reference (metadata only)
$userData['file_name'] = !empty($userData['profile_picture'])
    ? "/public/uploads/profile_pictures/" . $userData['profile_picture']
    : null;


// Fetch related data
$data = [
    'user'             => $userData,
    'settings'         => fetchData($db, "SELECT theme, language, font_size, notifications_enabled, custom_css, wallpaper, custom_css_md5 FROM user_settings WHERE user_id = ?", "i", $userId)[0] ?? [],
    'blacklist'        => array_column(fetchData($db, "SELECT tag FROM user_blacklist WHERE user_id = ?", "i", $userId), 'tag'),
    'uploads'          => fetchData($db, "SELECT * FROM uploads WHERE uploaded_by = ?", "i", $userId),
    'comments'         => fetchData($db, "SELECT id, image_id, comment, parent_id, created_at FROM comments WHERE user_id = ?", "i", $userId),
    'favorites'        => fetchData($db, "SELECT id, user_id, image_id, favorited_at FROM favorites WHERE user_id = ?", "i", $userId),
    'image_likes'      => fetchData($db, "SELECT id, image_id, action, created_at FROM image_likes WHERE user_id = ?", "i", $userId),
    'forum_replies'    => fetchData($db, "SELECT forum_id, content, created_at FROM forum_replies WHERE user_id = ?", "i", $userId),
    'private_messages' => fetchData($db, "SELECT sender_id, receiver_id, message, timestamp FROM private_messages WHERE sender_id = ? OR receiver_id = ?", "ii", $userId, $userId),
    'notifications'    => fetchData($db, "SELECT message, status, created_at, dismissed FROM notifications WHERE user_id = ?", "i", $userId)
];

// Encode JSON
$jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Temp ZIP
$tempDir = sys_get_temp_dir();
$zipFile = $tempDir . "/user_data_" . $userId . ".zip";

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit("Failed to create ZIP file.");
}

// Add JSON
$zip->addFromString("user_data.json", $jsonData);

// Add uploaded images
// Adjust this path as needed
$uploadBaseDir = realpath("S:/FluffFox-Data/Data");

foreach ($data['uploads'] as $upload) {
    if (empty($upload['file_name'])) {
        continue;
    }

    // Normalize + prevent absolute escape
    $relativePath = ltrim(str_replace('\\', '/', $upload['file_name']), '/');
    $fullPath = $uploadBaseDir . DIRECTORY_SEPARATOR . $relativePath;

    if (is_file($fullPath)) {
        $zip->addFile($fullPath, "posts/" . $relativePath);
    }
}

// Add profile picture file
if (!empty($userData['profile_picture'])) {
    $profilePicRelPath = ltrim($userData['profile_picture'], '/');
    $profilePicFullPath = realpath(__DIR__ . "/../public/uploads/" . $profilePicRelPath);

    if ($profilePicFullPath && is_file($profilePicFullPath)) {
        $zip->addFile(
            $profilePicFullPath,
            "profile_picture/" . basename($profilePicFullPath)
        );
    }
}

// Finalize ZIP
$zip->close();

// Clear buffer
if (ob_get_length()) ob_end_clean();

// Send file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="user_data_' . $userId . '.zip"');
header('Content-Length: ' . filesize($zipFile));
flush();
readfile($zipFile);

// Cleanup
unlink($zipFile);
exit;
