<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to favorite images.");
}

if (isset($_POST['image_id'])) {
    $userId = $_SESSION['user_id'];
    $imageId = intval($_POST['image_id']);

    if ($imageId <= 0) {
        die("Invalid image ID.");
    }

    $checkQuery = $db->prepare("SELECT * FROM favorites WHERE user_id = ? AND image_id = ?");
    $checkQuery->bind_param("ii", $userId, $imageId);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        $deleteQuery = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND image_id = ?");
        $deleteQuery->bind_param("ii", $userId, $imageId);
        $deleteQuery->execute();
        $deleteQuery->close();
        $response = ['status' => 'removed'];
    } else {
        $insertQuery = $db->prepare("INSERT INTO favorites (user_id, image_id) VALUES (?, ?)");
        $insertQuery->bind_param("ii", $userId, $imageId);
        $insertQuery->execute();
        $insertQuery->close();
        $response = ['status' => 'added'];
    }

    $checkQuery->close();

    header("Location: /posts/$imageId/");
    exit();
} else {
    die("Invalid request.");
}
?>
