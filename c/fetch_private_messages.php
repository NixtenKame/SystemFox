<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;

if ($receiver_id <= 0) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

// Fetch the last 100 messages between sender and receiver
$stmt = $db->prepare("
    SELECT pm.id, pm.sender_id, pm.receiver_id, pm.message, pm.image_path, pm.timestamp, u.username 
    FROM private_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE (pm.sender_id = ? AND pm.receiver_id = ?) 
       OR (pm.sender_id = ? AND pm.receiver_id = ?)
    ORDER BY pm.timestamp ASC
");

$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();

$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id'          => (int)$row['id'],         // unique ID
        'sender_id'   => (int)$row['sender_id'],  // for left/right alignment
        'receiver_id' => (int)$row['receiver_id'],// needed for WS checks
        'message'     => $row['message'],         // text content
        'image_path'  => $row['image_path'],      // optional image
        'timestamp'   => $row['timestamp'],       // timestamp string
        'username'    => $row['username']         // display username
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($messages);
