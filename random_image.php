<?php
define('ROOT_PATH', realpath(__DIR__ . '/..'));

include_once ROOT_PATH . '/connections/config.php';

// Get a random image ID
$query = "SELECT id FROM uploads ORDER BY RAND() LIMIT 1";
$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $randomImageId = $row['id'];
    header("Location: /posts/$randomImageId");
    exit();
} else {
    // Handle the case where no images are found
    header("Location: /posts/");
    exit();
}
?>