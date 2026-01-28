<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create a thread.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST); // Check what data is being submitted
    echo "</pre>";

    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $userId = $_SESSION['user_id'];

    if (empty($title)) {
        die("Title cannot be empty.");
    }

    $stmt = $db->prepare("INSERT INTO forums (user_id, title, category) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $title, $category);
    if ($stmt->execute()) {
        echo "Thread created successfully!";
        header("Location: forum");
        exit();
    } else {
        echo "Error creating thread: " . $db->error;
    }

    $stmt->close();
}
?>
