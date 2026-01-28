<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';


if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT tag FROM user_blacklist WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$blacklistedTags = [];
while ($row = $result->fetch_assoc()) {
    $blacklistedTags[] = $row['tag'];
}

echo json_encode($blacklistedTags); // Send blacklist as JSON
?>
