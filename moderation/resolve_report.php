<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'moderator')) {
    die("Access Denied: You do not have permission to perform this action.");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $report_id = $_GET['id'];

    // Update report status to resolved
    $query = "UPDATE reports SET status = 'resolved' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        header("Location: moderation?success=resolved"); // Redirect back
        exit();
    } else {
        die("Error resolving report: " . $stmt->error);
    }
} else {
    die("Invalid request.");
}
?>
