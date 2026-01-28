<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to vote.");
}

$postId = $_GET['id'];
$type = $_GET['type'];

if ($type === "up") {
    $stmt = $db->prepare("UPDATE posts SET votes = votes + 1 WHERE id = ?");
} else {
    $stmt = $db->prepare("UPDATE posts SET votes = votes - 1 WHERE id = ?");
}

$stmt->bind_param("i", $postId);
$stmt->execute();
$stmt->close();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
