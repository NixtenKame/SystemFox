<?php
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));
include_once ROOT_PATH . '/connections/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json');

// ----------------------------
// Fetch all available posts
// ----------------------------
$whereClause = ["uploads.is_deleted = 0"];
$params = [];
$paramTypes = "";

// Build query
$mainQuery = "
    SELECT 
        uploads.id, uploads.file_name, uploads.category, uploads.description, uploads.uploaded_by, uploads.upload_date, uploads.display_name, uploads.tag_string,
        COALESCE(users.username, 'Unknown') AS username,
        GROUP_CONCAT(DISTINCT LOWER(TRIM(tags.tag_name)) ORDER BY tags.tag_name SEPARATOR ', ') AS tags,
        (SELECT COUNT(*) FROM image_likes WHERE image_id = uploads.id AND action = 'like') AS like_count,
        (SELECT COUNT(*) FROM image_likes WHERE image_id = uploads.id AND action = 'dislike') AS dislike_count,
        (SELECT COUNT(*) FROM favorites WHERE image_id = uploads.id) AS favorite_count,
        (SELECT COUNT(*) FROM comments WHERE image_id = uploads.id) AS comment_count
    FROM uploads
    LEFT JOIN users ON uploads.uploaded_by = users.id
    LEFT JOIN upload_tags ON uploads.id = upload_tags.upload_id
    LEFT JOIN tags ON upload_tags.tag_id = tags.tag_id
    WHERE " . implode(" AND ", $whereClause) . "
    GROUP BY uploads.id
    ORDER BY uploads.upload_date DESC
";

$mainStmt = $db->prepare($mainQuery);
if (!$mainStmt) {
    echo json_encode(["error" => "Failed to prepare query: " . $db->error]);
    exit;
}
$mainStmt->execute();
$result = $mainStmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = [
        "id" => (int)$row['id'],
        'display_name' => $row['display_name'],
        "file_name" => $row['file_name'],
        "description" => $row['description'],
        "image_url" => "https://nixten.ddns.net:9001/data" . $row['file_name']
    ];
}

// Echo JSON directly
echo json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
