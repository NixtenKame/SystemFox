<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$blacklist = [];

// Fetch blacklisted tags for the logged-in user
$query = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $blacklist[] = $row['tag'];
}

echo json_encode($blacklist);
exit;
?>
