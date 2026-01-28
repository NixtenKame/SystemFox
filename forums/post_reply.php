<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to reply.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST); // Check submitted form data
    echo "</pre>";

    $forumId = $_POST['forum_id'];
    $content = trim($_POST['content']);
    $userId = $_SESSION['user_id'];
    $imagePath = null;

    if (empty($content)) {
        die("Reply content cannot be empty.");
    }

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../public/uploads/forums/";
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = "/uploads/forums/" . $fileName;
        } else {
            echo "File upload failed.";
        }
    }

    $stmt = $db->prepare("INSERT INTO posts (forum_id, user_id, content, image_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $forumId, $userId, $content, $imagePath);
    if ($stmt->execute()) {
        echo "Reply posted successfully!";
        header("Location: thread?id=" . $forumId);
        exit();
    } else {
        echo "Error posting reply: " . $db->error;
    }

    $stmt->close();
}
?>
