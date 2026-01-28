<?php
//detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Not logged in');
}

// Get the token from the request body
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;

if ($token) {
    // Include your existing DB connection
    require_once 'includes/config.php'; // adjust path if needed

    $userId = $_SESSION['user_id'];

    // Update the token in your users table
    $stmt = $db->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $userId);
    $stmt->execute();
    $stmt->close();

    echo 'Token saved.';
} else {
    http_response_code(400);
    echo 'No token received.';
}