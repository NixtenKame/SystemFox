<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$stmt = $db->prepare("UPDATE users SET custom_profile_css = ? WHERE id = ?");
$stmt->bind_param("si", $_POST['custom_profile_css'], $_SESSION['user_id']);
$stmt->execute();
$stmt->close();
header("Location: /you/edit");