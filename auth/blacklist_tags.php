<?php
define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';
if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['tags'])) {
        $tags = explode(",", $_POST['tags']); // Convert comma-separated input into an array
        $tags = array_map('trim', $tags); // Trim spaces

        // Clear previous blacklist
        $stmt = $db->prepare("DELETE FROM user_blacklist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Insert new blacklist tags
        $stmt = $db->prepare("INSERT INTO user_blacklist (user_id, tag) VALUES (?, ?)");
        foreach ($tags as $tag) {
            if (!empty($tag)) { // Avoid empty tags
                $stmt->bind_param("is", $user_id, $tag);
                $stmt->execute();
            }
        }

        echo "Blacklist updated!";
    } else {
        echo "No tags provided!";
    }
}
?>
