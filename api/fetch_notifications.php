<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$userId = $_SESSION['user_id'];

$query = "SHOW COLUMNS FROM notifications LIKE 'is_read'";
$result = $db->query($query);
$isReadExists = $result->num_rows > 0;

if ($isReadExists) {
    $query = "SELECT id, message FROM notifications WHERE user_id = ? AND is_read = 0 AND dismissed = 0 ORDER BY created_at DESC";
} else {
    $query = "SELECT id, message FROM notifications WHERE user_id = ? AND dismissed = 0 ORDER BY created_at DESC";
}

$stmt = $db->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        "id" => $row['id'],
        "message" => $row['message']
    ];
}

$stmt->close();
echo json_encode($notifications);
?>