<?php

define('ROOT_PATH', realpath(__DIR__ . '/../..'));

include_once ROOT_PATH . '/connections/config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'moderator') {
    die("Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];

    if ($action == "remove") {
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$report_id]);
        echo "Content removed.";
    } elseif ($action == "dismiss") {
        $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?")->execute([$report_id]);
        echo "Report dismissed.";
    }
}
?>
