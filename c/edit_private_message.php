<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = intval($_POST['message_id']);
    $new_message = trim($_POST['new_message']);
    $user_id = $_SESSION['user_id'];

    if (empty($new_message)) {
        die(json_encode(['status' => 'error', 'message' => 'Message cannot be empty']));
    }

    // Check if the message belongs to the logged-in user
    $stmt = $db->prepare("SELECT sender_id FROM private_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    $stmt->close();

    if (!$message || $message['sender_id'] !== $user_id) {
        die(json_encode(['status' => 'error', 'message' => 'You can only edit your own messages']));
    }

    // Update the message and mark it as edited
    $stmt = $db->prepare("UPDATE private_messages SET message = ?, edited = 1 WHERE id = ?");
    $stmt->bind_param("si", $new_message, $message_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Message updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update message']);
    }

    $stmt->close();
}
?>