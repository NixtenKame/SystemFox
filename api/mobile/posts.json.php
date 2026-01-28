<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json; charset=UTF-8');

$stmt = $db->prepare("SELECT id, file_name, display_name, category, uploaded_by, upload_date, created_at, updated_at, up_score, down_score, score, source, rating, is_note_locked, is_rating_locked, is_status_locked, is_pending, is_flagged, is_deleted, uploader_ip_addr, approver_id, fav_string, tag_count, tag_count_general, tag_count_artist, tag_count_character, tag_count_copyright, file_ext, file_size, image_width, image_height, parent_id, has_children, last_commented_at, has_active_children, bit_flags, tag_count_meta, locked_tags, tag_count_species, tag_count_invalid, description, comment_count, change_seq, tag_count_lore, bg_color, duration, is_comment_disabled, is_comment_locked, tag_count_contributor, video_samples FROM uploads ORDER BY created_at DESC LIMIT 20");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query preparation failed.']);
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = [
        'id' => (int)$row['id'],
        'file_name' => $row['file_name'],
        'category' => (int)$row['category'],
        'display_name' => $row['display_name'],
        'description' => $row['description'],
        'uploaded_by' => (int)$row['uploaded_by'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'upload_date' => $row['upload_date'],
        'up_score' => (int)$row['up_score'],
        'down_score' => (int)$row['down_score'],
        'score' => (int)$row['score'],
        'source' => $row['source'],
        'rating' => $row['rating'],
        'is_note_locked' => (bool)$row['is_note_locked'],
        'is_rating_locked' => (bool)$row['is_rating_locked'],
        'is_status_locked' => (bool)$row['is_status_locked'],
        'is_pending' => (bool)$row['is_pending'],
        'is_flagged' => (bool)$row['is_flagged'],
        'is_deleted' => (bool)$row['is_deleted'],
        'tag_count' => (int)$row['tag_count'],
        'tag_count_general' => (int)$row['tag_count_general'],
        'tag_count_artist' => (int)$row['tag_count_artist'],
        'tag_count_character' => (int)$row['tag_count_character'],
        'tag_count_copyright' => (int)$row['tag_count_copyright'],
        'file_ext' => $row['file_ext'],
        'file_size' => (int)$row['file_size'],
        'image_width' => (int)$row['image_width'],
        'image_height' => (int)$row['image_height'],
        'parent_id' => (int)$row['parent_id'],
        'has_children' => (bool)$row['has_children'],
        'last_commented_at' => $row['last_commented_at'],
        'has_active_children' => (bool)$row['has_active_children'],
        'bit_flags' => (int)$row['bit_flags'],
        'tag_count_meta' => (int)$row['tag_count_meta'],
        'locked_tags' => $row['locked_tags'],
        'tag_count_species' => (int)$row['tag_count_species'],
        'tag_count_invalid' => (int)$row['tag_count_invalid'],
        'comment_count' => (int)$row['comment_count'],
        'change_seq' => (int)$row['change_seq'],
        'tag_count_lore' => (int)$row['tag_count_lore'],
        'bg_color' => $row['bg_color'],
        'duration' => (float)$row['duration'],
        'is_comment_disabled' => (bool)$row['is_comment_disabled'],
        'is_comment_locked' => (bool)$row['is_comment_locked'],
        'tag_count_contributor' => (int)$row['tag_count_contributor'],
        'video_samples' => $row['video_samples']
    ];
}
$stmt->close();
echo json_encode($posts);
?>