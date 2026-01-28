<?php
// Simple helper to POST notifications to the local WebSocket push endpoint.
// Usage: call this after creating a notification in PHP, e.g. pushNotification($db, $userId, $message)

define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

function pushNotification($userId, $notificationsPayload) {
    // notificationsPayload should be an array or object suitable for clients
    $url = 'https://127.0.0.1:5502/push_notification';
    $data = [
        'user_id' => $userId,
        'payload' => [
            'type' => 'notification_update',
            'data' => $notificationsPayload
        ]
    ];

    $json = json_encode($data);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                        "Content-Length: " . strlen($json) . "\r\n",
            'content' => $json,
            'timeout' => 5
        ],
        'ssl' => [
            // If you're using self-signed certs for the WS server, disable peer verification.
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ];

    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) {
        $err = error_get_last();
        error_log('pushNotification failed: ' . ($err['message'] ?? 'unknown'));
        return false;
    }

    $resp = json_decode($result, true);
    return $resp;
}

// If called directly via POST (for testing), send a simple payload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['message'])) {
    $userId = $_POST['user_id'];
    $message = $_POST['message'];
    $payload = [[ 'id' => null, 'message' => $message ]];
    $res = pushNotification($userId, $payload);
    header('Content-Type: application/json');
    echo json_encode($res);
}
