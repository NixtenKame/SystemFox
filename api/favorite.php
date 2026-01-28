<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';


header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in to favorite images."]);
    exit;
}

$userId = $_SESSION['user_id'];
$imageId = intval($_POST['image_id'] ?? 0);

if ($imageId === 0) {
    echo json_encode(["success" => false, "message" => "Invalid image ID."]);
    exit;
}

$checkQuery = $db->prepare("SELECT * FROM favorites WHERE user_id = ? AND image_id = ?");
$checkQuery->bind_param("ii", $userId, $imageId);
$checkQuery->execute();
$result = $checkQuery->get_result();
$isFavorited = $result->num_rows > 0;
$checkQuery->close();

if ($isFavorited) {
    $deleteQuery = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND image_id = ?");
    $deleteQuery->bind_param("ii", $userId, $imageId);
    $success = $deleteQuery->execute();
    $deleteQuery->close();
    echo json_encode(["success" => $success, "isFavorited" => false]);
} else {
    $insertQuery = $db->prepare("INSERT INTO favorites (user_id, image_id) VALUES (?, ?)");
    $insertQuery->bind_param("ii", $userId, $imageId);
    $success = $insertQuery->execute();
    $insertQuery->close();
    echo json_encode(["success" => $success, "isFavorited" => true]);
}
?>
