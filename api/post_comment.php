<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment']) || !isset($_POST['parent_id']) || !is_numeric($_POST['parent_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$commentText = $_POST['comment'];
$parentId = intval($_POST['parent_id']);
$userId = $_SESSION['user_id'];

$commentText = htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8');

$insertQuery = $db->prepare("INSERT INTO comments (image_id, user_id, comment, parent_id) 
                             SELECT image_id, ?, ?, ? FROM comments WHERE id = ?");
$insertQuery->bind_param("issi", $userId, $commentText, $parentId, $parentId);
$insertQuery->execute();

echo json_encode(['success' => true]);
?>
