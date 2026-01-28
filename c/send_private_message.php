<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');
$imagePath   = null;

if ($receiver_id <= 0) {
    echo json_encode(["error" => "Invalid receiver."]);
    exit;
}

if (empty($message) && empty($_FILES['image']['name'])) {
    echo json_encode(["error" => "Message or image is required."]);
    exit;
}

/**
 * Fetch sender + receiver ages
 */
$ageStmt = $db->prepare("
    SELECT id, username, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age
    FROM users
    WHERE id IN (?, ?)
");
$ageStmt->bind_param("ii", $sender_id, $receiver_id);
$ageStmt->execute();
$result = $ageStmt->get_result();

$sender   = null;
$receiver = null;

while ($row = $result->fetch_assoc()) {
    if ((int)$row['id'] === $sender_id) {
        $sender = $row;
    } elseif ((int)$row['id'] === $receiver_id) {
        $receiver = $row;
    }
}

$ageStmt->close();

if (!$sender || !$receiver) {
    echo json_encode(["error" => "Sender or receiver not found."]);
    exit;
}

/**
 * Age-group enforcement
 */
$senderAge   = (int)$sender['age'];
$receiverAge = (int)$receiver['age'];

$senderIsAdult   = ($senderAge >= 18);
$receiverIsAdult = ($receiverAge >= 18);

// Block cross-age messaging
if ($senderIsAdult !== $receiverIsAdult) {
    echo json_encode([
        "error" => "Messaging between 13–17 users and 18+ users is not allowed."
    ]);
    exit;
}

// Extra safety: block under-13 entirely
if ($senderAge < 13 || $receiverAge < 13) {
    echo json_encode(["error" => "Messaging is not allowed for users under 13."]);
    exit;
}

/**
 * Handle image upload
 */
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = ROOT_PATH . '/uploads/chat_images/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageName = uniqid('chat_', true) . '_' . basename($_FILES['image']['name']);
    $fullPath  = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
        echo json_encode(["error" => "Error uploading image."]);
        exit;
    }

    $imagePath = '/uploads/chat_images/' . $imageName;
}

/**
 * Insert message
 */
$stmt = $db->prepare("
    INSERT INTO private_messages (sender_id, receiver_id, message, image_path)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $imagePath);

if (!$stmt->execute()) {
    echo json_encode(["error" => "Error sending message."]);
    $stmt->close();
    exit;
}

$message_id = $stmt->insert_id;
$stmt->close();

/**
 * Fetch inserted message
 */
$stmt = $db->prepare("
    SELECT pm.id, pm.sender_id, pm.receiver_id, pm.message,
           pm.image_path, pm.timestamp, u.username
    FROM private_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE pm.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$msgData = $stmt->get_result()->fetch_assoc();
$stmt->close();

/**
 * Create notification
 */
$chatUrl = "/c/$sender_id";
$notificationMessage = $sender['username'] . ": " . ($message ?: "📷 Image");

$stmt = $db->prepare("
    INSERT INTO notifications (user_id, message, url, sender_id)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("issi", $receiver_id, $notificationMessage, $chatUrl, $sender_id);
$stmt->execute();
$stmt->close();

/**
 * Success
 */
echo json_encode([
    "success" => true,
    "message" => $msgData
]);