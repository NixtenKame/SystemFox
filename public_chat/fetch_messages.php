<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

$query = "SELECT username, message, timestamp FROM messages ORDER BY timestamp ASC LIMIT 50";
$result = $db->query($query);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
